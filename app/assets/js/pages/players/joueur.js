import $ from 'jquery';

let isFiltering = false;
let isModalLoading = false;

const setPlayerLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
    $('.player-filter').toggleClass('is-disabled', isLoading);
};

const openPlayerModal = () => {
    $('#playerModal').addClass('is-open').attr('aria-hidden', 'false');
};

const closePlayerModal = () => {
    $('#playerModal').removeClass('is-open').attr('aria-hidden', 'true');
    $('#playerModalContent').empty();
};

$(document).on('click', '.player-filter', function () {
    if (isFiltering) {
        return;
    }

    const $filter = $(this);
    const role = $filter.data('role');

    if ($filter.hasClass('is-active')) {
        return;
    }

    $('.player-filter').removeClass('is-active');
    $filter.addClass('is-active');

    isFiltering = true;
    setPlayerLoading(true);

    $.ajax({
        url: '/players',
        method: 'GET',
        data: { role },
        headers: {
            'X-Naxera-Ajax': '1',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            const $html = $('<div>').html(html);
            const newGrid = $html.find('#players-grid').html();

            if (newGrid !== undefined) {
                $('#players-grid').html(newGrid);
            }
        },
        error: function () {
            alert('Erreur lors du filtrage des joueurs');
        },
        complete: function () {
            isFiltering = false;
            setPlayerLoading(false);
        }
    });
});

$(document).on('keydown', '.player-filter', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
    }
});

$(document).on('click', '.js-player-card', function () {
    if (isModalLoading) {
        return;
    }

    const playerId = $(this).data('player-id');

    isModalLoading = true;
    $('#playerModalContent').empty();
    openPlayerModal();
    setPlayerLoading(true);

    $.ajax({
        url: `/players/${playerId}/modal`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $('#playerModalContent').html(html);
        },
        error: function () {
            $('#playerModalContent').html('<p>Erreur lors du chargement du joueur.</p>');
        },
        complete: function () {
            isModalLoading = false;
            setPlayerLoading(false);
        }
    });
});

$(document).on('click', '.js-close-player-modal', function () {
    closePlayerModal();
});

$(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
        closePlayerModal();
    }
});
