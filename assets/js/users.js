// assets/js/users.js

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin'); // Will redirect if not Admin
    if (!user) return;

    loadUsers();
    document.getElementById('userForm').addEventListener('submit', saveUser);

    // Auto-expand textarea
    const shortDetails = document.getElementById('short_details_input');
    if (shortDetails) {
        shortDetails.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});

function previewUserPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('userPhotoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

async function loadUsers() {
    const res = await fetch('api/users.php?action=list');
    const data = await res.json();
    if (data.success) {
        const tbody = document.getElementById('usersBody');
        tbody.innerHTML = '';
        data.data.forEach(u => {
            const badgeClass = u.role === 'Admin' ? 'bg-danger' : (u.role === 'Accounts' ? 'bg-info' : 'bg-primary');
            const photoAttr = u.photo ? u.photo : 'https://ui-avatars.com/api/?name=' + (u.fullname || u.username) + '&background=random';

            // Store user data in a data attribute for editing
            const userStr = btoa(unescape(encodeURIComponent(JSON.stringify(u))));

            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${photoAttr}" class="rounded-circle me-3" width="40" height="40" style="object-fit:cover">
                            <div>
                                <div class="fw-bold">${u.fullname || u.username}</div>
                                <div class="text-muted small">@${u.username}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-medium">${u.mobile || '-'}</div>
                        <div class="text-muted small">${u.email || '-'}</div>
                    </td>
                    <td><span class="badge ${badgeClass}">${u.role}</span></td>
                    <td class="text-muted small">${u.created_at}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary border-0 me-1" onclick="editUser('${userStr}')"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteUser(${u.id})"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });

        if ($.fn.DataTable.isDataTable('#usersTable')) {
            $('#usersTable').DataTable().destroy();
        }
        $('#usersTable').DataTable({
            dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            pageLength: 10,
            ordering: true,
            order: [[0, 'asc']],
            language: { emptyTable: "No users found." }
        });
    }
}

async function saveUser(e) {
    e.preventDefault();
    const btn = document.getElementById('userSubmitBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Processing...';
    btn.disabled = true;

    const fd = new FormData(e.target);
    const id = document.getElementById('user_id').value;
    const action = id ? 'update' : 'create';

    try {
        const res = await fetch('api/users.php?action=' + action, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            if (modal) modal.hide();
            resetUserForm();
            loadUsers();
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

function editUser(userStr) {
    const u = JSON.parse(decodeURIComponent(escape(atob(userStr))));

    document.getElementById('user_id').value = u.id;
    document.getElementById('userModalLabel').innerText = 'Edit User: ' + (u.fullname || u.username);

    const form = document.getElementById('userForm');
    form.fullname.value = u.fullname || '';
    form.username.value = u.username || '';
    form.role.value = u.role || 'Cashier';
    form.mobile.value = u.mobile || '';
    form.email.value = u.email || '';
    form.short_details.value = u.short_details || '';
    form.password.value = ''; // Don't show hashed password
    form.password.required = false; // Optional on edit

    document.getElementById('userPhotoPreview').src = u.photo ? u.photo : 'https://ui-avatars.com/api/?name=' + (u.fullname || u.username) + '&background=random';

    // Trigger textarea resize
    const shortDetails = document.getElementById('short_details_input');
    if (shortDetails) {
        shortDetails.style.height = 'auto';
        shortDetails.style.height = (shortDetails.scrollHeight) + 'px';
    }

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function resetUserForm() {
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('user_password_input').required = true;
    document.getElementById('userModalLabel').innerText = 'Create New User Account';
    document.getElementById('userPhotoPreview').src = 'https://ui-avatars.com/api/?name=User&background=random';
    const shortDetails = document.getElementById('short_details_input');
    if (shortDetails) shortDetails.style.height = 'auto';
}

async function deleteUser(id) {
    const result = await Swal.fire({
        title: 'Delete User?',
        text: 'This user will no longer be able to log in.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        const res = await fetch('api/users.php?action=delete', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            loadUsers();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }
}
