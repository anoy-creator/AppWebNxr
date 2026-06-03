import $ from 'jquery';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const getTomSelectItems = (selector) => {
    const el = document.querySelector(selector);
    return el?.tomselect ? [...el.tomselect.items] : [];
};

const getPlayerLabel = (id) => {
    const option =
        document.querySelector(`#match-players option[value="${CSS.escape(String(id))}"]`) ||
        document.querySelector(`#event-players option[value="${CSS.escape(String(id))}"]`);

    return option?.textContent?.trim() || `Joueur ${id}`;
};

const fillPlainSelect = (selector, values) => {
    const el = document.querySelector(selector);
    if (!el) return;

    el.innerHTML = '<option value="">Choisir</option>';

    values.forEach((id) => {
        const option = document.createElement('option');
        option.value = id;
        option.textContent = getPlayerLabel(id);
        el.appendChild(option);
    });
};

const collectStats = (container) => [...container.querySelectorAll('.match-stat-row')].map((row) => ({
    player: row.dataset.playerId,
    kills: row.querySelector('[data-stat="kills"]')?.value || 0,
    deaths: row.querySelector('[data-stat="deaths"]')?.value || 0,
}));

const renderMatchStats = (players) => {
    const container = document.querySelector('#match-player-stats');
    if (!container) return;

    const currentValues = {};

    container.querySelectorAll('.match-stat-row').forEach((row) => {
        currentValues[row.dataset.playerId] = {
            kills: row.querySelector('[data-stat="kills"]')?.value || '0',
            deaths: row.querySelector('[data-stat="deaths"]')?.value || '0',
        };
    });

    container.innerHTML = '';

    if (players.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'match-stats-empty';
        empty.textContent = 'Selectionne les titulaires du match pour saisir les kills et morts.';
        container.appendChild(empty);
        return;
    }

    players.forEach((id) => {
        const values = currentValues[id] || { kills: '0', deaths: '0' };
        const label = getPlayerLabel(id);
        const row = document.createElement('div');

        row.className = 'match-stat-row';
        row.dataset.playerId = id;
        row.innerHTML = `
            <span>${escapeHtml(label)}</span>
            <input type="number" min="0" value="${escapeHtml(values.kills)}" data-stat="kills" aria-label="Kills ${escapeHtml(label)}">
            <input type="number" min="0" value="${escapeHtml(values.deaths)}" data-stat="deaths" aria-label="Morts ${escapeHtml(label)}">
        `;

        container.appendChild(row);
    });
};

const updateMatchCompositionTools = () => {
    const players = getTomSelectItems('#match-players');
    const substitutes = getTomSelectItems('#match-substitutes');

    fillPlainSelect('#match-player-to-substitute', players);
    fillPlainSelect('#match-substitute-to-player', substitutes);
    renderMatchStats(players);
};

const syncExclusivePlayerSelects = (changedSelect = null) => {
    const pairs = [
        ['#event-players', '#event-substitutes'],
        ['#match-players', '#match-substitutes'],
    ];

    pairs.forEach(([playersSelector, substitutesSelector]) => {
        const players = document.querySelector(playersSelector)?.tomselect;
        const substitutes = document.querySelector(substitutesSelector)?.tomselect;

        if (!players || !substitutes) return;

        const playersItems = [...players.items].map(String);
        const substitutesItems = [...substitutes.items].map(String);
        const duplicates = playersItems.filter((id) => substitutesItems.includes(id));

        duplicates.forEach((id) => {
            if (changedSelect === players) {
                substitutes.removeItem(id, true);
                return;
            }

            if (changedSelect === substitutes) {
                players.removeItem(id, true);
                return;
            }

            substitutes.removeItem(id, true);
        });

        players.refreshOptions(false);
        substitutes.refreshOptions(false);
        players.refreshItems();
        substitutes.refreshItems();
    });

    updateMatchCompositionTools();
};

const initSelectCustom = () => {
    document.querySelectorAll('.select-custom-multiple').forEach((el) => {
        if (el.tomselect) {
            el.tomselect.destroy();
        }

        new TomSelect(el, {
            plugins: ['remove_button'],
            create: false,
            maxItems: null,
            maxOptions: null,
            searchField: ['text'],
            valueField: 'value',
            labelField: 'text',
            placeholder: el.dataset.placeholder || 'Rechercher...',
            persist: false,
            closeAfterSelect: false,
            hideSelected: true,
            preload: true,
            openOnFocus: true,

            onFocus() {
                this.refreshOptions(false);
                this.open();
            },

            onClick() {
                this.refreshOptions(false);
                this.open();
            },

            onChange() {
                syncExclusivePlayerSelects(this);
            },
        });
    });
};

const refreshVisibleSelects = (container = document) => {
    container.querySelectorAll('.select-custom-multiple').forEach((el) => {
        if (!el.tomselect) return;

        el.tomselect.refreshOptions(false);
        el.tomselect.refreshItems();
    });
};

const toggleEventPlayersFields = () => {
    const eventTypeSelect = document.querySelector('#event-type');
    const eventPlayersFields = document.querySelector('#event-players-fields');

    if (!eventTypeSelect || !eventPlayersFields) return;

    const shouldShow = ['training', 'tournament', 'match'].includes(eventTypeSelect.value);

    eventPlayersFields.style.display = shouldShow ? 'flex' : 'none';

    if (shouldShow) {
        refreshVisibleSelects(eventPlayersFields);
    }
};

const ensureModalLoaded = async (modalId) => {
    let modal = document.getElementById(modalId);

    if (modal) {
        return modal;
    }

    const root = document.querySelector('#admin-modal-root') || document.body;
    const response = await fetch(`/admin/content/modal/${encodeURIComponent(modalId)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Impossible de charger la modale ${modalId}`);
    }

    root.insertAdjacentHTML('beforeend', await response.text());
    modal = document.getElementById(modalId);

    initSelectCustom();
    toggleEventPlayersFields();
    syncExclusivePlayerSelects();

    return modal;
};

const openModal = async (modalId) => {
    const modal = await ensureModalLoaded(modalId);

    $(`#${modalId}`).addClass('is-open');

    toggleEventPlayersFields();
    refreshVisibleSelects(modal || document);
    syncExclusivePlayerSelects();
};

const modalByType = {
    news: 'add-news',
    player: 'add-player',
    roster: 'add-roster',
    event: 'add-event',
    match: 'add-match',
};

const modalTitles = {
    news: 'Modifier une actualite',
    player: 'Modifier un joueur',
    roster: 'Modifier un roster',
    event: 'Modifier un event',
    match: 'Modifier un match',
};

const closeModal = ($modal) => {
    $modal.removeClass('is-open');
};

const setSelectValue = (selector, value) => {
    const el = document.querySelector(selector);
    if (!el) return;

    el.value = value || '';
};

const setTomSelectValues = (selector, values) => {
    const el = document.querySelector(selector);
    if (!el?.tomselect) return;

    el.tomselect.clear(true);

    if (Array.isArray(values) && values.length > 0) {
        el.tomselect.setValue(values.map(String), true);
    }

    syncExclusivePlayerSelects(el.tomselect);
};

const setFieldValue = (form, key, value) => {
    const field = form.querySelector(`[name="${CSS.escape(key)}"]`);
    if (!field) return;

    field.value = value ?? '';
};

const setMultiFieldValues = (form, key, values) => {
    const field = form.querySelector(`[name="${CSS.escape(key)}[]"]`);
    if (!field) return;

    if (field.tomselect) {
        field.tomselect.clear(true);
        field.tomselect.setValue((values || []).map(String), true);
        return;
    }

    [...field.options].forEach((option) => {
        option.selected = (values || []).map(String).includes(String(option.value));
    });
};

const resetModalFormMode = (modal) => {
    const form = modal?.querySelector('.admin-form');
    if (!form) return;

    if (form.dataset.createEndpoint) {
        form.dataset.endpoint = form.dataset.createEndpoint;
    }

    delete form.dataset.method;
    form.reset();

    form.querySelectorAll('.select-custom-multiple').forEach((el) => {
        el.tomselect?.clear(true);
    });

    const submit = form.querySelector('[type="submit"]');
    if (submit) submit.textContent = 'Ajouter';

    const title = modal.querySelector('h2');
    if (title && title.dataset.createTitle) title.textContent = title.dataset.createTitle;

    toggleEventPlayersFields();
    updateMatchCompositionTools();
};

const populateForm = (form, values) => {
    const stats = values?.stats || [];

    Object.entries(values || {}).forEach(([key, value]) => {
        if (key === 'stats') return;

        if (Array.isArray(value)) {
            setMultiFieldValues(form, key, value);
            return;
        }

        setFieldValue(form, key, value);
    });

    toggleEventPlayersFields();
    syncExclusivePlayerSelects();

    stats.forEach((row) => {
        const statRow = form.querySelector(`.match-stat-row[data-player-id="${CSS.escape(String(row.player))}"]`);
        if (!statRow) return;

        const kills = statRow.querySelector('[data-stat="kills"]');
        const deaths = statRow.querySelector('[data-stat="deaths"]');

        if (kills) kills.value = row.kills ?? 0;
        if (deaths) deaths.value = row.deaths ?? 0;
    });
};

const openEditModal = async (type, id) => {
    const response = await fetch(`/admin/content/edit/${encodeURIComponent(type)}/${encodeURIComponent(id)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.message || 'Impossible de charger l element');
    }

    const modalId = result.modal || modalByType[type];
    await openModal(modalId);

    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('.admin-form');

    if (!form) return;

    form.dataset.createEndpoint ??= form.dataset.endpoint;
    form.dataset.endpoint = result.endpoint;
    form.dataset.method = 'PATCH';

    const title = modal.querySelector('h2');
    if (title) {
        title.dataset.createTitle ??= title.textContent;
        title.textContent = modalTitles[type] || 'Modifier';
    }

    const submit = form.querySelector('[type="submit"]');
    if (submit) submit.textContent = 'Modifier';

    populateForm(form, result.values || {});
    refreshVisibleSelects(modal);
};

const loadTournamentPlayers = async (tournamentId) => {
    if (!tournamentId) {
        setSelectValue('#match-captain', '');
        setTomSelectValues('#match-players', []);
        setTomSelectValues('#match-substitutes', []);
        return;
    }

    try {
        const response = await fetch(`/admin/content/tournament/${tournamentId}/players`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) return;

        const data = await response.json();

        setSelectValue('#match-captain', data.captain || '');
        setTomSelectValues('#match-players', data.players || []);
        setTomSelectValues('#match-substitutes', data.substitutes || []);
    } catch (error) {
        console.error(error);
    }
};

const moveMatchPlayer = (fromSelector, toSelector, playerId) => {
    if (!playerId) return;

    const from = document.querySelector(fromSelector)?.tomselect;
    const to = document.querySelector(toSelector)?.tomselect;

    if (!from || !to) return;

    from.removeItem(String(playerId), true);
    to.addItem(String(playerId), true);

    from.refreshOptions(false);
    to.refreshOptions(false);

    syncExclusivePlayerSelects(to);
};

const serializeForm = (form) => {
    syncExclusivePlayerSelects();

    const formData = new FormData(form);
    const data = {};

    formData.forEach((value, key) => {
        if (key.endsWith('[]')) {
            const cleanKey = key.replace('[]', '');
            data[cleanKey] ??= [];
            data[cleanKey].push(value);
            return;
        }

        data[key] = value;
    });

    return data;
};

$(document).on('click', '#adminMenuBtn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    $('#adminMenu').toggleClass('show');
});

$(document).on('click', function (e) {
    if (!$(e.target).closest('.admin-dropdown').length) {
        $('#adminMenu').removeClass('show');
    }
});

$(document).on('click', '.dropdown-item[data-modal]', async function () {
    const modalId = $(this).data('modal');

    $('#adminMenu').removeClass('show');

    try {
        await openModal(modalId);
        resetModalFormMode(document.getElementById(modalId));
    } catch (error) {
        console.error(error);
        alert('Impossible de charger la modale');
    }
});

$(document).on('click', '[data-admin-edit]', async function (event) {
    event.preventDefault();
    event.stopPropagation();

    try {
        await openEditModal(this.dataset.adminEdit, this.dataset.adminEditId);
    } catch (error) {
        console.error(error);
        alert('Impossible de charger la modification');
    }
});

$(document).on('click', '.admin-modal-close', function () {
    closeModal($(this).closest('.admin-modal'));
});

$(document).on('click', '.admin-modal', function (e) {
    if ($(e.target).hasClass('admin-modal')) {
        closeModal($(this));
    }
});

$(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
        $('.admin-modal.is-open').removeClass('is-open');
        $('#adminMenu').removeClass('show');
    }
});

$(document).on('change', '#event-type', toggleEventPlayersFields);

$(document).on('change', '#match-tournament', function () {
    loadTournamentPlayers(this.value);
});

$(document).on('click', '[data-match-swap]', function () {
    if (this.dataset.matchSwap === 'player-to-substitute') {
        moveMatchPlayer(
            '#match-players',
            '#match-substitutes',
            document.querySelector('#match-player-to-substitute')?.value
        );
    }

    if (this.dataset.matchSwap === 'substitute-to-player') {
        moveMatchPlayer(
            '#match-substitutes',
            '#match-players',
            document.querySelector('#match-substitute-to-player')?.value
        );
    }
});

$(document).on('submit', '.admin-form', async function (e) {
    e.preventDefault();

    const $form = $(this);
    const endpoint = $form.data('endpoint');

    if (!endpoint) {
        console.error('data-endpoint manquant');
        return;
    }

    const data = serializeForm(this);

    if (this.querySelector('#match-player-stats')) {
        data.stats = collectStats(this);
    }

    try {
        const response = await fetch(endpoint, {
            method: this.dataset.method || 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (!response.ok) {
            alert(result.message || 'Erreur');
            return;
        }

        alert(this.dataset.method ? 'Modification effectuee' : 'Ajout effectue');
        this.reset();

        this.querySelectorAll('.select-custom-multiple').forEach((el) => {
            el.tomselect?.clear(true);
        });

        updateMatchCompositionTools();
        closeModal($form.closest('.admin-modal'));
        window.location.reload();
    } catch (error) {
        console.error(error);
        alert('Erreur reseau');
    }
});

$(document).on('submit', '.match-result-form', async function (e) {
    e.preventDefault();

    const endpoint = this.dataset.endpoint;

    if (!endpoint) return;

    const data = serializeForm(this);
    data.stats = collectStats(this);

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (!response.ok) {
            alert(result.message || 'Erreur');
            return;
        }

        alert('Resultat enregistre');
        window.location.reload();
    } catch (error) {
        console.error(error);
        alert('Erreur reseau');
    }
});

$(document).ready(() => {
    initSelectCustom();
    toggleEventPlayersFields();
    syncExclusivePlayerSelects();
});
