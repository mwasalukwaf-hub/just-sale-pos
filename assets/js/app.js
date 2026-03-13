// assets/js/app.js

async function checkAuth(requiredRole = null) {
    // --- INSTALLATION CHECK ---
    const currentPage = window.location.pathname.split('/').pop().replace('.html', '');
    if (currentPage !== 'install') {
        const installRes = await fetch('api/auth.php?action=check_install');
        const installData = await installRes.json();
        if (!installData.installed) {
            window.location.href = 'install';
            return null;
        }
    }
    // -------------------------

    try {
        const response = await fetch('api/auth.php?action=me');
        const data = await response.json();

        if (!data.success) {
            window.location.href = 'login';
            return null;
        }

        // --- LICENSING GATEKEEPER ---
        // Verify license is active for this installation
        const licenseRes = await fetch('api/activate.php?action=check');
        const licenseData = await licenseRes.json();
        
        let currentPage = window.location.pathname.split('/').pop().replace('.html', '');
        if (!currentPage || currentPage === '') currentPage = 'admin'; // Default fallback
        
        if (!licenseData.isLicensed && currentPage !== 'activate') {
            window.location.href = 'activate';
            return null;
        }
        // -----------------------------

        // Cashier restriction - Prioritize over general auth check
        const isCashier = data.user.role === 'Cashier';
        const currentPath = window.location.pathname;
        
        if (isCashier) {
            // Only allow pos, profile, and index (login)
            const allowedPages = ['pos', 'profile', 'index', 'login', ''];
            if (!allowedPages.includes(currentPage)) {
                window.location.href = 'pos';
                return null;
            }

            // Load System Settings (Logo & Company Name)
            fetch('api/settings.php?action=get').then(r => r.json()).then(data => {
                if (data.success && data.data) {
                    const s = data.data;
                    const companyName = s.company_name || 'JUSTSALE POS';
                    const el = document.getElementById('licensedToName');
                    if (el) el.innerText = companyName;

                    if (s.company_logo) {
                        const logoImg = document.getElementById('loginLogo');
                        if (logoImg) {
                            logoImg.src = s.company_logo;
                            logoImg.classList.remove('d-none');
                            const brandText = document.getElementById('loginBrandText');
                            if (brandText) brandText.classList.add('d-none');
                        }
                    }
                }
            });
            // Hide restricted navigation elements for cashiers
            document.querySelectorAll('.nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== 'pos' && !link.classList.contains('dropdown-toggle')) {
                    link.style.display = 'none';
                }
            });
            document.querySelectorAll('.nav-link-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }

        // Authorization check
        if (requiredRole && data.user.role !== requiredRole && data.user.role !== 'Admin') {
            Swal.fire('Unauthorized', 'You do not have permission to view this page.', 'error')
                .then(() => window.location.href = 'login');
            return null;
        }

        // Display user context
        const userDisplay = document.getElementById('current-user');
        if (userDisplay) {
            const firstName = data.user.fullname ? data.user.fullname.split(' ')[0] : data.user.username;
            const photoUrl = data.user.photo ? data.user.photo : 'https://ui-avatars.com/api/?name=' + (data.user.fullname || data.user.username) + '&background=random';

            // Hide settings if cashier
            const settingsItem = isCashier ? '' : `<li><a class="dropdown-item py-2" href="settings"><i class="fa-solid fa-gear me-3 text-muted"></i>Settings</a></li>`;

            userDisplay.innerHTML = `
                <div class="dropdown">
                    <div class="d-flex align-items-center gap-2 cursor-pointer dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                        <img src="${photoUrl}" class="rounded-circle border border-2 border-white shadow-sm" width="32" height="32" style="object-fit: cover;">
                        <div class="text-white d-none d-md-block">
                            <div class="fw-bold lh-1 small text-start">${firstName}</div>
                            <div class="text-white-50 lh-1" style="font-size: 0.65rem;">${data.user.role}</div>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-3 overflow-hidden">
                        <li class="px-3 py-2 bg-light border-bottom d-md-none">
                            <div class="fw-bold small">${data.user.fullname || data.user.username}</div>
                            <div class="text-muted small">${data.user.role}</div>
                        </li>
                        <li><a class="dropdown-item py-2" href="profile"><i class="fa-solid fa-user-circle me-3 text-muted"></i>My Profile</a></li>
                        ${settingsItem}
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="#" onclick="logout()"><i class="fa-solid fa-sign-out-alt me-3"></i>Logout</a></li>
                    </ul>
                </div>
            `;
        }

        return data.user;
    } catch (e) {
        window.location.href = 'login';
    }
}

async function logout() {
    await fetch('api/auth.php?action=logout');
    window.location.href = 'login';
}

async function getAppSettings() {
    try {
        const response = await fetch('api/settings.php?action=get');
        const data = await response.json();
        if (data.success) {
            return data.data;
        }
    } catch (e) {
        console.error('Failed to fetch settings', e);
    }
    return null;
}

function showToast(message, icon = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
    Toast.fire({
        icon: icon,
        title: message
    });
}

function formatCurrency(amount) {
    if (isNaN(amount) || amount === null) return '0';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}
