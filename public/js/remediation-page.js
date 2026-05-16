document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // Utilitaires partagés
    // =========================

    const toast = document.getElementById('rz-remediation-toast');

    function showToast(message, type = 'success') {
        if (!toast) return;
        toast.className = `alert alert-${type} mb-4`;
        toast.textContent = message;
        toast.classList.remove('d-none');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.add('d-none'), 4000);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    // =========================
    // Filtre — état collapse
    // =========================

    const FILTER_STORAGE_KEY = 'rz_filter_collapsed';
    const filterBody         = document.getElementById('rz-filter-body');
    const filterToggle       = document.querySelector('[data-bs-target="#rz-filter-body"]');

    if (filterBody && filterToggle && typeof bootstrap !== 'undefined') {
        if (sessionStorage.getItem(FILTER_STORAGE_KEY) === '1') {
            bootstrap.Collapse.getOrCreateInstance(filterBody, { toggle: false }).hide();
            filterToggle.setAttribute('aria-expanded', 'false');
        }

        filterBody.addEventListener('hide.bs.collapse', () => {
            sessionStorage.setItem(FILTER_STORAGE_KEY, '1');
        });

        filterBody.addEventListener('show.bs.collapse', () => {
            sessionStorage.removeItem(FILTER_STORAGE_KEY);
        });
    }

    // =========================
    // Drawer — initialisation
    // =========================

    const drawerElement = document.getElementById('remediationDrawer');

    if (!drawerElement || typeof bootstrap === 'undefined') {
        return;
    }

    const drawerBody    = drawerElement.querySelector('[data-remediation-drawer-body]');
    const drawerState   = drawerElement.querySelector('[data-remediation-drawer-state]');
    const drawerContext = drawerElement.querySelector('[data-remediation-drawer-context]');
    const drawerTitle   = drawerElement.querySelector('#remediationDrawerLabel');

    if (!drawerBody) {
        return;
    }

    const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerElement);

    const LOADING_HTML = `
        <div class="rz-remediation-drawer-loading">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                <div class="small text-body-secondary">Chargement de l'action…</div>
            </div>
        </div>
    `;

    // =========================
    // Drawer — événements
    // =========================

    document.addEventListener('click', async (event) => {
        const openButton = event.target.closest('[data-remediation-drawer-open]');
        if (openButton) {
            event.preventDefault();
            const url     = openButton.dataset.remediationDrawerUrl;
            const title   = openButton.dataset.remediationDrawerTitle   || 'Action de remédiation';
            const context = openButton.dataset.remediationDrawerContext || 'Édition contextuelle depuis le tableau de suivi.';
            if (url) {
                await openRemoteDrawer(url, { title, context });
            }
            return;
        }

        const deleteButton = event.target.closest('[data-remediation-delete-url]');
        if (deleteButton) {
            event.preventDefault();
            await deleteRemediation(deleteButton);
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('#remediation-action-drawer-form');
        if (!form) return;
        event.preventDefault();
        await submitRemoteForm(form);
    });

    drawerElement.addEventListener('hidden.bs.offcanvas', resetDrawerShell);

    // =========================
    // Drawer — shell
    // =========================

    function resetDrawerShell() {
        if (drawerTitle)   drawerTitle.textContent  = 'Action de remédiation';
        if (drawerContext) drawerContext.textContent = 'Édition contextuelle depuis le tableau de suivi.';
        setDrawerState('text-bg-dark', 'Chargement');
        drawerBody.innerHTML = LOADING_HTML;
    }

    function setDrawerState(cls, label) {
        if (!drawerState) return;
        drawerState.className   = `badge ${cls}`;
        drawerState.textContent = label;
    }

    // =========================
    // Drawer — ouverture
    // =========================

    async function openRemoteDrawer(url, options = {}) {
        if (drawerTitle)   drawerTitle.textContent  = options.title   || 'Action de remédiation';
        if (drawerContext) drawerContext.textContent = options.context || 'Édition contextuelle depuis le tableau de suivi.';
        setDrawerState('text-bg-dark', 'Chargement');
        drawerBody.innerHTML = LOADING_HTML;

        drawer.show();

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error('Impossible de charger le panneau.');
            }

            drawerBody.innerHTML = await response.text();
            setDrawerState('text-bg-primary', 'Édition');
            focusFirstField();
        } catch (error) {
            setDrawerState('text-bg-danger', 'Erreur');
            drawerBody.innerHTML = `
                <div class="alert alert-danger m-3 mb-0">
                    ${escapeHtml(error.message || 'Erreur de chargement.')}
                </div>
            `;
        }
    }

    // =========================
    // Drawer — soumission formulaire
    // =========================

    async function submitRemoteForm(form) {
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        setDrawerState('text-bg-warning', 'Enregistrement');

        try {
            const response = await fetch(form.action, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body:    new FormData(form),
            });

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const data = await response.json();

                if (!response.ok || !data.ok) {
                    if (data.html) {
                        drawerBody.innerHTML = data.html;
                        setDrawerState('text-bg-danger', 'À corriger');
                        focusFirstField();
                        return;
                    }
                    throw new Error(data.message || 'Erreur lors de l\'enregistrement.');
                }

                setDrawerState('text-bg-success', 'Enregistré');
                showToast(data.message || 'Action enregistrée.', 'success');
                drawer.hide();
                location.reload();
                return;
            }

            // Réponse HTML → erreurs de formulaire Symfony
            drawerBody.innerHTML = await response.text();
            setDrawerState('text-bg-danger', 'À corriger');
            focusFirstField();
        } catch (error) {
            setDrawerState('text-bg-danger', 'Erreur');
            showToast(error.message || 'Erreur lors de l\'enregistrement.', 'danger');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    }

    // =========================
    // Drawer — suppression
    // =========================

    async function deleteRemediation(button) {
        const url   = button.dataset.remediationDeleteUrl;
        const token = button.dataset.remediationDeleteToken;
        const label = button.dataset.remediationDeleteLabel || 'cette action';

        if (!url || !token) return;
        if (!confirm(`Supprimer "${label}" ?`)) return;

        try {
            const body = new FormData();
            body.append('_token', token);

            const response = await fetch(url, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Erreur lors de la suppression.');
            }

            showToast(data.message || 'Action supprimée.', 'success');
            location.reload();
        } catch (error) {
            showToast(error.message || 'Erreur lors de la suppression.', 'danger');
        }
    }

    // =========================
    // Drawer — helpers
    // =========================

    function focusFirstField() {
        const field = drawerBody.querySelector(
            'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
        );
        if (field) setTimeout(() => field.focus(), 80);
    }

    // =========================
    // Kanban board
    // =========================

    const board = document.querySelector('[data-remediation-kanban]');

    if (!board || typeof Sortable === 'undefined') {
        return;
    }

    const statusUrl = board.dataset.remediationStatusUrl;
    const csrfToken = board.dataset.remediationCsrf || '';

    const STATUS_LABELS = {
        draft:       'Brouillon',
        open:        'Ouverte',
        in_progress: 'En cours',
        done:        'Terminée',
        cancelled:   'Annulée',
    };

    initTooltips(board);
    initSortable(board);

    // =========================
    // Kanban — tooltips
    // =========================

    function initTooltips(scope) {
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    function disposeTooltips(scope) {
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getInstance(el)?.dispose();
        });
    }

    // =========================
    // Kanban — sortable
    // =========================

    function initSortable(scope) {
        scope.querySelectorAll('[data-remediation-column]').forEach((column) => {
            if (column.dataset.sortableInitialized === 'true') return;

            Sortable.create(column, {
                group:       'rezilio-remediations',
                animation:   180,
                easing:      'cubic-bezier(0.16, 1, 0.3, 1)',
                ghostClass:  'sortable-ghost',
                chosenClass: 'sortable-chosen',
                draggable:   '.rz-remediation-card',
                filter:      'button, a, .btn, [data-bs-toggle="tooltip"]',

                onStart() {
                    document.body.classList.add('rz-is-sorting');
                },

                onMove(evt) {
                    board.querySelectorAll('[data-remediation-column]').forEach((list) => {
                        list.classList.toggle('sortable-drag-over', list === evt.to);
                    });
                },

                async onEnd(evt) {
                    document.body.classList.remove('rz-is-sorting');
                    board.querySelectorAll('[data-remediation-column]').forEach((list) => {
                        list.classList.remove('sortable-drag-over');
                    });

                    const card       = evt.item;
                    const toColumn   = evt.to;
                    const fromColumn = evt.from;
                    const id         = card?.dataset.remediationCardId;
                    const newStatus  = toColumn?.dataset.remediationColumn;

                    if (!id || !newStatus) {
                        restoreCard(evt);
                        return;
                    }

                    if (fromColumn === toColumn && evt.oldIndex === evt.newIndex) {
                        return;
                    }

                    setCardBusy(card, true);

                    try {
                        await patchStatus(id, newStatus, evt.newIndex);

                        if (newStatus === 'done') {
                            card.classList.add('is-done');
                            card.querySelector('.bi-calendar-event')
                                ?.closest('.rz-remediation-meta-icon')
                                ?.classList.remove('is-overdue');
                        } else {
                            card.classList.remove('is-done');
                        }

                        syncEmptyStates(fromColumn);
                        syncEmptyStates(toColumn);
                        setTimeout(() => refreshCounters(), 0);
                        showToast(
                            `Déplacée vers « ${STATUS_LABELS[newStatus] ?? newStatus} ».`,
                            'success'
                        );
                    } catch (error) {
                        restoreCard(evt);
                        syncEmptyStates(fromColumn);
                        syncEmptyStates(toColumn);
                        setTimeout(() => refreshCounters(), 0);
                        showToast(error.message || 'Erreur lors de la mise à jour.', 'danger');
                    } finally {
                        setCardBusy(card, false);
                    }
                },
            });

            column.dataset.sortableInitialized = 'true';
        });

        board.querySelectorAll('[data-remediation-column]').forEach(syncEmptyStates);
        setTimeout(() => refreshCounters(), 0);
    }

    // =========================
    // Kanban — API
    // =========================

    async function patchStatus(id, status, position) {
        if (!statusUrl) {
            throw new Error('URL de mise à jour absente.');
        }

        const response = await fetch(statusUrl, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token':     csrfToken,
            },
            body: JSON.stringify({ id, status, position }),
        });

        let data = {};
        try { data = await response.json(); } catch (_) {}

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || 'Mise à jour impossible.');
        }

        return data;
    }

    // =========================
    // Kanban — helpers DOM
    // =========================

    function restoreCard(evt) {
        if (!evt.item || !evt.from) return;
        evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] ?? null);
    }

    function setCardBusy(card, busy) {
        card.classList.toggle('is-updating', busy);
        card.setAttribute('aria-busy', busy ? 'true' : 'false');
        card.querySelectorAll('button').forEach((btn) => {
            btn.disabled = busy;
        });
    }

    function syncEmptyStates(column) {
        if (!column) return;
        const hasCards = column.querySelector('[data-remediation-card-id]') !== null;
        const empty    = column.querySelector('.rz-kanban-empty');

        if (hasCards && empty)  { empty.remove(); return; }
        if (!hasCards && !empty) {
            const node = document.createElement('div');
            node.className   = 'rz-kanban-empty text-body-secondary small';
            node.textContent = 'Aucune action';
            column.appendChild(node);
        }
    }

    function refreshCounters() {
        board.querySelectorAll('.rz-kanban-column').forEach((col) => {
            const list  = col.querySelector('[data-remediation-column]');
            const badge = col.querySelector('.card-header .badge')
                    ?? col.querySelector('header .badge')
                    ?? col.querySelector('.badge');

            if (!list || !badge) {
                console.warn('[refreshCounters] badge ou list introuvable', col);
                return;
            }

            const count = list.querySelectorAll('[data-remediation-card-id]').length;
            badge.textContent = String(count);
        });
    }

    // Événement externe pour réinitialiser les tooltips après rerendu AJAX
    document.addEventListener('rezilio:kanban:refresh-tooltips', (e) => {
        const scope = e.detail?.scope ?? board;
        disposeTooltips(scope);
        initTooltips(scope);
    });
});
