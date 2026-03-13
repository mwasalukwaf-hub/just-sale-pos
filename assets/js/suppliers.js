// assets/js/suppliers.js

let suppliersData = [];
let appSettings = {};

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin');
    if (!user) return;

    // Load settings
    appSettings = await getAppSettings() || { company_currency_code: 'USD' };

    loadSuppliers();

    document.getElementById('supplierForm').addEventListener('submit', saveSupplier);

    // Search filter
    document.getElementById('supplierSearch').addEventListener('input', filterSuppliers);
});

async function loadSuppliers() {
    try {
        const res = await fetch('api/suppliers.php?action=list');
        const data = await res.json();
        if (data.success) {
            suppliersData = data.data;
            renderSuppliers();
        }
    } catch (e) {
        console.error("Error loading suppliers", e);
    }
}

function renderSuppliers() {
    const tbody = document.getElementById('suppliersBody');
    tbody.innerHTML = '';

    suppliersData.forEach(s => {
        const tr = document.createElement('tr');
        tr.className = "align-middle";
        tr.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${s.name}</div>
                <div class="small text-muted">${s.address || 'No address'}</div>
            </td>
            <td>${s.contact_person || '-'}</td>
            <td>${s.email || '-'}</td>
            <td>${s.phone || '-'}</td>
            <td><span class="badge bg-light text-dark border fw-normal">${s.tin || '-'}</span></td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="viewSupplier(${s.id})" title="View Details">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="editSupplier(${s.id})" title="Edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteSupplier(${s.id})" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#suppliersTable')) {
        $('#suppliersTable').DataTable().destroy();
    }
    $('#suppliersTable').DataTable({
        dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 10,
        ordering: true,
        order: [[0, 'asc']],
        language: { emptyTable: "No suppliers registered." }
    });
}

window.resetSupplierForm = () => {
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplierForm').reset();
};

window.editSupplier = (id) => {
    const s = suppliersData.find(x => x.id == id);
    if (!s) return;

    document.getElementById('supplier_id').value = s.id;
    document.getElementById('supplier_name').value = s.name;
    document.getElementById('supplier_contact').value = s.contact_person || '';
    document.getElementById('supplier_email').value = s.email || '';
    document.getElementById('supplier_phone').value = s.phone || '';
    document.getElementById('supplier_tin').value = s.tin || '';
    document.getElementById('supplier_address').value = s.address || '';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal'));
    modal.show();
};

async function saveSupplier(e) {
    e.preventDefault();
    const id = document.getElementById('supplier_id').value;
    const action = id ? 'update' : 'create';
    const fd = new FormData(e.target);

    try {
        const res = await fetch(`api/suppliers.php?action=${action}`, {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
            showToast(data.message);
            loadSuppliers();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Communication failure', 'error');
    }
}

async function deleteSupplier(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Delete Supplier?',
        text: "This may affect historical records!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33'
    });

    if (isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        await fetch('api/suppliers.php?action=delete', { method: 'POST', body: fd });
        loadSuppliers();
    }
}

let currentSupplierPurchases = [];

window.viewSupplier = async (id) => {
    try {
        const res = await fetch(`api/suppliers.php?action=details&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const s = data.data;
            let totalSpend = 0;
            currentSupplierPurchases = s.purchases || [];
            let poCount = currentSupplierPurchases.length;

            if (poCount > 0) {
                totalSpend = currentSupplierPurchases.reduce((sum, p) => sum + parseFloat(p.total_amount), 0);
            }

            document.getElementById('detailsContent').innerHTML = `
                <div class="row g-4 mb-4">
                    <!-- Left: Identity & Contact -->
                    <div class="col-xl-4 col-md-5">
                        <div class="card bg-light border-0 h-100 p-4 rounded-4">
                            <h6 class="text-muted small fw-bold text-uppercase mb-3">Professional Identity</h6>
                            <h3 class="fw-black text-dark mb-1">${s.name}</h3>
                            <div class="badge bg-primary px-3 py-2 mb-4">ID: SUP-${s.id.toString().padStart(4, '0')}</div>
                            
                            <hr class="my-4 opacity-10">
                            
                            <div class="mb-3">
                                <label class="small text-muted d-block fw-bold mb-1">CONTACT PERSON</label>
                                <div class="fw-bold">${s.contact_person || 'Not Assigned'}</div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted d-block fw-bold mb-1">TAX IDENTITY (TIN)</label>
                                <div class="fw-bold fs-5">${s.tin || 'Not Registered'}</div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted d-block fw-bold mb-1">STREET ADDRESS</label>
                                <div class="text-muted small">${s.address || 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Performance & History -->
                    <div class="col-xl-8 col-md-7">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-0 bg-dark text-white p-4 rounded-4 shadow-sm">
                                    <h6 class="text-white-50 small fw-bold text-uppercase mb-2">Total Accumulated Spend</h6>
                                    <h2 class="mb-0 fw-black text-success">${formatCurrency(totalSpend)}</h2>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-white border border-2 p-4 rounded-4 shadow-sm">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Purchase Order Count</h6>
                                    <h2 class="mb-0 fw-black text-dark">${poCount} POs</h2>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 mt-5">
                            <h6 class="text-muted small fw-bold text-uppercase mb-0">Complete Order History</h6>
                            <div class="input-group input-group-sm w-50">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="historySearch" placeholder="Search orders..." oninput="filterOrderHistory()">
                            </div>
                        </div>

                        <div class="table-responsive border rounded-3 overflow-hidden">
                            <table class="table table-hover mb-0" id="historyTable" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3 py-3">Date</th>
                                        <th class="py-3">Reference</th>
                                        <th class="py-3">Total Value</th>
                                        <th class="py-3 text-center">Status</th>
                                        <th class="text-end pe-3 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="historyBody">
                                    <!-- Populated by renderOrderHistory -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="bg-light p-3 rounded-4 mt-4">
                    <div class="row align-items-center text-center">
                        <div class="col-md-4">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-envelope me-2"></i>Email Communications</div>
                            <div class="fw-bold">${s.email || 'N/A'}</div>
                        </div>
                        <div class="col-md-4 border-start border-end">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-2"></i>Direct Line</div>
                            <div class="fw-bold">${s.phone || 'N/A'}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-calendar-check me-2"></i>Relationship Since</div>
                            <div class="fw-bold">${new Date(s.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                </div>
            `;

            renderOrderHistory();
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierDetailsModal'));
            modal.show();
        }
    } catch (e) {
        console.error("View Supplier Error:", e);
        Swal.fire('Error', 'Failed to fetch details: ' + e.message, 'error');
    }
}

function renderOrderHistory() {
    const tbody = document.getElementById('historyBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    currentSupplierPurchases.forEach(p => {
        const tr = document.createElement('tr');
        tr.className = "align-middle";
        tr.innerHTML = `
            <td class="ps-3">${new Date(p.purchase_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}</td>
            <td><span class="badge bg-light text-dark border fw-bold px-3">PO-${p.id.toString().padStart(5, '0')}</span></td>
            <td class="fw-black text-primary">${formatCurrency(p.total_amount)}</td>
            <td class="text-center">
                <span class="badge status-badge-${p.status.toLowerCase()} px-3 py-1">${p.status}</span>
            </td>
            <td class="text-end pe-3">
                <a href="view-po?id=${p.id}" class="btn btn-sm btn-primary-custom px-3">
                    <i class="fa-solid fa-gears me-1"></i> Manage
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#historyTable')) {
        $('#historyTable').DataTable().destroy();
    }
    $('#historyTable').DataTable({
        dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 5,
        ordering: true,
        order: [[0, 'desc']],
        language: { emptyTable: "No orders found." }
    });
}

window.filterOrderHistory = () => {
    const query = document.getElementById('historySearch').value;
    $('#historyTable').DataTable().search(query).draw();
}


function filterSuppliers() {
    const query = document.getElementById('supplierSearch').value;
    $('#suppliersTable').DataTable().search(query).draw();
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    return `${code} ${formatted}`;
}

window.toggleModalMaximize = (modalId) => {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    const dialog = modal.querySelector('.modal-dialog');
    const btn = document.getElementById(`maximizeBtn-${modalId}`);
    const icon = btn.querySelector('i');

    dialog.classList.toggle('modal-maximized');

    if (dialog.classList.contains('modal-maximized')) {
        icon.classList.replace('fa-expand', 'fa-compress');
        btn.setAttribute('title', 'Restore');
    } else {
        icon.classList.replace('fa-compress', 'fa-expand');
        btn.setAttribute('title', 'Maximize');
    }
}
