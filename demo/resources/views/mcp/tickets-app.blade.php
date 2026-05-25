<x-mcp::app :title="$title">
    <x-slot:head>
        <style>
            :root {
                color-scheme: light dark;
                --bg: #fafafa;
                --card: #ffffff;
                --border: rgba(0, 0, 0, 0.08);
                --text: #18181b;
                --muted: #71717a;
                --hover: rgba(0, 0, 0, 0.025);
            }
            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0a0a0a;
                    --card: #18181b;
                    --border: rgba(255, 255, 255, 0.08);
                    --text: #fafafa;
                    --muted: #a1a1aa;
                    --hover: rgba(255, 255, 255, 0.03);
                }
            }
            * { box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif;
                margin: 0;
                padding: 20px;
                background: var(--bg);
                color: var(--text);
                font-size: 14px;
                line-height: 1.4;
                /* Inline (autoResize reporta scrollWidth): sem isto a grid
                   colapsa pra 1 coluna ~280px. Um min força várias colunas. */
                min-width: 640px;
            }
            /* Fullscreen: o host dá o viewport inteiro — preenchemos tudo
               e deixamos a grid rolar internamente. */
            body.fullscreen {
                min-width: 0;
                height: 100vh;
                display: flex;
                flex-direction: column;
            }
            body.fullscreen .grid {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                align-content: start;
            }
            .header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                letter-spacing: -0.01em;
            }
            .filters {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
            }
            .chip {
                font: inherit;
                font-size: 12px;
                padding: 5px 10px;
                border-radius: 999px;
                border: 1px solid var(--border);
                background: var(--card);
                color: var(--text);
                cursor: pointer;
                transition: background 120ms ease, border-color 120ms ease;
            }
            .chip:hover { background: var(--hover); }
            .chip[aria-pressed="true"] {
                background: var(--text);
                color: var(--bg);
                border-color: var(--text);
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 12px;
            }
            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 14px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                transition: border-color 120ms ease, transform 120ms ease;
            }
            .card:hover { border-color: rgba(127, 127, 127, 0.35); }
            .card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }
            .ticket-id {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 11px;
                color: var(--muted);
            }
            .subject {
                font-weight: 600;
                font-size: 14px;
                line-height: 1.35;
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .description {
                color: var(--muted);
                font-size: 12.5px;
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                font-size: 12px;
                color: var(--muted);
                border-top: 1px solid var(--border);
                padding-top: 10px;
            }
            .customer {
                display: flex;
                align-items: center;
                gap: 8px;
                min-width: 0;
            }
            .avatar {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6366f1, #ec4899);
                color: #fff;
                font-size: 10px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .customer-name {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .badges {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .status-select {
                font: inherit;
                font-size: 11px;
                font-weight: 500;
                line-height: 1;
                padding: 3px 22px 3px 8px;
                border-radius: 6px;
                border: 0;
                appearance: none;
                -webkit-appearance: none;
                cursor: pointer;
                background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath d='M2 4l3 3 3-3' stroke='currentColor' stroke-width='1.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 6px center;
                transition: opacity 120ms ease;
            }
            .status-select:focus-visible {
                outline: 2px solid currentColor;
                outline-offset: 1px;
            }
            .status-select[data-saving] { opacity: 0.5; cursor: progress; }
            .status-select option { color: var(--text); background: var(--card); }

            .status-open              { background-color: rgba(59, 130, 246, 0.12); color: #2563eb; }
            .status-in_progress       { background-color: rgba(245, 158, 11, 0.15); color: #b45309; }
            .status-waiting_customer  { background-color: rgba(139, 92, 246, 0.14); color: #6d28d9; }
            .status-resolved          { background-color: rgba(34, 197, 94, 0.14);  color: #15803d; }

            .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
            .priority-low     { background: #94a3b8; }
            .priority-normal  { background: #38bdf8; }
            .priority-high    { background: #f59e0b; }
            .priority-urgent  { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }

            .empty, .loading {
                padding: 40px 20px;
                text-align: center;
                color: var(--muted);
                border: 1px dashed var(--border);
                border-radius: 12px;
            }
            .footer {
                margin-top: 14px;
                font-size: 12px;
                color: var(--muted);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .footer button {
                font: inherit;
                font-size: 12px;
                padding: 5px 10px;
                border-radius: 6px;
                border: 1px solid var(--border);
                background: var(--card);
                color: var(--text);
                cursor: pointer;
            }
            .footer button:hover { background: var(--hover); }
        </style>

        <script type="module">
            createMcpApp(async (app) => {
                // Inline: mantém o iframe do tamanho do conteúdo.
                app.autoResize();

                // Pede tela cheia pro host (no inspector é o botão ⤢) — assim
                // o board preenche o painel inteiro em vez de uma coluna.
                app.requestDisplayMode('fullscreen');

                // Reage ao modo: aplica/remove o layout de tela cheia.
                app.onHostContextChanged((ctx) => {
                    document.body.classList.toggle(
                        'fullscreen',
                        ctx.displayMode === 'fullscreen',
                    );
                });

                const grid = document.getElementById('grid');
                const count = document.getElementById('count');
                const refreshBtn = document.getElementById('refresh');
                const filters = document.querySelectorAll('.chip');

                let activeStatus = 'open';

                const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                })[c]);

                const STATUSES = [
                    { value: 'open',             label: 'Open' },
                    { value: 'in_progress',      label: 'In Progress' },
                    { value: 'waiting_customer', label: 'Waiting' },
                    { value: 'resolved',         label: 'Resolved' },
                ];
                const labelStatus = (s) => STATUSES.find((x) => x.value === s)?.label ?? s;

                const initials = (name) => (name ?? '?')
                    .split(/\s+/).filter(Boolean).slice(0, 2)
                    .map((p) => p[0].toUpperCase()).join('');

                const timeAgo = (iso) => {
                    if (!iso) return '';
                    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
                    if (diff < 60) return 'just now';
                    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
                    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
                    return `${Math.floor(diff / 86400)}d ago`;
                };

                const renderCard = (t) => `
                    <article class="card" data-id="${t.id}">
                        <div class="card-top">
                            <span class="ticket-id">#${t.id}</span>
                            <div class="badges">
                                <span class="dot priority-${escapeHtml(t.priority)}" title="${escapeHtml(t.priority)} priority"></span>
                                <select
                                    class="status-select status-${escapeHtml(t.status)}"
                                    data-id="${t.id}"
                                    data-prev="${escapeHtml(t.status)}"
                                    aria-label="Change status of ticket #${t.id}">
                                    ${STATUSES.map((s) => `
                                        <option value="${s.value}" ${s.value === t.status ? 'selected' : ''}>${s.label}</option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                        <h3 class="subject">${escapeHtml(t.subject)}</h3>
                        ${t.description ? `<p class="description">${escapeHtml(t.description)}</p>` : ''}
                        <div class="meta">
                            <div class="customer">
                                <span class="avatar">${escapeHtml(initials(t.customer?.name))}</span>
                                <span class="customer-name">${escapeHtml(t.customer?.name ?? 'Unknown')}</span>
                            </div>
                            <span>${escapeHtml(timeAgo(t.updated_at))}</span>
                        </div>
                    </article>
                `;

                const render = (tickets) => {
                    if (!Array.isArray(tickets) || tickets.length === 0) {
                        grid.innerHTML = '<div class="empty">No tickets match the current filter.</div>';
                        count.textContent = '0 tickets';
                        return;
                    }
                    grid.innerHTML = tickets.map(renderCard).join('');
                    count.textContent = `${tickets.length} ticket${tickets.length === 1 ? '' : 's'}`;
                };

                const parse = (result) => {
                    const text = result?.content?.find?.((c) => c.type === 'text')?.text;
                    try { return JSON.parse(text ?? '[]'); }
                    catch { return []; }
                };

                // SDK do Laravel MCP expõe o hook como função (não property —
                // `app.ontoolresult = ...` não dispara). Se o initial result
                // já é só "open", renderiza direto; senão força refresh
                // pro filtro default.
                app.onToolResult((result) => {
                    render(parse(result));
                });

                const refresh = async () => {
                    grid.innerHTML = '<div class="loading">Loading…</div>';
                    const result = await app.callServerTool('show-tickets', activeStatus ? { status: activeStatus } : {});
                    render(parse(result));
                };

                filters.forEach((chip) => {
                    chip.addEventListener('click', () => {
                        const next = chip.dataset.status || null;
                        activeStatus = (activeStatus === next) ? null : next;
                        filters.forEach((c) => c.setAttribute(
                            'aria-pressed', String((c.dataset.status || null) === activeStatus),
                        ));
                        refresh();
                    });
                });

                refreshBtn.addEventListener('click', refresh);

                // Status edit — delegação no grid pra pegar cards re-renderizados.
                // Otimista: troca a classe (e a cor) na hora, faz rollback se a tool falhar.
                grid.addEventListener('change', async (e) => {
                    const select = e.target;
                    if (!(select instanceof HTMLSelectElement) || !select.classList.contains('status-select')) return;

                    const id = Number(select.dataset.id);
                    const prev = select.dataset.prev;
                    const next = select.value;
                    if (next === prev) return;

                    select.classList.replace(`status-${prev}`, `status-${next}`);
                    select.dataset.saving = '1';
                    select.disabled = true;

                    try {
                        const result = await app.callServerTool('update_ticket_status', {
                            ticket_id: id,
                            status: next,
                        });
                        if (result?.isError) throw new Error('tool returned an error');

                        select.dataset.prev = next;

                        // Sem refresh: se há filtro ativo e o novo status não bate,
                        // tira o card do DOM e atualiza o contador. Caso contrário,
                        // o card fica visível com o novo badge já aplicado.
                        if (activeStatus && next !== activeStatus) {
                            const card = select.closest('.card');
                            card?.remove();

                            const remaining = grid.querySelectorAll('.card').length;
                            if (remaining === 0) {
                                grid.innerHTML = '<div class="empty">No tickets match the current filter.</div>';
                                count.textContent = '0 tickets';
                            } else {
                                count.textContent = `${remaining} ticket${remaining === 1 ? '' : 's'}`;
                            }
                        }
                    } catch (err) {
                        select.classList.replace(`status-${next}`, `status-${prev}`);
                        select.value = prev;
                    } finally {
                        delete select.dataset.saving;
                        select.disabled = false;
                    }
                });
            });
        </script>
    </x-slot:head>

    <div class="header">
        <h1>Tickets</h1>
        <div class="filters" role="group" aria-label="Status filter">
            <button class="chip" data-status="open"             aria-pressed="true"  type="button">Open</button>
            <button class="chip" data-status="in_progress"      aria-pressed="false" type="button">In Progress</button>
            <button class="chip" data-status="waiting_customer" aria-pressed="false" type="button">Waiting</button>
            <button class="chip" data-status="resolved"         aria-pressed="false" type="button">Resolved</button>
        </div>
    </div>

    <div id="grid" class="grid">
        <div class="loading">Loading tickets…</div>
    </div>

    <div class="footer">
        <span id="count">—</span>
        <button id="refresh" type="button">Refresh</button>
    </div>
</x-mcp::app>
