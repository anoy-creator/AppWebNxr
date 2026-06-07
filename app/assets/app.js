import './styles/app.css';
import $ from 'jquery';

window.$ = $;
window.jQuery = $;

const $root = $('#ajax-root');
const $loader = $('.ajax-loader');

let activeController = null;

const isInternalPageLink = ($link) => {
    if (
        !$link.length ||
        $link.attr('target') ||
        $link.attr('download') !== undefined ||
        $link.data('noAjax') === true
    ) {
        return false;
    }

    const url = new URL($link.attr('href'), window.location.href);

    if (url.pathname === '/logout') {
        return false;
    }

    return (
        url.origin === window.location.origin &&
        !url.hash &&
        url.pathname !== window.location.pathname
    );
};

const setLoading = (isLoading) => {
    $('body').toggleClass('is-ajax-loading', isLoading);
    $loader.attr('aria-hidden', isLoading ? 'false' : 'true');
};

const updateActiveNavigation = (pathname) => {
    $('.nav-link').each(function () {
        const url = new URL($(this).attr('href'), window.location.href);
        $(this).toggleClass('is-active', url.pathname === pathname);
    });
};

const readJsonPayload = async (response) => {
    const text = await response.text();

    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch (error) {
        return { message: text };
    }
};

const serializeProfileSocials = (form) => {
    const socials = {};

    new FormData(form).forEach((value, key) => {
        const match = key.match(/^socials\[([^\]]+)]$/);

        if (!match || String(value).trim() === '') {
            return;
        }

        socials[match[1]] = value;
    });

    return { socials };
};

const loadPage = async (url, pushState = true) => {
    if (!$root.length) {
        window.location.href = url.href;
        return;
    }

    activeController?.abort();
    activeController = new AbortController();

    setLoading(true);

    try {
        const response = await fetch(url.href, {
            headers: {
                'X-Naxera-Ajax': '1',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: activeController.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        $root.html(await response.text());

        const title = response.headers.get('X-Page-Title');

        if (title) {
            document.title = title;
        }

        $('body').toggleClass(
            'hide-site-footer',
            response.headers.get('X-Hide-Footer') === '1'
        );

        if (pushState) {
            window.history.pushState({ ajax: true }, title || '', url.href);
        }

        updateActiveNavigation(url.pathname);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.href = url.href;
        }
    } finally {
        setLoading(false);
    }
};

const loadProfileAjax = async () => {
    const $content = $('#ajax-content').length ? $('#ajax-content') : $('#ajax-root');

    if (!$content.length) {
        return;
    }

    setLoading(true);

    try {
        const response = await fetch('/ajax/profile', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            $content.html(await response.text());
        } else {
            $content.html('<p>Erreur lors du chargement du profil</p>');
        }
    } catch (error) {
        console.error(error);
        $content.html('<p>Erreur reseau</p>');
    } finally {
        setLoading(false);
    }
};

$(document).on('click', 'a', function (event) {
    const $link = $(this);

    if (!isInternalPageLink($link)) {
        return;
    }

    event.preventDefault();
    loadPage(new URL($link.attr('href'), window.location.href));
});

$(document).on('click', '[data-logout-link]', function () {
    setLoading(true);
});

$(window).on('popstate', () => {
    loadPage(new URL(window.location.href), false);
});

$(document).on('click', '#profileMenuBtn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    $('#profileMenu').toggleClass('show');
    $('#adminMenu').removeClass('show');
});

$(document).on('click', '[data-load-profile]', async function (e) {
    e.preventDefault();

    $('#profileMenu').removeClass('show');
    await loadProfileAjax();
});

$(document).on('submit', '[data-profile-socials-form]', async function (e) {
    e.preventDefault();

    const form = this;
    const button = form.querySelector('button[type="submit"]');
    const feedback = form.querySelector('[data-profile-socials-feedback]');

    if (feedback) {
        feedback.textContent = '';
        feedback.classList.remove('is-error', 'is-success');
    }

    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch('/ajax/profile/socials', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(serializeProfileSocials(form)),
        });
        const payload = await readJsonPayload(response);

        if (!response.ok) {
            throw new Error(payload.message || 'Impossible d enregistrer les liens');
        }

        if (feedback) {
            feedback.textContent = payload.message || 'Liens enregistres';
            feedback.classList.add('is-success');
        }

        window.setTimeout(loadProfileAjax, 450);
    } catch (error) {
        if (feedback) {
            feedback.textContent = error.message || 'Erreur reseau';
            feedback.classList.add('is-error');
        }
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
});

$(document).on('submit', '[data-profile-delete-form]', async function (e) {
    e.preventDefault();

    const form = this;
    const button = form.querySelector('button[type="submit"]');
    const feedback = form.querySelector('[data-profile-delete-feedback]');

    if (!window.confirm('Supprimer ton profil et tes donnees personnelles ? Cette action est definitive.')) {
        return;
    }

    if (feedback) {
        feedback.textContent = '';
        feedback.classList.remove('is-error', 'is-success');
    }

    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch('/ajax/profile/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                _token: form.dataset.token || '',
            }),
        });
        const payload = await readJsonPayload(response);

        if (!response.ok) {
            throw new Error(payload.message || 'Impossible de supprimer le profil');
        }

        if (feedback) {
            feedback.textContent = payload.message || 'Profil supprime';
            feedback.classList.add('is-success');
        }

        window.sessionStorage?.clear();
        window.location.href = payload.redirect || '/';
    } catch (error) {
        if (feedback) {
            feedback.textContent = error.message || 'Erreur reseau';
            feedback.classList.add('is-error');
        }

        if (button) {
            button.disabled = false;
        }
    }
});

$(document).on('click', function (e) {
    if (!$(e.target).closest('.user-menu').length) {
        $('#profileMenu').removeClass('show');
    }
});
