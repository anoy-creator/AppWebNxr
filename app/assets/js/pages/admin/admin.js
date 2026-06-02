import $ from 'jquery';

const openModal = (modalId) => {
    $('#' + modalId).addClass('is-open');
};

const closeModal = ($modal) => {
    $modal.removeClass('is-open');
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

$(document).on('click', '.dropdown-item[data-modal]', function () {
    const modalId = $(this).data('modal');

    $('#adminMenu').removeClass('show');
    openModal(modalId);
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

$(document).on('submit', '.admin-form', async function (e) {
    e.preventDefault();

    const $form = $(this);
    const endpoint = $form.data('endpoint');

    if (!endpoint) {
        console.error('data-endpoint manquant');
        return;
    }

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

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

        alert('✅ Ajout effectué');

        this.reset();

        closeModal($form.closest('.admin-modal'));

        window.location.reload();
    } catch (error) {
        console.error(error);
        alert('Erreur réseau');
    }
});
