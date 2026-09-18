import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.css';

document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('global-loader');
    const swal = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4200,
        timerProgressBar: true,
        background: '#1b2844',
        color: '#edf1ff',
        customClass: { popup: 'send-swal-popup' },
    });

    const showLoader = (title = 'Chargement en cours', message = 'Préparation de votre espace…') => {
        if (!loader) return;
        loader.querySelector('[data-loader-title]').textContent = title;
        loader.querySelector('[data-loader-message]').textContent = message;
        loader.classList.add('is-visible');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-loading');
    };

    const hideLoader = () => {
        if (!loader) return;
        loader.classList.remove('is-visible');
        loader.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-loading');
    };

    window.SEND = { showLoader, hideLoader, swal };

    document.querySelectorAll('.flash').forEach((flash) => {
        const type = flash.classList.contains('success') ? 'success' : flash.classList.contains('danger') ? 'error' : 'info';
        const title = type === 'success' ? 'Opération réussie' : type === 'error' ? 'Action impossible' : 'Information';
        swal.fire({ icon: type, title, text: flash.querySelector('span')?.textContent.trim() || '' });
        flash.remove();
    });

    document.querySelectorAll('.inline-error').forEach((error) => {
        swal.fire({ icon: 'error', title: 'Vérifiez votre saisie', text: error.textContent.trim() });
        error.remove();
    });

    document.querySelectorAll('[data-dismiss]').forEach((button) => button.addEventListener('click', () => button.closest('.flash')?.remove()));

    document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
        if (event.defaultPrevented || form.dataset.noLoader === 'true') return;
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            submitButton.dataset.originalContent = submitButton.innerHTML || submitButton.value;
            submitButton.disabled = true;
            if (submitButton.tagName === 'INPUT') submitButton.value = 'Chargement…';
            else submitButton.innerHTML = '<span class="button-spinner"></span> Traitement…';
            submitButton.classList.add('is-submitting');
        }
        showLoader(form.dataset.loadingTitle || 'Traitement en cours', form.dataset.loadingMessage || 'Votre demande est en cours de traitement…');
    }));

    const fileInput = document.querySelector('[data-file-input]');
    const fileDrop = document.querySelector('[data-file-drop]');
    const fileName = document.querySelector('[data-file-name]');
    const fileStatus = document.querySelector('[data-file-status]');
    const fileValidation = document.querySelector('[data-file-validation]');
    if (fileInput && fileDrop && fileName && fileStatus && fileValidation) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            fileDrop.classList.remove('has-file', 'is-invalid');
            fileValidation.textContent = '';

            if (!file) {
                fileName.textContent = 'Choisir un fichier CSV';
                fileStatus.textContent = 'CSV ou TXT · 5 Mo maximum';
                return;
            }

            const isAllowedExtension = /\.(csv|txt)$/i.test(file.name);
            const isAllowedSize = file.size <= 5 * 1024 * 1024;
            if (!isAllowedExtension || !isAllowedSize) {
                fileInput.value = '';
                fileDrop.classList.add('is-invalid');
                fileName.textContent = 'Fichier non valide';
                fileStatus.textContent = 'Choisissez un CSV ou TXT de 5 Mo maximum';
                fileValidation.textContent = '× Vérifiez le format et la taille du fichier';
                swal.fire({ icon: 'error', title: 'Fichier non valide', text: 'Le fichier doit être au format CSV/TXT et ne pas dépasser 5 Mo.' });
                return;
            }

            const size = file.size < 1024 * 1024
                ? Math.max(1, Math.round(file.size / 1024)) + ' Ko'
                : (file.size / (1024 * 1024)).toFixed(1) + ' Mo';
            fileDrop.classList.add('has-file');
            fileName.textContent = file.name;
            fileStatus.textContent = size + ' · prêt pour la validation';
            fileValidation.textContent = '✓ Fichier sélectionné et prêt à importer';
            swal.fire({ icon: 'success', title: 'Fichier sélectionné', text: file.name + ' est prêt à être importé.' });
        });
    }

    document.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', (event) => {
        if (event.defaultPrevented || link.dataset.noLoader === 'true' || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.getAttribute('href') || '';
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return;
        } catch { return; }
        showLoader(link.dataset.loadingTitle || 'Ouverture de la page', link.dataset.loadingMessage || 'Chargement de votre espace…');
    }));

    document.querySelectorAll('.panel .pagination-size-form').forEach((form) => {
        const panel = form.closest('.panel');
        const target = panel?.querySelector('.section-head') || panel?.querySelector('.list-toolbar');
        if (target && !target.contains(form)) target.append(form);
    });

    window.addEventListener('pageshow', hideLoader);

    document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
        const input = button.closest('.password-wrap').querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        button.textContent = input.type === 'password' ? 'Afficher' : 'Masquer';
    }));
    document.querySelectorAll('[data-open-modal]').forEach((button) => button.addEventListener('click', () => document.getElementById(button.dataset.openModal)?.classList.add('open')));
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => button.closest('.modal')?.classList.remove('open')));
    document.querySelectorAll('.modal').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) modal.classList.remove('open'); }));
    document.querySelectorAll('select.multi-select[multiple]').forEach((select) => {
        const selectAllRow = document.createElement('label');
        const selectAll = document.createElement('input');
        selectAllRow.className = 'select-all-row';
        selectAll.type = 'checkbox';
        selectAll.setAttribute('aria-label', 'Sélectionner tous les contacts');
        selectAllRow.append(selectAll, document.createTextNode('Tout sélectionner'));
        select.insertAdjacentElement('beforebegin', selectAllRow);

        const updateSelectAllState = () => {
            const options = Array.from(select.options);
            const selectedCount = options.filter((option) => option.selected).length;
            selectAll.checked = options.length > 0 && selectedCount === options.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < options.length;
        };

        selectAll.addEventListener('change', () => {
            Array.from(select.options).forEach((option) => { option.selected = selectAll.checked; });
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
        select.addEventListener('change', updateSelectAllState);
        updateSelectAllState();
    });
    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => document.getElementById('sidebar')?.classList.toggle('open'));
    document.querySelectorAll('[data-toggle-menu]').forEach((button) => button.addEventListener('click', (event) => { event.stopPropagation(); button.nextElementSibling.classList.toggle('open'); }));
    document.addEventListener('click', () => document.querySelectorAll('.mini-menu.open').forEach((menu) => menu.classList.remove('open')));
    document.querySelectorAll('[data-edit-group]').forEach((button) => button.addEventListener('click', () => {
        const data = JSON.parse(button.dataset.editGroup);
        const modal = document.getElementById('edit-group-modal');
        const form = modal.querySelector('[data-edit-form]');
        form.action = `/groupes/${data.id}`;
        Object.entries(data).forEach(([key, value]) => { const field = form.querySelector(`[data-edit-field="${key}"]`); if (field) field.value = value ?? ''; });
        modal.classList.add('open');
    }));
    const message = document.querySelector('[data-counter]');
    const preview = document.querySelector('[data-preview-message]');
    const channelInputs = document.querySelectorAll('input[name="channel"]');
    if (message) {
        const count = document.querySelector('[data-char-count]');
        const counter = document.querySelector('.char-counter');
        const updateMessageLimit = () => {
            const channel = document.querySelector('input[name="channel"]:checked')?.value || 'whatsapp';
            const limit = channel === 'sms' ? 160 : 4096;
            message.maxLength = limit;
            if (message.value.length > limit) message.value = message.value.slice(0, limit);
            if (count) count.textContent = message.value.length;
            if (counter?.lastChild) counter.lastChild.textContent = '/' + limit + ' caractères';
            if (preview) preview.textContent = message.value || 'Votre message apparaîtra ici.';
        };
        message.addEventListener('input', updateMessageLimit);
        channelInputs.forEach((input) => input.addEventListener('change', updateMessageLimit));
        updateMessageLimit();
    }
    const campaignForm = document.querySelector('[data-campaign-form]');
    const campaignGroup = document.querySelector('[data-campaign-group]');
    const campaignChannels = document.querySelectorAll('[data-campaign-channel]');
    const campaignSubmit = document.querySelector('[data-campaign-submit]');
    const quotaNotice = document.querySelector('[data-quota-notice]');
    if (campaignForm && campaignGroup && campaignSubmit && quotaNotice) {
        const updateCampaignQuota = () => {
            const channel = document.querySelector('[data-campaign-channel]:checked')?.value || 'sms';
            const selectedOption = campaignGroup.options[campaignGroup.selectedIndex];
            const recipientCount = Number(selectedOption?.dataset.contactCount || 0);
            const available = Number(campaignForm.dataset[channel + 'Credits'] || 0);
            const pending = Number(campaignForm.dataset['pending' + channel.charAt(0).toUpperCase() + channel.slice(1)] || 0);
            const enough = recipientCount > 0 && available >= recipientCount;

            campaignSubmit.disabled = !enough;
            campaignSubmit.classList.toggle('quota-blocked', !enough);
            quotaNotice.hidden = enough;
            if (!enough) {
                quotaNotice.textContent = pending > 0
                    ? 'Quota en attente : l’administrateur doit approuver votre achat avant l’envoi.'
                    : 'Quota insuffisant : achetez puis faites approuver les crédits nécessaires avant l’envoi.';
            }
        };
        campaignGroup.addEventListener('change', updateCampaignQuota);
        campaignChannels.forEach((input) => input.addEventListener('change', updateCampaignQuota));
        updateCampaignQuota();
    }
    document.querySelectorAll('[data-stepper]').forEach((button) => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target); const delta = button.dataset.stepper === 'up' ? 10 : -10;
        input.value = Math.max(0, Math.min(100000, Number(input.value || 0) + delta)); input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    const total = document.querySelector('[data-total]');
    const updateTotal = () => { if (!total) return; let value = 0; document.querySelectorAll('.quantity-control input').forEach((input) => { value += Number(input.value || 0) * Number(input.dataset.price || 0); }); total.textContent = value.toLocaleString('fr-FR'); };
    document.querySelectorAll('.quantity-control input').forEach((input) => input.addEventListener('input', updateTotal)); updateTotal();
});
