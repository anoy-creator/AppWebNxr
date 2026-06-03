import $ from 'jquery';

let isFiltering = false;
let isDetailLoading = false;
let openedItemId = null;

const setGlobalLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $('.ajax-loader').attr('aria-hidden', isLoading ? 'false' : 'true');
};

const closeScheduleDetail = () => {
    $('.schedule-detail-row').removeClass('is-open');
    $('.schedule-detail-content').empty();
    $('.schedule-inline-loader').remove();
    openedItemId = null;
};

$(document).on('change', '.schedule-filter input', function () {
    if (isFiltering) {
        return;
    }

    const selectedTypes = $('.schedule-filter input:checked')
        .map(function () {
            return $(this).val();
        })
        .get();

    closeScheduleDetail();

    isFiltering = true;
    setGlobalLoading(true);

    $.ajax({
        url: '/schedule',
        method: 'GET',
        data: {
            types: selectedTypes.length ? selectedTypes : ['__none__']
        },
        headers: {
            'X-Naxera-Ajax': '1',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            const $html = $('<div>').html(html);
            const $newScheduleList = $html.find('#schedule-list');

            if ($newScheduleList.length) {
                $('#schedule-list').replaceWith($newScheduleList);
            } else {
                console.error('Impossible de trouver #schedule-list dans la réponse AJAX');
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            alert('Erreur lors du filtrage du planning');
        },
        complete: function () {
            isFiltering = false;
            setGlobalLoading(false);
        }
    });
});

$(document).on('click', '.js-schedule-item', function () {
    if (isDetailLoading) {
        return;
    }

    const $item = $(this);
    const detailUrl = $item.data('detail-url');
    const itemId = $item.data('item-id');
    const $detailRow = $(`#schedule-detail-${itemId}`);
    const $content = $detailRow.find('.schedule-detail-content');

    if (openedItemId === itemId) {
        closeScheduleDetail();
        return;
    }

    closeScheduleDetail();

    openedItemId = itemId;
    isDetailLoading = true;

    $item
        .find('.event-card-meta')
        .append('<span class="schedule-inline-loader" aria-hidden="true"></span>');

    $.ajax({
        url: detailUrl,
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (html) {
            $content.html(html);
            $detailRow.addClass('is-open');
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            $content.html('<p>Erreur lors du chargement du détail.</p>');
            $detailRow.addClass('is-open');
        },
        complete: function () {
            isDetailLoading = false;
            $item.find('.schedule-inline-loader').remove();
        }
    });
});
