import $ from 'jquery';

const eventNamespace = '.nxrMatches';
const isInteractiveClick = (event) => (
    $(event.target).closest('[data-admin-edit], button, a, input, select, textarea, label').length > 0
);

let isFiltering = false;
let isLoadingDetails = false;
let openedMatchId = null;

const setGlobalLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
};

const closeOpenedMatch = () => {
    $('.match-details-row').removeClass('is-open');
    $('.match-details-content').empty();
    $('.match-result-loader').remove();
    openedMatchId = null;
};

$(document).off(eventNamespace);

$(document).on(`click${eventNamespace}`, '.match-filter', function () {
    if (isFiltering) return;

    const $filter = $(this);
    const game = $filter.data('game');

    $('.match-filter').removeClass('is-active');
    $filter.addClass('is-active');

    closeOpenedMatch();

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
            const newTable = $html.find('#matches-table-wrapper').html();

            if (newStats !== undefined) {
                $('.match-stats').html(newStats);
            }

            if (newTable !== undefined) {
                $('#matches-table-wrapper').html(newTable);
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

$(document).on(`keydown${eventNamespace}`, '.match-filter', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
    }
});

$(document).on(`click${eventNamespace}`, '.js-match-row', function (event) {
    if (isInteractiveClick(event)) {
        return;
    }

    if (isLoadingDetails) return;

    const $row = $(this);
    const matchId = $row.data('match-id');
    const $detailsRow = $(`#match-details-${matchId}`);
    const $content = $detailsRow.find('.match-details-content');
    const $resultCell = $row.find('.match-result-cell');

    if (openedMatchId === matchId) {
        closeOpenedMatch();
        return;
    }

    closeOpenedMatch();

    openedMatchId = matchId;
    isLoadingDetails = true;

    $resultCell.append('<span class="match-result-loader" aria-hidden="true"></span>');

    $.ajax({
        url: `/matches/${matchId}/details`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $content.html(html);
            $detailsRow.addClass('is-open');
        },
        error: function () {
            $content.html('<p>Erreur lors du chargement du détail du match.</p>');
            $detailsRow.addClass('is-open');
        },
        complete: function () {
            isLoadingDetails = false;
            $row.find('.match-result-loader').remove();
        }
    });
});
