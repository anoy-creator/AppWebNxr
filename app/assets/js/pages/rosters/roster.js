import $ from 'jquery';

let isRosterModalLoading = false;

const setRosterLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
};

const openRosterModal = () => {
    $('#rosterModal').addClass('is-open').attr('aria-hidden', 'false');
};

const closeRosterModal = () => {
    $('#rosterModal').removeClass('is-open').attr('aria-hidden', 'true');
    $('#rosterModalContent').empty();
};

$(document).on('click', '.js-roster-card', function () {
    if (isRosterModalLoading) {
        return;
    }

    const rosterId = $(this).data('roster-id');

    isRosterModalLoading = true;
    $('#rosterModalContent').empty();
    setRosterLoading(true);

    $.ajax({
        url: `/rosters/${rosterId}/modal`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $('#rosterModalContent').html(html);
            openRosterModal();
        },
        error: function () {
            $('#rosterModalContent').html('<p>Erreur lors du chargement du roster.</p>');
            openRosterModal();
        },
        complete: function () {
            isRosterModalLoading = false;
            setRosterLoading(false);
        }
    });
});

$(document).on('click', '.js-close-roster-modal', function () {
    closeRosterModal();
});

$(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
        closeRosterModal();
    }
});
