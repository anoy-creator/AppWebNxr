import $ from 'jquery';

const eventNamespace = '.nxrMatches';
const isInteractiveClick = (event) => (
    $(event.target).closest('[data-admin-edit], button, a, input, select, textarea, label').length > 0
);

let isFiltering = false;
let isLoadingDetails = false;

const setGlobalLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
};

const openMatchModal = () => {
    $('#matchModal').addClass('is-open').attr('aria-hidden', 'false');
};

const closeMatchModal = () => {
    $('#matchModal').removeClass('is-open').attr('aria-hidden', 'true');
    $('#matchModalContent').empty();
    $('.match-result-loader').remove();
};

const setMatchTriggerLoading = ($trigger, isLoading) => {
    $('.match-result-loader').remove();

    if (!isLoading) {
        return;
    }

    const $target = $trigger.find('.match-result-cell, .home-match-meta').first();

    if ($target.length) {
        $target.append('<span class="match-result-loader" aria-hidden="true"></span>');
    }
};

const loadMatchDetails = ($trigger) => {
    if (isLoadingDetails) return;

    const matchId = $trigger.data('match-id');

    if (!matchId) return;

    isLoadingDetails = true;
    $('#matchModalContent').empty();
    setMatchTriggerLoading($trigger, true);

    $.ajax({
        url: `/matches/${matchId}/details`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $('#matchModalContent').html(html);
            openMatchModal();
        },
        error: function () {
            $('#matchModalContent').html('<p>Erreur lors du chargement du detail du match.</p>');
            openMatchModal();
        },
        complete: function () {
            isLoadingDetails = false;
            setMatchTriggerLoading($trigger, false);
        }
    });
};

$(document).off(eventNamespace);

$(document).on(`click${eventNamespace}`, '.match-filter', function () {
    if (isFiltering) return;

    const $filter = $(this);
    const game = $filter.data('game');

    $('.match-filter').removeClass('is-active');
    $filter.addClass('is-active');
    closeMatchModal();

    isFiltering = true;
    setGlobalLoading(true);

    $.ajax({
        url: '/matches',
        method: 'GET',
        data: { game },
        headers: {
            'X-Naxera-Ajax': '1',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            const $html = $('<div>').html(html);

            const newStats = $html.find('.match-stats').html();
            const newContent = $html.find('#matches-content').html();

            if (newStats !== undefined) {
                $('.match-stats').html(newStats);
            }

            if (newContent !== undefined) {
                $('#matches-content').html(newContent);
            }
        },
        error: function () {
            alert('Erreur lors du filtrage des matchs');
        },
        complete: function () {
            isFiltering = false;
            setGlobalLoading(false);
        }
    });
});

$(document).on(`keydown${eventNamespace}`, '.match-filter, .js-match-card, .js-tournament-card', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
    }
});

$(document).on(`click${eventNamespace}`, '.js-tournament-card', function (event) {
    if (isInteractiveClick(event)) {
        return;
    }

    const tournamentId = $(this).data('tournament-id');
    const $panel = $(`#tournament-matches-${tournamentId}`);

    $('.tournament-match-panel').not($panel).removeClass('is-open');
    $('.js-tournament-card').not(this).removeClass('is-open');

    $panel.toggleClass('is-open');
    $(this).toggleClass('is-open', $panel.hasClass('is-open'));
});

$(document).on(`click${eventNamespace}`, '.js-match-row, .js-match-card', function (event) {
    if (isInteractiveClick(event)) {
        return;
    }

    loadMatchDetails($(this));
});

$(document).on(`click${eventNamespace}`, '.js-close-match-modal', function () {
    closeMatchModal();
});

$(document).on(`keyup${eventNamespace}`, function (e) {
    if (e.key === 'Escape') {
        closeMatchModal();
    }
});
