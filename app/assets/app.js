import './styles/app.css';

const root = document.querySelector('#ajax-root');
const loader = document.querySelector('.ajax-loader');

let activeController = null;

const isInternalPageLink = (link) => {
    if (!link || link.target || link.hasAttribute('download') || link.dataset.noAjax === 'true') {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    return url.origin === window.location.origin && !url.hash && url.pathname !== window.location.pathname;
};

const setLoading = (isLoading) => {
    document.body.classList.toggle('is-ajax-loading', isLoading);
    loader?.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
};

const updateActiveNavigation = (pathname) => {
    document.querySelectorAll('.nav-link').forEach((link) => {
        const url = new URL(link.href);
        link.classList.toggle('is-active', url.pathname === pathname);
    });
};

const loadPage = async (url, pushState = true) => {
    if (!root) {
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

        root.innerHTML = await response.text();
        const title = response.headers.get('X-Page-Title');

        if (title) {
            document.title = title;
        }

        document.body.classList.toggle('hide-site-footer', response.headers.get('X-Hide-Footer') === '1');

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

document.addEventListener('click', (event) => {
    const link = event.target.closest('a');

    if (!isInternalPageLink(link)) {
        return;
    }

    event.preventDefault();
    loadPage(new URL(link.href));
});

window.addEventListener('popstate', () => {
    loadPage(new URL(window.location.href), false);
});
