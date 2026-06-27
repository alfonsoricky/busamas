<script>
    document.querySelectorAll('[data-simple-datatable]').forEach((table) => {
        const tbody = table.tBodies[0];
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);
        const rows = Array.from(tbody?.querySelectorAll('[data-dt-row]') || []);
        const unit = table.dataset.dtUnit || 'data';
        const emptyText = table.dataset.dtEmpty || 'Tidak ada data yang cocok.';
        const state = {
            search: '',
            perPage: 25,
            page: 1,
            sortIndex: -1,
            sortDir: 'asc',
        };

        const toolbar = document.createElement('div');
        toolbar.className = 'border-b border-stone-100 bg-white p-4';
        toolbar.innerHTML = `
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                    <span>Tampilkan</span>
                    <select class="rounded-md border border-stone-300 bg-white px-2 py-1.5 text-sm outline-none focus:border-brand" data-dt-per-page>
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Semua</option>
                    </select>
                    <span>${unit}</span>
                </div>
                <label class="flex w-full items-center gap-2 text-sm text-stone-600 lg:w-96">
                    <span>Cari</span>
                    <input type="search" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20" placeholder="Ketik untuk mencari..." data-dt-search>
                </label>
            </div>
        `;

        const footer = document.createElement('div');
        footer.className = 'flex flex-col gap-3 border-t border-stone-100 bg-white p-4 text-sm text-stone-600 sm:flex-row sm:items-center sm:justify-between';
        footer.innerHTML = `
            <p data-dt-info></p>
            <div class="flex flex-wrap items-center gap-2" data-dt-pagination></div>
        `;

        table.closest('.overflow-hidden')?.insertBefore(toolbar, table.closest('.overflow-x-auto'));
        table.closest('.overflow-hidden')?.appendChild(footer);

        const perPageInput = toolbar.querySelector('[data-dt-per-page]');
        const searchInput = toolbar.querySelector('[data-dt-search]');
        const info = footer.querySelector('[data-dt-info]');
        const pagination = footer.querySelector('[data-dt-pagination]');

        function cleanNumber(value) {
            const normalized = String(value || '').replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
            const number = Number.parseFloat(normalized);
            return Number.isFinite(number) ? number : 0;
        }

        function cleanDate(value) {
            const text = String(value || '').trim();
            const match = text.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
            if (match) {
                return new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1])).getTime();
            }
            const timestamp = Date.parse(text);
            return Number.isFinite(timestamp) ? timestamp : 0;
        }

        function rowText(row) {
            return Array.from(row.cells).map((cell) => cell.textContent.trim()).join(' ').toLowerCase();
        }

        function filteredRows() {
            let filtered = rows.filter((row) => rowText(row).includes(state.search));

            if (state.sortIndex >= 0) {
                const sortType = headers[state.sortIndex]?.dataset.sortType || 'text';
                if (sortType !== 'none') {
                    filtered = [...filtered].sort((left, right) => {
                        const leftText = left.cells[state.sortIndex]?.textContent.trim() || '';
                        const rightText = right.cells[state.sortIndex]?.textContent.trim() || '';
                        let result;
                        if (sortType === 'number') {
                            result = cleanNumber(leftText) - cleanNumber(rightText);
                        } else if (sortType === 'date') {
                            result = cleanDate(leftText) - cleanDate(rightText);
                        } else {
                            result = leftText.localeCompare(rightText, 'id', { numeric: true, sensitivity: 'base' });
                        }
                        return state.sortDir === 'asc' ? result : -result;
                    });
                }
            }

            return filtered;
        }

        function visibleRows(filtered) {
            if (state.perPage === 'all') {
                return filtered;
            }
            const start = (state.page - 1) * state.perPage;
            return filtered.slice(start, start + state.perPage);
        }

        function button(label, disabled, active, onClick) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = label;
            btn.disabled = disabled;
            btn.className = [
                'min-w-9 rounded-md border px-3 py-1.5 text-sm font-semibold transition',
                active ? 'border-brand bg-brand text-white' : 'border-stone-300 bg-white text-ink hover:border-brand hover:text-brand',
                disabled ? 'cursor-not-allowed opacity-50 hover:border-stone-300 hover:text-ink' : '',
            ].join(' ');
            btn.addEventListener('click', onClick);
            return btn;
        }

        function renderHeaders() {
            headers.forEach((header, index) => {
                const sortType = header.dataset.sortType || 'text';
                const base = header.dataset.label || header.textContent.replace(/[▲▼⇅]/g, '').trim();
                header.dataset.label = base;

                if (sortType === 'none') {
                    header.textContent = base;
                    return;
                }

                header.classList.add('cursor-pointer', 'select-none');
                const icon = state.sortIndex === index ? (state.sortDir === 'asc' ? ' ▲' : ' ▼') : ' ⇅';
                header.textContent = base + icon;
            });
        }

        function render() {
            const filtered = filteredRows();
            const totalPages = state.perPage === 'all' ? 1 : Math.max(1, Math.ceil(filtered.length / state.perPage));
            state.page = Math.min(state.page, totalPages);

            tbody.replaceChildren(...visibleRows(filtered));

            if (filtered.length === 0) {
                const emptyRow = tbody.insertRow();
                const cell = emptyRow.insertCell();
                cell.colSpan = headers.length;
                cell.className = 'px-4 py-8 text-center text-sm text-stone-500';
                cell.textContent = emptyText;
            }

            renderHeaders();

            const start = filtered.length === 0 ? 0 : (state.perPage === 'all' ? 1 : ((state.page - 1) * state.perPage) + 1);
            const end = state.perPage === 'all' ? filtered.length : Math.min(state.page * state.perPage, filtered.length);
            info.textContent = `Menampilkan ${start}-${end} dari ${filtered.length} ${unit}`;

            pagination.replaceChildren();
            pagination.appendChild(button('Prev', state.page <= 1, false, () => {
                state.page -= 1;
                render();
            }));

            const totalButtons = state.perPage === 'all' ? 1 : totalPages;
            for (let page = 1; page <= totalButtons; page += 1) {
                if (totalButtons > 7 && page !== 1 && page !== totalButtons && Math.abs(page - state.page) > 1) {
                    if (page === 2 || page === totalButtons - 1) {
                        const dots = document.createElement('span');
                        dots.className = 'px-1 text-stone-400';
                        dots.textContent = '...';
                        pagination.appendChild(dots);
                    }
                    continue;
                }

                pagination.appendChild(button(String(page), false, page === state.page, () => {
                    state.page = page;
                    render();
                }));
            }

            pagination.appendChild(button('Next', state.page >= totalPages, false, () => {
                state.page += 1;
                render();
            }));
        }

        perPageInput.addEventListener('change', () => {
            state.perPage = perPageInput.value === 'all' ? 'all' : Number.parseInt(perPageInput.value, 10);
            state.page = 1;
            render();
        });

        searchInput.addEventListener('input', () => {
            state.search = searchInput.value.trim().toLowerCase();
            state.page = 1;
            render();
        });

        headers.forEach((header, index) => {
            if ((header.dataset.sortType || 'text') === 'none') {
                return;
            }
            header.addEventListener('click', () => {
                if (state.sortIndex === index) {
                    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortIndex = index;
                    state.sortDir = 'asc';
                }
                render();
            });
        });

        render();
    });
</script>
