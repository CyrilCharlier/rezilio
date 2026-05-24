(function () {
    const board = document.getElementById('rz-kanban-board');
    const toastStack = document.getElementById('rz-toast-stack');
    const modalElement = document.getElementById('measureReviewModal');
    const modalBody = document.getElementById('measureReviewModalBody');
    const pageRoot = document.getElementById('rz-page-content');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

    if (!board || !toastStack || !modalElement || !modalBody || !modal) {
        return;
    }

    let draggedCard = null;
    let sourceCardsContainer = null;
    let sourceColumnBody = null;
    let lastTriggerCard = null;

    function showToast(message, type = 'success', duration = 2500) {
        const toast = document.createElement('div');
        toast.className = `rz-toast rz-toast--${type}`;
        toast.textContent = message;
        toastStack.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, duration);
    }

    function updateColumnState(columnBody) {
        if (!columnBody) {
            return;
        }

        const cardsContainer = columnBody.querySelector('.rz-kanban-cards');
        const emptyState = columnBody.querySelector('[data-empty-state]');
        const countBadge = columnBody.closest('.rz-kanban-column')?.querySelector('[data-column-count]');
        const count = cardsContainer ? cardsContainer.children.length : 0;

        if (countBadge) {
            countBadge.textContent = String(count);
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', count > 0);
        }
    }

    function findDropzoneByStatus(statusValue) {
        return board.querySelector(`[data-dropzone][data-status-value="${statusValue}"]`);
    }

    function moveCardToStatus(card, targetStatus) {
        const currentDropzone = card.closest('[data-dropzone]');
        const targetDropzone = findDropzoneByStatus(targetStatus);

        if (!targetDropzone) {
            return;
        }

        const targetContainer = targetDropzone.querySelector('.rz-kanban-cards');

        if (!targetContainer) {
            return;
        }

        targetContainer.prepend(card);
        updateColumnState(currentDropzone);
        updateColumnState(targetDropzone);
        card.dataset.currentStatus = targetStatus;
    }

    function updateCardFromModalPayload(reviewId, payload) {
        const card = board.querySelector(`.rz-kanban-card[data-review-id="${reviewId}"]`);

        if (!card) {
            return;
        }

        if (payload.status && payload.status !== card.dataset.currentStatus) {
            moveCardToStatus(card, payload.status);
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'explanation')) {
            let explanationNode = card.querySelector('.rz-kanban-card-text');

            if (payload.explanation && payload.explanation.trim() !== '') {
                if (!explanationNode) {
                    explanationNode = document.createElement('p');
                    explanationNode.className = 'rz-kanban-card-text rz-clamp-2';
                    card.appendChild(explanationNode);
                }

                explanationNode.textContent = payload.explanation;
            } else if (explanationNode) {
                explanationNode.remove();
            }
        }
    }

    function syncConditionalFields(scope) {
        const statusField = scope.querySelector('[data-review-status="true"]');
        const notApplicableWrapper = scope.querySelector('[data-not-applicable-wrapper]');

        if (!statusField || !notApplicableWrapper) {
            return;
        }

        const shouldShowNotApplicable = ['BLOCKED', 'NOT_APPLICABLE'].includes(statusField.value);

        notApplicableWrapper.classList.toggle('is-hidden', !shouldShowNotApplicable);

        const textarea = notApplicableWrapper.querySelector('textarea');
        if (textarea) {
            textarea.disabled = !shouldShowNotApplicable;
        }
    }

    function initTooltips(container = document) {
        container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el, {
                container: 'body'
            });
        });
    }

    function initModalDynamicContent(scope = modalBody) {
        if (!scope) {
            return;
        }

        syncConditionalFields(scope);
        initTooltips(scope);
    }

    function getEvidenceFrame() {
        return modalBody?.querySelector('#measure-review-evidence-frame') ?? null;
    }

    function reloadEvidenceFrame() {
        const frame = getEvidenceFrame();
        if (!frame) {
            return;
        }

        const src = frame.getAttribute('src');
        if (!src) {
            return;
        }

        frame.setAttribute('src', src);
    }

    function isInsideEvidenceFrame(element) {
        if (!element) {
            return false;
        }

        return !!element.closest('#measure-review-evidence-frame');
    }

    function ensureBusyOverlay(container) {
        let overlay = container.querySelector(':scope > .rz-busy-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'rz-busy-overlay';
            overlay.innerHTML = `
                <div class="rz-busy-overlay__spinner" aria-hidden="true"></div>
                <div class="rz-busy-overlay__label">Chargement…</div>
            `;
            container.appendChild(overlay);
        }

        return overlay;
    }

    function setBusyState(container, isBusy, options = {}) {
        if (!container) {
            return;
        }

        const {
            message = 'Chargement…',
            disableForms = true
        } = options;

        container.classList.toggle('is-busy', isBusy);
        container.setAttribute('aria-busy', isBusy ? 'true' : 'false');

        const overlay = ensureBusyOverlay(container);
        const label = overlay.querySelector('.rz-busy-overlay__label');

        if (label) {
            label.textContent = message;
        }

        overlay.hidden = !isBusy;

        if (disableForms) {
            container
                .querySelectorAll('button, input, select, textarea, a')
                .forEach((el) => {
                    if (isBusy) {
                        el.dataset.wasDisabled = el.disabled ? 'true' : 'false';

                        if (
                            el.tagName === 'BUTTON' ||
                            el.tagName === 'INPUT' ||
                            el.tagName === 'SELECT' ||
                            el.tagName === 'TEXTAREA'
                        ) {
                            el.disabled = true;
                        }

                        if (el.tagName === 'A') {
                            el.setAttribute('aria-disabled', 'true');
                            el.classList.add('is-disabled');
                        }
                    } else {
                        if (
                            el.tagName === 'BUTTON' ||
                            el.tagName === 'INPUT' ||
                            el.tagName === 'SELECT' ||
                            el.tagName === 'TEXTAREA'
                        ) {
                            if (el.dataset.wasDisabled !== 'true') {
                                el.disabled = false;
                            }
                        }

                        if (el.tagName === 'A') {
                            el.removeAttribute('aria-disabled');
                            el.classList.remove('is-disabled');
                        }

                        delete el.dataset.wasDisabled;
                    }
                });
        }
    }

    async function withBusyState(container, asyncCallback, options = {}) {
        if (container?.dataset.requestPending === 'true') {
            return null;
        }

        if (container) {
            container.dataset.requestPending = 'true';
            setBusyState(container, true, options);
        }

        try {
            return await asyncCallback();
        } finally {
            if (container) {
                container.dataset.requestPending = 'false';
                setBusyState(container, false, options);
            }
        }
    }

    async function persistMove(card, newStatus) {
        const formData = new FormData();
        formData.append('status', newStatus);
        formData.append('_token', card.dataset.csrfToken);

        const response = await fetch(card.dataset.moveUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'La mise à jour a échoué.');
        }

        card.dataset.currentStatus = data.status;
        showToast(data.message || 'Statut mis à jour.', 'success', 2400);
    }

    async function openReviewModal(card) {
        lastTriggerCard = card;
        modalBody.innerHTML = '<div class="rz-modal-loading">Chargement…</div>';
        initModalDynamicContent(modalBody);

        if (pageRoot) {
            pageRoot.setAttribute('inert', '');
        }

        modal.show();
        reloadEvidenceFrame();

        await withBusyState(modalBody, async () => {
            const response = await fetch(card.dataset.modalUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">Impossible de charger la revue.</div>';
                return;
            }

            modalBody.innerHTML = await response.text();
            syncConditionalFields(modalBody);
            initTooltips(modalBody);
        }, {
            message: 'Chargement de la revue…'
        });
    }

    async function submitModalForm(form) {
        const submitButton = form.querySelector('[type="submit"]');
        submitButton?.classList.add('is-loading');

        try {
            await withBusyState(form, async () => {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.status === 422 && data.html) {
                    modalBody.innerHTML = data.html;
                    syncConditionalFields(modalBody);
                    showToast(data.message || 'Merci de corriger les erreurs.', 'error', 3200);
                    initTooltips(modalBody);
                    return;
                }

                if (!response.ok || !data.ok) {
                    showToast(data.message || 'La mise à jour a échoué.', 'error', 4200);
                    return;
                }

                updateCardFromModalPayload(data.reviewId, data);
                modal.hide();
                showToast(data.message || 'La revue a été mise à jour.', 'success', 2500);
            }, {
                message: 'Enregistrement…'
            });
        } finally {
            submitButton?.classList.remove('is-loading');
        }
    }

    async function loadRemediationPanel(url) {
        const panel = modalBody.querySelector('[data-remediation-panel]');

        if (!panel) {
            return;
        }

        if (panel.dataset.loading === 'true') {
            return;
        }

        panel.dataset.loading = 'true';
        panel.classList.add('rz-panel-busy');
        panel.innerHTML = '<div class="rz-modal-loading">Chargement…</div>';

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                panel.innerHTML = '<div class="alert alert-danger mb-0">Impossible de charger les remédiations.</div>';
                return;
            }

            panel.innerHTML = await response.text();
            initTooltips(modalBody);
        } catch (error) {
            panel.innerHTML = '<div class="alert alert-danger mb-0">Erreur lors du chargement des remédiations.</div>';
        } finally {
            panel.dataset.loading = 'false';
            panel.classList.remove('rz-panel-busy');
        }
    }

    async function submitRemediationForm(form) {
        const panel = modalBody.querySelector('[data-remediation-panel]');

        if (!panel) {
            return;
        }

        if (form.dataset.submitting === 'true') {
            // On évite les doubles clics pendant une requête
            return;
        }

        form.dataset.submitting = 'true';

        const submitButton = form.querySelector('[type="submit"]');
        submitButton?.classList.add('is-loading');
        submitButton?.setAttribute('disabled', 'disabled');
        panel.classList.add('rz-panel-busy');

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.status === 422 && data.html) {
                panel.innerHTML = data.html;
                showToast(data.message || 'Merci de corriger les erreurs.', 'error', 3200);
                initTooltips(modalBody);
                return;
            }

            if (!response.ok || !data.ok) {
                showToast(data.message || 'Impossible d’enregistrer l’action.', 'error', 4200);
                return;
            }

            panel.innerHTML = data.html;
            showToast(data.message || 'Action enregistrée.', 'success', 2400);
            initTooltips(modalBody);
        } catch (error) {
            showToast('Une erreur est survenue lors de l’enregistrement.', 'error', 4200);
        } finally {
            form.dataset.submitting = 'false';
            panel.classList.remove('rz-panel-busy');
            submitButton?.classList.remove('is-loading');
            submitButton?.removeAttribute('disabled');
        }
    }

    async function deleteRemediation(form) {
        const panel = modalBody.querySelector('[data-remediation-panel]');

        if (!panel) {
            return;
        }

        await withBusyState(panel, async () => {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                showToast(data.message || 'Impossible de supprimer l’action.', 'error', 4200);
                return;
            }

            panel.innerHTML = data.html;
            showToast(data.message || 'Action supprimée.', 'success', 2400);
            initTooltips(modalBody);
        }, {
            message: 'Suppression…'
        });
    }

    modalElement.addEventListener('hidden.bs.modal', () => {
        modalBody.innerHTML = '<div class="rz-modal-loading">Chargement…</div>';

        if (pageRoot) {
            pageRoot.removeAttribute('inert');
        }

        if (lastTriggerCard) {
            lastTriggerCard.focus();
        }
    });

    modalBody.addEventListener('change', (event) => {
        if (event.target.matches('[data-review-status="true"]')) {
            syncConditionalFields(modalBody);
        }
    });

    modalBody.addEventListener('click', (event) => {
        const createButton = event.target.closest('[data-remediation-create]');
        if (createButton) {
            event.preventDefault();
            loadRemediationPanel(createButton.dataset.remediationCreateUrl).catch(() => {
                showToast('Impossible de charger le formulaire.', 'error', 4200);
            });
            return;
        }

        const editButton = event.target.closest('[data-remediation-edit]');
        if (editButton) {
            event.preventDefault();
            loadRemediationPanel(editButton.dataset.remediationEditUrl).catch(() => {
                showToast('Impossible de charger le formulaire.', 'error', 4200);
            });
            return;
        }

        const backButton = event.target.closest('[data-remediation-back]');
        if (backButton) {
            event.preventDefault();
            loadRemediationPanel(backButton.dataset.remediationListUrl).catch(() => {
                showToast('Impossible de charger la liste.', 'error', 4200);
            });
            return;
        }
    });

    board.querySelectorAll('.rz-kanban-card').forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, textarea, label')) {
                return;
            }

            if (card.classList.contains('is-dragging')) {
                return;
            }

            openReviewModal(card).catch(() => {
                showToast('Impossible de charger la revue.', 'error', 4200);
            });
        });

        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openReviewModal(card).catch(() => {
                    showToast('Impossible de charger la revue.', 'error', 4200);
                });
            }
        });

        card.addEventListener('dragstart', (event) => {
            draggedCard = card;
            sourceCardsContainer = card.parentElement;
            sourceColumnBody = card.closest('[data-dropzone]');
            card.classList.add('is-dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.reviewId || '');
            }
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('is-dragging');

            board.querySelectorAll('[data-dropzone]').forEach((zone) => {
                zone.classList.remove('is-drop-target');
            });

            draggedCard = null;
            sourceCardsContainer = null;
            sourceColumnBody = null;
        });
    });

    board.querySelectorAll('[data-dropzone]').forEach((dropzone) => {
        dropzone.addEventListener('dragenter', (event) => {
            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            dropzone.classList.add('is-drop-target');
        });

        dropzone.addEventListener('dragover', (event) => {
            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            dropzone.classList.add('is-drop-target');
        });

        dropzone.addEventListener('dragleave', (event) => {
            if (!dropzone.contains(event.relatedTarget)) {
                dropzone.classList.remove('is-drop-target');
            }
        });

        dropzone.addEventListener('drop', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            dropzone.classList.remove('is-drop-target');

            if (!draggedCard) {
                return;
            }

            const targetStatus = dropzone.dataset.statusValue;
            const previousStatus = draggedCard.dataset.currentStatus;

            if (!targetStatus || targetStatus === previousStatus) {
                return;
            }

            const targetCardsContainer = dropzone.querySelector('.rz-kanban-cards');

            if (!targetCardsContainer) {
                return;
            }

            const previousContainer = sourceCardsContainer;
            const previousColumn = sourceColumnBody;

            targetCardsContainer.prepend(draggedCard);
            updateColumnState(previousColumn);
            updateColumnState(dropzone);

            try {
                await persistMove(draggedCard, targetStatus);
            } catch (error) {
                if (previousContainer) {
                    previousContainer.prepend(draggedCard);
                }

                updateColumnState(previousColumn);
                updateColumnState(dropzone);
                draggedCard.dataset.currentStatus = previousStatus;
                showToast(error.message || 'La mise à jour a échoué.', 'error', 4200);
            }
        });
    });

    modalBody.addEventListener('submit', (event) => {
        const reviewForm = event.target.closest('form[data-modal-form="true"]');
        if (reviewForm) {
            event.preventDefault();

            submitModalForm(reviewForm).catch(() => {
                showToast('Impossible d’enregistrer la revue.', 'error', 4200);
            });

            return;
        }

        const remediationForm = event.target.closest('form[data-remediation-form="true"]');
        if (remediationForm) {
            event.preventDefault();

            submitRemediationForm(remediationForm).catch(() => {
                showToast('Impossible d’enregistrer l’action.', 'error', 4200);
            });

            return;
        }

        const deleteForm = event.target.closest('form[data-remediation-delete-form="true"]');
        if (deleteForm) {
            event.preventDefault();

            if (!window.confirm('Supprimer cette action de remédiation ?')) {
                return;
            }

            deleteRemediation(deleteForm).catch(() => {
                showToast('Impossible de supprimer l’action.', 'error', 4200);
            });
        }
    });

    document.addEventListener('turbo:submit-end', function (event) {
        const form = event.target;

        if (!isInsideEvidenceFrame(form)) {
            return;
        }

        if (event.detail.success) {
            showToast('Preuve enregistrée.', 'success', 2200);
        } else {
            showToast('Erreur lors de l’enregistrement de la preuve.', 'error', 3200);
        }
    });

    document.addEventListener('turbo:before-fetch-request', function (event) {
        if (event.target.id === 'measure-review-evidence-frame') {
            const frame = event.target;
            frame.classList.add('is-loading');
        }
    });

    document.addEventListener('turbo:frame-load', function (event) {
        if (event.target.id !== 'measure-review-evidence-frame') {
            return;
        }

        event.target.classList.remove('is-loading');
        initModalDynamicContent(event.target);
    });
})();
