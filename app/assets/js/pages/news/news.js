import $ from 'jquery';

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

$(document).on('click', '.js-news-card', function () {
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

$(document).on('click', '.js-close-news-modal', function () {
    closeNewsModal();
});

$(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
        closeNewsModal();
    }
});
