// assets/js/profile.js

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth();
    if (!user) return;

    loadProfile(user);

    document.getElementById('profileForm').addEventListener('submit', updateProfile);
    document.getElementById('profile_photo_input').addEventListener('change', previewProfilePhoto);
});

function loadProfile(user) {
    const photoUrl = user.photo ? user.photo : 'https://ui-avatars.com/api/?name=' + (user.fullname || user.username) + '&background=random';

    document.getElementById('profilePhotoLarge').src = photoUrl;
    document.getElementById('profileFullnameDisplay').innerText = user.fullname || user.username;
    document.getElementById('profileRoleDisplay').innerText = user.role;
    document.getElementById('profileUsernameDisplay').innerText = '@' + user.username;
    document.getElementById('profileEmailDisplay').innerText = user.email || 'No email provided';

    document.getElementById('inputFullname').value = user.fullname || '';
    document.getElementById('inputMobile').value = user.mobile || '';
    document.getElementById('inputEmail').value = user.email || '';
    document.getElementById('inputShortDetails').value = user.short_details || '';
}

function previewProfilePhoto(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
            document.getElementById('profilePhotoLarge').src = event.target.result;
        }
        reader.readAsDataURL(file);
    }
}

async function updateProfile(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalContent = btn.innerHTML;

    const password = document.getElementById('inputPassword').value;
    const confirmPassword = document.getElementById('inputConfirmPassword').value;

    if (password && password !== confirmPassword) {
        Swal.fire('Error', 'Passwords do not match', 'error');
        return;
    }

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...';
    btn.disabled = true;

    const fd = new FormData(e.target);
    const photoInput = document.getElementById('profile_photo_input');
    if (photoInput.files[0]) {
        fd.append('photo', photoInput.files[0]);
    }

    try {
        const res = await fetch('api/auth.php?action=update_me', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            // Reload page to refresh all context including navbar
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Connection failed', 'error');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
