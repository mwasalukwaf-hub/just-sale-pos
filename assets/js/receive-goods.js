// assets/js/receive-goods.js

let appSettings = {};
let productsData = [];
let suppliersData = [];
let receiveItems = [];

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initial Authentication & Identity Check
    const user = await checkAuth('Inventory');
    if (!user) return;

    // 2. Fetch Core Application Settings (Currency, etc.)
    appSettings = await getAppSettings() || { company_currency_code: 'USD' };

    // 3. Apply Localized Preferences
    applyCurrencySymbols();

    // 4. Load Data for Selection
    await Promise.all([loadProducts(), loadSuppliers()]);

    // 5. Setup Event Listeners
    document.getElementById('addReceiveItemBtn').addEventListener('click', addReceiveItem);
    document.getElementById('submitReceiveBtn').addEventListener('click', finalizeReceive);

    // Initial date
    const dateInput = document.getElementById('receive_date');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

    // Initialize Select2 specifically for Bootstrap 5
    $('.select2-init').each(function () {
        const $this = $(this);
        $this.select2({
            width: '100%',
            theme: 'bootstrap-5'
        });
    });
});

function applyCurrencySymbols() {
    const symbols = document.querySelectorAll('.currency-symbol');
    symbols.forEach(s => s.innerText = appSettings.company_currency_code || 'USD');
}

async function loadProducts() {
    try {
        const res = await fetch('api/products.php?action=list');
        const data = await res.json();
        if (data.success) {
            productsData = data.data;
            populateProductSelect();
        }
    } catch (e) {
        console.error("Failed to load products", e);
    }
}

async function loadSuppliers() {
    try {
        const res = await fetch('api/suppliers.php?action=list');
        const data = await res.json();
        if (data.success) {
            suppliersData = data.data;
            populateSupplierSelect();
        }
    } catch (e) {
        console.error("Failed to load suppliers", e);
    }
}

function populateSupplierSelect() {
    const select = document.getElementById('receive_supplier_select');
    if (!select) return;
    suppliersData.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        select.appendChild(opt);
    });
}

function populateProductSelect() {
    const select = document.getElementById('receive_product_select');
    select.innerHTML = '<option value="">-- Search Product --</option>';
    productsData.forEach(p => {
        select.innerHTML += `<option value="${p.id}" data-cost="${p.cost_price}">${p.name} (${p.sku})</option>`;
    });

    $(select).on('change', function () {
        const cost = $(this).find(':selected').data('cost');
        if (cost !== undefined) {
            document.getElementById('receive_cost').value = cost;
        }
    });
}

function addReceiveItem() {
    const select = document.getElementById('receive_product_select');
    const qtyInput = document.getElementById('receive_qty');
    const costInput = document.getElementById('receive_cost');

    const qty = parseInt(qtyInput.value);
    const cost = parseFloat(costInput.value);

    if (!select.value || !qty || isNaN(cost)) {
        Swal.fire('Incomplete', 'Please select a product, quantity and cost.', 'warning');
        return;
    }

    const productId = select.value;
    const productName = select.options[select.selectedIndex].text;

    const existing = receiveItems.find(i => i.product_id == productId);
    if (existing) {
        existing.quantity += qty;
        existing.cost_price = cost;
    } else {
        receiveItems.push({ product_id: productId, name: productName, quantity: qty, cost_price: cost });
    }

    renderReceiveTable();

    // Reset inputs but keep supplier/date
    $(select).val('').trigger('change');
    qtyInput.value = 1;
    costInput.value = '';
}

function renderReceiveTable() {
    const tbody = document.getElementById('receiveBody');
    tbody.innerHTML = '';
    let total = 0;

    receiveItems.forEach((item, index) => {
        const extension = item.quantity * item.cost_price;
        total += extension;
        const tr = document.createElement('tr');
        tr.className = "align-middle";
        tr.innerHTML = `
            <td class="ps-3">
                <div class="fw-bold">${item.name}</div>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark border px-3">${item.quantity}</span>
            </td>
            <td>${formatCurrency(item.cost_price)}</td>
            <td class="fw-bold text-success">${formatCurrency(extension)}</td>
            <td class="text-end pe-3">
                <button class="btn btn-sm btn-outline-danger border-0" onclick="removeReceiveItem(${index})">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#inspectionTable')) {
        $('#inspectionTable').DataTable().destroy();
    }
    
    if (receiveItems.length > 0) {
        $('#inspectionTable').DataTable({
            paging: false,
            ordering: true,
            info: false,
            searching: false,
            dom: 't'
        });
    }

    document.getElementById('receiveTotalAmount').innerText = formatCurrency(total);
}

window.removeReceiveItem = (index) => {
    receiveItems.splice(index, 1);
    renderReceiveTable();
};

async function finalizeReceive() {
    if (receiveItems.length === 0) {
        Swal.fire('Empty List', 'Add some products to the inspection list first.', 'warning');
        return;
    }

    const supplierId = document.getElementById('receive_supplier_select').value;
    if (!supplierId) {
        Swal.fire('Supplier Missing', 'Please select a supplier from the list.', 'warning');
        return;
    }

    const btn = document.getElementById('submitReceiveBtn');
    btn.disabled = true;

    // Get total from numerical summation to avoid string parsing issues
    const total = receiveItems.reduce((sum, i) => sum + (i.quantity * i.cost_price), 0);

    const payload = {
        supplier_id: supplierId,
        total_amount: total,
        items: receiveItems
    };

    try {
        const res = await fetch('api/inventory.php?action=receive_goods', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                title: 'PO Generated',
                text: 'Purchase Order request has been saved. You can now track it in the PO list.',
                icon: 'success'
            });
            window.location.href = 'purchases';
        } else {
            Swal.fire('Failed', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Transaction failed at transmission level.', 'error');
    } finally {
        btn.disabled = false;
    }
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
    return `${code} ${formatted}`;
}
