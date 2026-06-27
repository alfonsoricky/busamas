<div id="confirm-dialog" class="fixed inset-0 z-50 hidden items-center justify-center bg-stone-950/40 px-4 backdrop-blur-[1px]" aria-hidden="true">
    <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
        <h2 id="confirm-dialog-title" class="text-lg font-bold text-ink">Konfirmasi Hapus</h2>
        <p id="confirm-dialog-message" class="mt-2 text-sm leading-6 text-stone-600">Apakah data ini akan dihapus?</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand" data-confirm-cancel>
                Tidak
            </button>
            <button type="button" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800" data-confirm-yes>
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        const dialog = document.getElementById('confirm-dialog');
        if (!dialog) {
            return;
        }

        const message = dialog.querySelector('#confirm-dialog-message');
        const cancelButton = dialog.querySelector('[data-confirm-cancel]');
        const yesButton = dialog.querySelector('[data-confirm-yes]');
        let pendingAction = null;

        function openConfirm(text, action) {
            message.textContent = text || 'Apakah data ini akan dihapus?';
            pendingAction = action;
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
            dialog.setAttribute('aria-hidden', 'false');
            cancelButton.focus();
        }

        function closeConfirm() {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            dialog.setAttribute('aria-hidden', 'true');
            pendingAction = null;
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm-message]');
            if (!form || form.dataset.confirmAccepted === 'true') {
                return;
            }

            event.preventDefault();
            openConfirm(form.dataset.confirmMessage, () => {
                form.dataset.confirmAccepted = 'true';
                form.submit();
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-confirm-target]');
            if (!button) {
                return;
            }

            event.preventDefault();
            const target = document.getElementById(button.dataset.confirmTarget);
            if (!target) {
                return;
            }

            openConfirm(button.dataset.confirmMessage, () => {
                target.submit();
            });
        });

        yesButton.addEventListener('click', () => {
            const action = pendingAction;
            closeConfirm();
            if (typeof action === 'function') {
                action();
            }
        });

        cancelButton.addEventListener('click', closeConfirm);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                closeConfirm();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dialog.classList.contains('hidden')) {
                closeConfirm();
            }
        });
    })();
</script>
