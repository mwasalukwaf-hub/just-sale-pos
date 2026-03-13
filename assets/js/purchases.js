// assets/js/purchases.js

let purchasesData = [];
let appSettings = {};

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin');
    if (!user) return;

    // Load settings for currency
    appSettings = await getAppSettings() || { company_currency_code: 'USD' };

    loadPurchases();

    // Search filter
    document.getElementById('poSearch').addEventListener('input', filterPurchases);
});

async function loadPurchases() {
    try {
        const res = await fetch('api/purchases.php?action=list');
        const data = await res.json();
        if (data.success) {
            purchasesData = data.data;
            renderPurchases();
        }
    } catch (e) {
        console.error("Error loading POs", e);
    }
}

function renderPurchases() {
    const tbody = document.getElementById('poBody');
    tbody.innerHTML = '';

    purchasesData.forEach(p => {
        const poNum = `PO-${p.id.toString().padStart(5, '0')}`;
        const tr = document.createElement('tr');
        tr.className = p.status === 'Cancelled' ? "align-middle opacity-75" : "align-middle";

        tr.innerHTML = `
            <td>${new Date(p.purchase_date).toLocaleDateString()}</td>
            <td class="fw-bold text-dark">${poNum}</td>
            <td>${p.supplier_name || 'Walk-in / Unknown'}</td>
            <td><span class="small text-muted">${p.creator}</span></td>
            <td class="text-end fw-bold text-primary">${formatCurrency(p.total_amount)}</td>
            <td class="text-center">
                <span class="badge status-badge-${p.status.toLowerCase()} px-3 py-1">
                    ${p.status}
                </span>
            </td>
            <td class="text-end">
                <a href="view-po?id=${p.id}" class="btn btn-primary btn-sm px-3 fw-bold rounded-pill">
                    <i class="fa-solid fa-gears me-1"></i> Manage
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#purchasesTable')) {
        $('#purchasesTable').DataTable().destroy();
    }
    $('#purchasesTable').DataTable({
        dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 10,
        ordering: true,
        order: [[0, 'desc']],
        language: { emptyTable: "No purchase orders found." }
    });
}

function filterPurchases() {
    const query = document.getElementById('poSearch').value;
    $('#purchasesTable').DataTable().search(query).draw();
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
    return `${code} ${formatted}`;
}
