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
        $content.html('<p>Erreur réseau</p>');
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

$(document).on('click', function (e) {
    if (!$(e.target).closest('.user-menu').length) {
        $('#profileMenu').removeClass('show');
    }
});
