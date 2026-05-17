<script setup>
import { Head } from '@inertiajs/vue3';
import MarkdownRender from 'markstream-vue';
import 'markstream-vue/index.css';
import { computed, nextTick, ref } from 'vue';
import ToolInspector from '../Components/ToolInspector.vue';

// ─── estado ────────────────────────────────────────────────────────────
// Cada item é { role, content, toolCalls? }. O toolCalls[] guarda os
// tool calls que aconteceram DENTRO desse turno do assistant, na ordem.
const messages = ref([]);
const input = ref('');
const isStreaming = ref(false);
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const scroller = ref(null);

// Provider + model atual — preenchidos pelo stream_start event do Prism
// (que vem com `model` e `provider` no payload).
const currentProvider = ref('');
const currentModel = ref('');

// Lista plana de tool calls pro painel direito (com ref pra mensagem).
const allToolCalls = computed(() =>
    messages.value.flatMap((m, msgIdx) =>
        (m.toolCalls ?? []).map((tc) => ({ ...tc, msgIdx })),
    ),
);

async function send() {
    const text = input.value.trim();
    if (!text || isStreaming.value) return;

    messages.value.push({ role: 'user', content: text });
    input.value = '';
    isStreaming.value = true;
    await scrollDown();

    // Cria o turno do assistant — vai sendo populado pelos eventos.
    // IMPORTANTE: pegar a referência DEPOIS do push pra ter o proxy
    // reativo do Vue. Mutar o objeto original (antes do push) não
    // dispara reatividade, então o inspector e o streaming "somem".
    // `streaming: true` enquanto deltas chegam → vira false no done; o
    // MarkdownRender usa isso como `:final` (estável vs. parser ao vivo).
    messages.value.push({ role: 'assistant', content: '', toolCalls: [], streaming: true });
    const assistantMsg = messages.value.at(-1);

    // Histórico enviado pro backend = tudo MENOS o turno em construção.
    const payload = {
        messages: messages.value
            .slice(0, -1)
            .map(({ role, content }) => ({ role, content })),
    };

    try {
        const res = await fetch('/chat/stream', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'text/event-stream',
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok || !res.body) {
            assistantMsg.content = `⚠ HTTP ${res.status}`;
            return;
        }

        await consumeSse(res.body, assistantMsg);
    } catch (e) {
        assistantMsg.content = `⚠ ${e.message}`;
    } finally {
        assistantMsg.streaming = false;
        isStreaming.value = false;
        await scrollDown();
    }
}

/**
 * Lê o ReadableStream do fetch como SSE e despacha cada evento pro
 * estado. Fragmenta por "\n\n" (delimitador SSE) e parseia event: + data:.
 */
async function consumeSse(body, assistantMsg) {
    const reader = body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
        const { value, done } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });

        // Frames SSE separados por linha em branco.
        let sepIdx;
        while ((sepIdx = buffer.indexOf('\n\n')) !== -1) {
            const frame = buffer.slice(0, sepIdx);
            buffer = buffer.slice(sepIdx + 2);
            handleFrame(frame, assistantMsg);
        }
    }
}

function handleFrame(frame, assistantMsg) {
    let event = 'message';
    let data = '';
    for (const line of frame.split('\n')) {
        if (line.startsWith('event:')) event = line.slice(6).trim();
        else if (line.startsWith('data:')) data += line.slice(5).trim();
    }
    if (!data) return;

    let parsed;
    try {
        parsed = JSON.parse(data);
    } catch {
        return;
    }

    // Event names + payload shapes vêm direto do toArray() de cada
    // StreamEvent do Prism (enum StreamEventType com underscore).
    switch (event) {
        case 'stream_start':
            // Primeiro evento do stream — traz model + provider do Prism.
            // Usado pra popular o header da página.
            currentModel.value = parsed.model ?? '';
            currentProvider.value = parsed.provider ?? '';
            break;
        case 'text_delta':
            assistantMsg.content += parsed.delta;
            scrollDown();
            break;
        case 'tool_call':
            assistantMsg.toolCalls.push({
                id: parsed.tool_id,            // ToolCall->id
                name: parsed.tool_name,         // ToolCall->name
                arguments: parsed.arguments,    // ToolCall->arguments()
                timestamp: parsed.timestamp,    // base StreamEvent->timestamp
                result: null,
                pending: true,
            });
            break;
        case 'tool_result': {
            const tc = assistantMsg.toolCalls.find(
                (t) => t.id === parsed.tool_id, // ToolResult->toolCallId
            );
            if (tc) {
                tc.result = parsed.result;
                tc.pending = false;
            }
            break;
        }
        case 'stream_end': {
            // Cada step do agente (tool call + tool result) dispara um
            // stream_end. Acumulamos os tokens de todos pra mostrar o
            // total do turno.
            const u = parsed.usage;
            if (!u) break;
            if (!assistantMsg.usage) {
                assistantMsg.usage = { prompt: 0, completion: 0, cache_read: 0, cache_write: 0 };
            }
            assistantMsg.usage.prompt += u.prompt_tokens ?? 0;
            assistantMsg.usage.completion += u.completion_tokens ?? 0;
            assistantMsg.usage.cache_read += u.cache_read_input_tokens ?? 0;
            assistantMsg.usage.cache_write += u.cache_write_input_tokens ?? 0;
            break;
        }
        case 'step_finish':
            // ignorado na UI por enquanto
            break;
    }
}

async function scrollDown() {
    await nextTick();
    if (scroller.value) {
        scroller.value.scrollTop = scroller.value.scrollHeight;
    }
}

function reset() {
    messages.value = [];
    input.value = '';
}

const samplePrompts = [
    'List open tickets for ACME',
    'Propose a solution for ticket #5',
    'Mark #5 as in_progress',
    'Find tickets about webhook issues',
];
</script>
<template>
    <Head title="Support Agent" />

    <div class="flex h-screen w-screen bg-zinc-50 text-zinc-900">
        <!-- ─── sidebar: chat thread ──────────────────────────────── -->
        <section class="flex flex-col flex-1 border-r border-zinc-200">
            <header class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 bg-white">
                <div>
                    <h1 class="text-lg font-semibold">PHP Velho Oeste 2026 - Support Agent</h1>
                    <p class="text-xs text-zinc-500 flex flex-wrap gap-x-1.5 items-center">
                        <template v-if="currentProvider || currentModel">
                            <span class="capitalize">{{ currentProvider }}</span>
                            <span v-if="currentProvider && currentModel">·</span>
                            <span class="font-mono">{{ currentModel }}</span>
                            <span>·</span>
                        </template>
                        <span>{{ messages.length }} messages</span>
                    </p>
                </div>
                <button
                    @click="reset"
                    class="cursor-pointer text-xs text-zinc-500 hover:text-zinc-900 transition"
                >
                    new chat
                </button>
            </header>

            <!-- transcript -->
            <div ref="scroller" class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                <div
                    v-if="messages.length === 0"
                    class="max-w-md mx-auto text-center text-zinc-500 mt-20"
                >
                    <div class="text-4xl mb-3">💬</div>
                    <p class="font-medium mb-4">How can I help with your tickets?</p>
                    <div class="grid grid-cols-1 gap-2 text-left">
                        <button
                            v-for="p in samplePrompts"
                            :key="p"
                            @click="input = p"
                            class="px-3 py-2 text-sm rounded-lg border border-zinc-200 hover:bg-zinc-100 transition text-zinc-700"
                        >
                            {{ p }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(m, i) in messages"
                    :key="i"
                    :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
                >
                    <div
                        :class="[
                            'max-w-[80%] rounded-2xl px-4 py-3 text-sm leading-relaxed',
                            m.role === 'user'
                                ? 'bg-indigo-600 text-white whitespace-pre-wrap'
                                : 'bg-white border border-zinc-200 text-zinc-900',
                        ]"
                    >
                        <template v-if="m.role === 'assistant' && m.toolCalls?.length">
                            <div class="mb-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="tc in m.toolCalls"
                                    :key="tc.id"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-zinc-100 border border-zinc-200 text-[11px] font-mono text-zinc-600"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            tc.pending ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500',
                                        ]"
                                    />
                                    {{ tc.name }}
                                </span>
                            </div>
                        </template>

                        <!-- user mantém texto puro; assistant renderiza
                             markdown via markstream-vue (com streaming
                             estável até `:final` virar true). -->
                        <template v-if="m.role === 'user'">{{ m.content }}</template>
                        <MarkdownRender
                            v-else-if="m.content"
                            :custom-id="`msg-${i}`"
                            :content="m.content"
                            :final="!m.streaming"
                            class="markstream-bubble"
                        />
                        <span
                            v-else-if="m.role === 'assistant' && isStreaming"
                            class="inline-block w-2 h-4 bg-zinc-400 animate-pulse rounded-sm"
                        />

                        <!-- Token usage — agregado entre todos os steps
                             do turno via stream_end events do Prism. -->
                        <div
                            v-if="m.role === 'assistant' && m.usage"
                            class="mt-3 pt-2 border-t border-zinc-100 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[10px] font-mono text-zinc-400"
                        >
                            <span title="Prompt tokens (sent to the model)">
                                ↑ {{ m.usage.prompt.toLocaleString() }}
                            </span>
                            <span title="Completion tokens (generated by the model)">
                                ↓ {{ m.usage.completion.toLocaleString() }}
                            </span>
                            <span
                                v-if="m.usage.cache_read"
                                title="Cache read tokens (re-used from prompt cache)"
                                class="text-emerald-500"
                            >
                                ⚡ {{ m.usage.cache_read.toLocaleString() }} cached
                            </span>
                            <span
                                v-if="m.usage.cache_write"
                                title="Cache write tokens (added to prompt cache)"
                                class="text-amber-500"
                            >
                                ✎ {{ m.usage.cache_write.toLocaleString() }} written
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- composer -->
            <form
                @submit.prevent="send"
                class="border-t border-zinc-200 p-4 bg-white flex items-end gap-3"
            >
                <textarea
                    v-model="input"
                    rows="1"
                    :disabled="isStreaming"
                    placeholder="Type a message…"
                    class="flex-1 resize-none rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 px-4 py-3 text-sm disabled:bg-zinc-100"
                    @keydown.enter.exact.prevent="send"
                />
                <button
                    type="submit"
                    :disabled="isStreaming || !input.trim()"
                    class="rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:bg-zinc-300 text-white px-5 py-3 text-sm font-medium transition"
                >
                    {{ isStreaming ? '…' : 'Send' }}
                </button>
            </form>
        </section>

        <!-- ─── tool inspector ──────────────────────────────────────── -->
        <ToolInspector :tool-calls="allToolCalls" />
    </div>
</template>
