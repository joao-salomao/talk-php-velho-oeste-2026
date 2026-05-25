<script setup>
import { reactive } from 'vue';

defineProps({
    toolCalls: { type: Array, default: () => [] },
});

// Vue 3 reactive Set: add/delete são tracked automaticamente,
// sem precisar reassign pra disparar re-render.
const expanded = reactive(new Set());

function toggle(id) {
    if (expanded.has(id)) expanded.delete(id);
    else expanded.add(id);
}

/**
 * Stringify pra display. Aceita objeto OU JSON string — se vier string,
 * tenta parsear pra deixar a indentação consistente.
 */
function pretty(value) {
    if (value == null) return '';
    let v = value;
    if (typeof value === 'string') {
        try {
            v = JSON.parse(value);
        } catch {
            return value; // não era JSON — exibe cru
        }
    }
    try {
        return JSON.stringify(v, null, 2);
    } catch {
        return String(value);
    }
}

/**
 * Timestamp vem em segundos (Unix) no toArray() dos StreamEvents.
 * Se a magnitude for muito alta tratamos como millis (defensivo).
 */
function formatTime(ts) {
    if (!ts) return '';
    const ms = ts > 1e12 ? ts : ts * 1000;
    return new Date(ms).toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}
</script>

<template>
    <aside class="w-[28rem] flex flex-col bg-zinc-900 text-zinc-100">
        <header class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-sm font-semibold tracking-wide uppercase text-zinc-400">
                Tool Inspector
            </h2>
            <p class="text-xs text-zinc-500 mt-0.5">
                {{ toolCalls.length }} tool call{{ toolCalls.length === 1 ? '' : 's' }}
            </p>
        </header>

        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div
                v-if="toolCalls.length === 0"
                class="text-xs text-zinc-500 text-center mt-8"
            >
                No tool calls yet. Send a message to the agent.
            </div>

            <div
                v-for="(tc, i) in toolCalls"
                :key="tc.id || i"
                class="rounded-lg bg-zinc-800/60 border border-zinc-800 overflow-hidden"
            >
                <button
                    @click="toggle(tc.id || i)"
                    class="w-full px-3 py-2.5 flex items-center justify-between gap-2 hover:bg-zinc-800 transition text-left"
                >
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <span
                            :class="[
                                'w-1.5 h-1.5 rounded-full shrink-0',
                                tc.pending ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400',
                            ]"
                        />
                        <span class="font-mono text-xs text-zinc-200 truncate">
                            {{ tc.name }}
                        </span>
                        <span
                            v-if="tc.timestamp"
                            class="ml-auto font-mono text-[10px] text-zinc-500 tabular-nums shrink-0"
                            :title="`Tool called at ${formatTime(tc.timestamp)}`"
                        >
                            {{ formatTime(tc.timestamp) }}
                        </span>
                    </div>
                    <span class="text-[10px] text-zinc-500 shrink-0">
                        {{ expanded.has(tc.id || i) ? '▾' : '▸' }}
                    </span>
                </button>

                <div
                    v-if="expanded.has(tc.id || i)"
                    class="border-t border-zinc-800 divide-y divide-zinc-800"
                >
                    <div class="p-3">
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mb-1">
                            arguments
                        </div>
                        <pre class="text-[11px] font-mono text-zinc-300 whitespace-pre-wrap break-words">{{ pretty(tc.arguments) }}</pre>
                    </div>
                    <div class="p-3">
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mb-1">
                            result
                        </div>
                        <pre
                            v-if="tc.result !== null"
                            class="text-[11px] font-mono text-zinc-300 whitespace-pre-wrap break-words max-h-64 overflow-y-auto"
                        >{{ pretty(tc.result) }}</pre>
                        <div v-else class="text-[11px] text-zinc-500 italic">
                            pending…
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
