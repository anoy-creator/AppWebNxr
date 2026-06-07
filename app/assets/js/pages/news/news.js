import $ from 'jquery';

const eventNamespace = '.nxrNews';
const isInteractiveClick = (event) => (
    $(event.target).closest('[data-admin-edit], button, a, input, select, textarea, label').length > 0
);

let isNewsModalLoading = false;

const setNewsLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
};

const openNewsModal = () => {
    $('#newsModal').addClass('is-open').attr('aria-hidden', 'false');
};

const closeNewsModal = () => {
    $('#newsModal').removeClass('is-open').attr('aria-hidden', 'true');
    $('#newsModalContent').empty();
};

$(document).off(eventNamespace);

$(document).on(`click${eventNamespace}`, '.js-news-card', function (event) {
    if (isInteractiveClick(event)) {
        return;
    }

    if (isNewsModalLoading) {
        return;
    }

    const newsId = $(this).data('news-id');

    isNewsModalLoading = true;
    $('#newsModalContent').empty();
    setNewsLoading(true);

    $.ajax({
        url: `/news/${newsId}/modal`,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $('#newsModalContent').html(html);
            openNewsModal();
        },
        error: function () {
            $('#newsModalContent').html('<p>Erreur lors du chargement de l’actualité.</p>');
            openNewsModal();
        },
        complete: function () {
            isNewsModalLoading = false;
            setNewsLoading(false);
        }
    });
});

$(document).on(`keydown${eventNamespace}`, '.js-news-card', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
    }
});

$(document).on(`click${eventNamespace}`, '.js-close-news-modal', function () {
    closeNewsModal();
});

$(document).on(`keyup${eventNamespace}`, function (e) {
    if (e.key === 'Escape') {
        closeNewsModal();
    }
});
