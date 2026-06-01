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

// Load profile via AJAX
const loadProfileAjax = async () => {
    const content = document.getElementById('ajax-content') || document.getElementById('ajax-root');
    if (!content) return;

    try {
        const response = await fetch('/ajax/profile', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            content.innerHTML = await response.text();
        } else {
            content.innerHTML = '<p>Erreur lors du chargement du profil</p>';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        content.innerHTML = '<p>Erreur réseau</p>';
    }
};

// Admin Dropdowns
document.addEventListener('DOMContentLoaded', () => {
    const adminMenuBtn = document.getElementById('adminMenuBtn');
    const adminMenu = document.getElementById('adminMenu');
    const profileMenuBtn = document.getElementById('profileMenuBtn');
    const profileMenu = document.getElementById('profileMenu');

    // Admin dropdown toggle
    if (adminMenuBtn && adminMenu) {
        adminMenuBtn.addEventListener('click', () => {
            adminMenu.classList.toggle('show');
            profileMenu?.classList.remove('show');
        });

        document.querySelectorAll('.admin-dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', () => {
                adminMenu.classList.remove('show');
            });
        });
    }

    // Profile dropdown toggle
    if (profileMenuBtn && profileMenu) {
        profileMenuBtn.addEventListener('click', () => {
            profileMenu.classList.toggle('show');
            adminMenu?.classList.remove('show');
        });

        // Load profile on click - attach to the menu itself
        const profileLink = profileMenu.querySelector('a[data-load-profile]');
        if (profileLink) {
            profileLink.addEventListener('click', async (e) => {
                e.preventDefault();
                profileMenu.classList.remove('show');
                console.log('Loading profile...');
                await loadProfileAjax();
            });
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.admin-dropdown') && !e.target.closest('.user-menu')) {
            adminMenu?.classList.remove('show');
            profileMenu?.classList.remove('show');
        }
    });

    // Admin form submission
    document.querySelectorAll('.admin-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const endpoint = form.dataset.endpoint;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

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

                if (response.ok) {
                    alert('✅ Élément ajouté avec succès!');
                    form.reset();
                    // Close modal
                    const modal = form.closest('.modal');
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal?.hide();
                    // Reload page
                    window.location.reload();
                } else {
                    alert('❌ Erreur: ' + (result.message || 'Une erreur est survenue'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Erreur réseau');
            }
        });
    });
});
