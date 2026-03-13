// assets/js/view-po.js

let appSettings = {};
let currentPo = null;

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin');
    if (!user) return;

    // Load settings for currency
    appSettings = await getAppSettings() || { company_currency_code: 'USD' };

    // Maximize logic for PDF modal
    document.getElementById('maximizePdfModal').addEventListener('click', function () {
        const dialog = this.closest('.modal-dialog');
        dialog.classList.toggle('modal-fullscreen');
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-expand');
        icon.classList.toggle('fa-compress');
    });

    const urlParams = new URLSearchParams(window.location.search);
    const poId = urlParams.get('id');

    if (!poId) {
        Swal.fire({
            title: 'No PO Found',
            text: 'You did not specify a valid Purchase Order ID.',
            icon: 'error',
            confirmButtonText: 'Back to List'
        }).then(() => window.location.href = 'purchases');
        return;
    }

    loadPODetails(poId);
});

async function loadPODetails(poId) {
    try {
        const res = await fetch(`api/purchases.php?action=details&id=${poId}`);
        const data = await res.json();

        if (data.success) {
            currentPo = data.data;
            renderPODetails();
        } else {
            Swal.fire('Error', data.message, 'error').then(() => window.location.href = 'purchases');
        }
    } catch (e) {
        console.error("Error loading PO details", e);
        Swal.fire('Error', 'Communication failure', 'error');
    }
}

function renderPODetails() {
    const p = currentPo;
    const poNum = `PO-${p.id.toString().padStart(5, '0')}`;

    // Update Title
    document.getElementById('pageTitle').innerText = `${poNum} - Details Overview`;

    // Actions
    const actionButtons = document.getElementById('actionButtons');
    let btnsMarkup = `
        <button class="btn btn-outline-secondary px-4 me-2" onclick="previewPDF(${p.id})">
            <i class="fa-solid fa-file-pdf me-2"></i> Preview PO PDF
        </button>
    `;

    if (p.status === 'Pending' || p.status === 'Partial') {
        btnsMarkup += `
            <a href="reception?po_id=${p.id}" class="btn btn-warning px-4 me-2 fw-bold text-dark">
                <i class="fa-solid fa-truck-pickup me-2"></i> Receive Delivery
            </a>
        `;
    }

    if (p.status !== 'Cancelled') {
        btnsMarkup += `
            <button class="btn btn-outline-danger px-4" onclick="reversePurchase(${p.id})">
                <i class="fa-solid fa-rotate-left me-2"></i> Cancel PO
            </button>
        `;
    } else {
        btnsMarkup += `<div class="badge bg-danger fs-6 px-4 py-2 border border-danger">CANCELLED / REVERSED</div>`;
    }

    actionButtons.innerHTML = btnsMarkup;

    let itemsHtml = `
        <div class="table-responsive border rounded-4 overflow-hidden shadow-sm bg-white mt-4">
            <table class="table table-hover mb-0">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Product Details</th>
                        <th class="text-center py-3 text-muted small fw-bold text-uppercase">Quantity</th>
                        <th class="py-3 text-muted small fw-bold text-uppercase">Unit Cost</th>
                        <th class="text-end pe-4 py-3 text-muted small fw-bold text-uppercase">Line Total</th>
                    </tr>
                </thead>
                <tbody>
    `;

    p.items.forEach(item => {
        itemsHtml += `
            <tr class="align-middle border-bottom">
                <td class="ps-4 py-3">
                    <div class="fw-bold text-dark">${item.product_name}</div>
                    <div class="small text-muted">SKU: ${item.sku}</div>
                </td>
                <td class="text-center py-3"><span class="badge bg-light text-dark border px-3 py-2">${item.quantity}</span></td>
                <td class="py-3">${formatCurrency(item.cost_price)}</td>
                <td class="text-end pe-4 py-3 fw-bold text-primary">${formatCurrency(item.subtotal)}</td>
            </tr>
        `;
    });

    itemsHtml += '</tbody></table></div>';

    const content = `
        <div class="row g-4 mb-4">
            <!-- Summary Profile Card -->
            <div class="col-xl-4 col-md-5">
                <div class="card card-elegant border-0 shadow-sm rounded-4 h-100 p-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-4">Record Particulars</h6>
                    <div class="mb-4">
                        <label class="small text-muted d-block fw-bold mb-1">REFERENCE NUMBER</label>
                        <h2 class="fw-black text-dark mb-1">${poNum}</h2>
                        <div class="badge badge-status-${p.status.toLowerCase()} px-3 py-2">STATUS: ${p.status}</div>
                    </div>
                    
                    <hr class="my-4 opacity-10">

                    <div class="mb-3">
                        <label class="small text-muted d-block fw-bold mb-1">PURCHASE DATE</label>
                        <div class="fw-bold text-dark fs-5">${new Date(p.purchase_date).toLocaleDateString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric'
    })}</div>
                        <div class="small text-muted">${new Date(p.purchase_date).toLocaleTimeString()}</div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted d-block fw-bold mb-1">RECORD PROCESSED BY</label>
                        <div class="fw-bold fs-5"><i class="fa-solid fa-user-circle me-1"></i> ${p.creator}</div>
                    </div>
                </div>
            </div>

            <!-- Supplier Stats & Identity -->
            <div class="col-xl-8 col-md-7">
                <div class="card card-elegant border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h6 class="text-muted small fw-bold text-uppercase mb-3">Supplier Identity</h6>
                            <h3 class="fw-black text-dark mb-1">${p.supplier_name || 'N/A'}</h3>
                            <p class="text-muted mb-0"><i class="fa-solid fa-location-dot me-2"></i> ${p.address || 'Address Not Provided'}</p>
                        </div>
                        <div class="col-md-5 text-md-end border-start ps-md-4 mt-3 mt-md-0">
                            <label class="small text-muted d-block fw-bold mb-1">TIN / TAX ID</label>
                            <div class="fw-bold fs-4 text-primary">${p.tin || 'Not Registered'}</div>
                        </div>
                    </div>
                </div>

                <div class="card card-elegant border-0 shadow-sm rounded-4 p-4 bg-dark text-white">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h6 class="text-white-50 small fw-bold text-uppercase mb-2">Total PO Valuation</h6>
                            <h1 class="mb-0 fw-black text-success lh-1">${formatCurrency(p.total_amount)}</h1>
                        </div>
                        <div class="col-6 text-end">
                            <span class="badge rounded-pill bg-white text-dark border px-3 py-2 fw-bold">Items Count: ${p.items.length}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-2 mt-5">
            <h5 class="text-dark fw-bold mb-0">Order Item Specifications</h5>
            <span class="badge rounded-pill bg-light text-dark border px-3 py-2 small">Audit Log Ready</span>
        </div>
        ${itemsHtml}

        <div class="card card-elegant border-0 shadow-sm rounded-4 mt-4 p-4 bg-info bg-opacity-10">
            <div class="row align-items-center text-center">
                <div class="col-md-4">
                    <div class="small text-muted mb-1"><i class="fa-solid fa-envelope me-2"></i>Supplier Email</div>
                    <div class="fw-bold text-dark">${p.email || 'N/A'}</div>
                </div>
                <div class="col-md-4 border-start border-end">
                    <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-2"></i>Supplier Direct Line</div>
                    <div class="fw-bold text-dark">${p.phone || 'N/A'}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1"><i class="fa-solid fa-address-book me-2"></i>Point of Contact</div>
                    <div class="fw-bold text-dark">${p.contact_person || 'N/A'}</div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('detailsPlaceholder').style.display = 'none';
    const contentArea = document.getElementById('detailsContent');
    contentArea.innerHTML = content;
    contentArea.style.display = 'block';
}

async function reversePurchase(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Reverse Purchase Order?',
        text: "This will REDUCE stock quantities for all items in this PO. Historical data will be preserved but marked as reversed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6e7d88',
        confirmButtonText: 'Yes, Reverse Record'
    });

    if (isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        try {
            const res = await fetch('api/purchases.php?action=reverse', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                loadPODetails(id); // Reload
            } else {
                Swal.fire('Failed', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Communication failure', 'error');
        }
    }
}

window.previewPDF = (id) => {
    const frame = document.getElementById('poPdfFrame');
    // Using stream=1 to tell PHP to output PDF instead of HTML
    frame.src = `api/print_po.php?id=${id}&stream=1`;
    const modal = new bootstrap.Modal(document.getElementById('poPdfModal'));
    modal.show();
}

window.printPO = (id) => {
    window.open(`api/print_po.php?id=${id}&stream=1`, '_blank');
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    return `${code} ${formatted}`;
}
