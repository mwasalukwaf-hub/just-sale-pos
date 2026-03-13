// assets/js/inventory.js

let productsData = [];
let categoriesData = [];
let unitsData = [];
let allMovementsData = [];
let appSettings = {};

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth();
    if (!user) return;

    appSettings = await getAppSettings() || { company_currency_code: 'USD' };
    applyCurrencySymbols();

    loadInventoryData();

    // Form handlers
    document.getElementById('productForm').addEventListener('submit', saveProduct);
    document.getElementById('categoryForm').addEventListener('submit', saveCategory);
    document.getElementById('unitForm').addEventListener('submit', saveUnit);
    document.getElementById('adjustmentForm').addEventListener('submit', applyAdjustment);
    document.getElementById('importForm').addEventListener('submit', importProducts);

    // Search & Filter
    document.getElementById('inventorySearch').addEventListener('input', filterCatalog);
    document.getElementById('filterCategory').addEventListener('change', filterCatalog);



    // Initialize Select2 specifically for Bootstrap 5
    $('.select2-init').each(function () {
        const $this = $(this);
        const parentModal = $this.closest('.modal');
        $this.select2({
            dropdownParent: parentModal.length ? parentModal : $(document.body),
            width: '100%',
            theme: 'bootstrap-5'
        });
    });

    $('#filterMovementProduct').on('change', filterMovements);

    // Handle re-initialization when modals are shown to ensure dropdownParent is correct
    // (In case modals were moved or recreated, though here they are static)
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('.select2-init').each(function () {
            $(this).select2({
                dropdownParent: $(this).closest('.modal'),
                width: '100%',
                theme: 'bootstrap-5'
            });
        });
    });

    // Thousand Separator for prices
    document.querySelectorAll('.thousand-separator').forEach(input => {
        input.addEventListener('input', function (e) {
            let value = this.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                // Keep cursor position logic could be added but let's keep it simple
                this.value = parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            }
        });
    });
});

function applyCurrencySymbols() {
    const symbols = document.querySelectorAll('.currency-symbol');
    symbols.forEach(s => s.innerText = appSettings.company_currency_code || 'USD');
}

async function loadInventoryData() {
    try {
        // Parallel loading for speed
        const [prodRes, catRes, unitRes, statRes, moveRes, settingsRes] = await Promise.all([
            fetch('api/products.php?action=list'),
            fetch('api/products.php?action=list_categories'),
            fetch('api/products.php?action=list_units'),
            fetch('api/products.php?action=stats'),
            fetch('api/inventory.php?action=movements'),
            fetch('api/settings.php?action=get')
        ]);

        const prodData = await prodRes.json();
        const catData = await catRes.json();
        const uniData = await unitRes.json();
        const statsData = await statRes.json();
        const movementsData = await moveRes.json();
        const settingsData = await settingsRes.json();

        if (settingsData.success) {
            appSettings = settingsData.data;
            applyCurrencySymbols();
        }

        if (prodData.success) {
            productsData = prodData.data;
            renderProducts(productsData);
            populateAdjustmentSelect();
        }

        if (catData.success) {
            categoriesData = catData.data;
            renderCategories();
            populateCategoryFilter();
        }

        if (uniData.success) {
            unitsData = uniData.data;
            renderUnits();
            populateUnitSelect();
        }

        if (statsData.success) {
            renderStats(statsData.data);
        }

        if (movementsData.success) {
            allMovementsData = movementsData.data;
            populateMovementProductFilter();
            renderMovements(allMovementsData);
        }

    } catch (err) {
        console.error('Error loading inventory:', err);
        Swal.fire('Error', 'Failed to synchronize with inventory server', 'error');
    }
}

function renderProducts(products) {
    const tbody = document.getElementById('productsBody');
    tbody.innerHTML = '';

    products.forEach(p => {
        const stockStatus = p.stock_quantity <= 0 ? 'bg-danger' : (p.stock_quantity <= p.min_stock_level ? 'bg-warning' : 'bg-success');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="fw-bold text-dark">${p.name}</div>
                <div class="small text-muted">${p.unit_name || 'pcs'}</div>
            </td>
            <td>
                <div class="badge bg-light text-dark border fw-normal">${p.sku || 'N/A'}</div>
                <div class="small text-muted mt-1">${p.barcode || ''}</div>
            </td>
            <td><span class="badge bg-secondary opacity-75">${p.category_name || 'Uncategorized'}</span> <span class="d-none">${p.category_id || 0}</span></td>
            <td class="text-end fw-bold">${formatCurrency(p.cost_price)}</td>
            <td class="text-end fw-bold text-primary">${formatCurrency(p.selling_price)}</td>
            <td class="text-center">
                <span class="badge ${stockStatus} rounded-pill px-3">${p.stock_quantity}</span>
            </td>
            <td class="text-end fw-bold text-dark">
                ${formatCurrency(p.stock_quantity * p.cost_price)}
            </td>
            <td class="text-end pe-4">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning" onclick="adjustProductStock(${p.id})" title="Adjust Stock">
                        <i class="fa-solid fa-sliders"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="editProduct(${p.id})" title="Modify Product Data">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteProduct(${p.id})" title="Decommission SKU">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#productsTable')) {
        $('#productsTable').DataTable().destroy();
    }
    $('#productsTable').DataTable({
        dom: 'rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 10,
        ordering: true,
        order: [[0, 'asc']],
        language: { emptyTable: "No products in catalog." },
        footerCallback: function (row, data, start, end, display) {
            const api = this.api();
            const symbol = appSettings.company_currency_code || 'USD';
            
            // Remove formatting to get integer data for summation
            const intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '').replace(/[^-0-9\.]/g, '') * 1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            // Total over all pages
            const total = api
                .column(6)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Total over this page
            const pageTotal = api
                .column(6, { page: 'current' })
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer
            $(api.column(6).footer()).html(
                `${symbol} ${new Intl.NumberFormat('en-US', { minimumFractionDigits: 0 }).format(pageTotal)}`
            );
        }
    });
}

function renderStats(stats) {
    document.getElementById('stat_total_items').innerText = stats.total_items;
    document.getElementById('stat_stock_value').innerText = formatCurrency(stats.stock_value);
    document.getElementById('stat_low_stock').innerText = stats.low_stock;
    document.getElementById('stat_categories').innerText = stats.total_categories;
}

function renderMovements(movements) {
    const tbody = document.getElementById('movementsBody');
    tbody.innerHTML = '';

    movements.forEach(m => {
        let typeBadge = 'bg-secondary';
        if (m.type === 'Purchase') typeBadge = 'bg-success';
        else if (m.type === 'Sale') typeBadge = 'bg-primary';
        else if (['Damage', 'Loss', 'Out'].includes(m.type)) typeBadge = 'bg-danger';
        else if (m.type === 'In') typeBadge = 'bg-info';

        const date = new Date(m.date).toLocaleString();
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="small text-muted">${date}</td>
            <td><span class="badge ${typeBadge}">${m.type}</span></td>
            <td class="fw-bold">${m.product_name}</td>
            <td class="small text-truncate" style="max-width: 200px;">${m.identifier || '-'}</td>
            <td class="fw-bold ${m.qty < 0 ? 'text-danger' : 'text-success'}">${m.qty > 0 ? '+' : ''}${m.qty}</td>
            <td class="small text-muted">${m.username}</td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#movementsTable')) {
        $('#movementsTable').DataTable().destroy();
    }
    $('#movementsTable').DataTable({
        dom: 'rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 10,
        ordering: true,
        order: [[0, 'desc']],
        language: { emptyTable: "No movements logged." }
    });
}

function renderCategories() {
    const list = document.getElementById('categoryList');
    list.innerHTML = '';

    categoriesData.forEach(c => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
        item.innerHTML = `
            <span class="fw-bold px-2">${c.name}</span>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-link text-primary p-0 me-2" onclick="editCategory(${c.id})">
                    <i class="fa-solid fa-edit"></i>
                </button>
                <button class="btn btn-link text-danger p-0" onclick="deleteCategory(${c.id})">
                    <i class="fa-solid fa-times-circle"></i>
                </button>
            </div>
        `;
        list.appendChild(item);
    });
}

function renderUnits() {
    const list = document.getElementById('unitList');
    list.innerHTML = '';

    unitsData.forEach(u => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
        item.innerHTML = `
            <span class="fw-bold px-2">${u.name} <small class="text-muted">(${u.short_name})</small></span>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-link text-primary p-0 me-2" onclick="editUnit(${u.id})">
                    <i class="fa-solid fa-edit"></i>
                </button>
                <button class="btn btn-link text-danger p-0" onclick="deleteUnit(${u.id})">
                    <i class="fa-solid fa-times-circle"></i>
                </button>
            </div>
        `;
        list.appendChild(item);
    });
}

function populateCategoryFilter() {
    const filter = document.getElementById('filterCategory');
    const select = document.getElementById('product_category');

    filter.innerHTML = '<option value="">All Categories</option>';
    let options = '<option value="">UNCATEGORIZED</option>';

    categoriesData.forEach(c => {
        filter.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        options += `<option value="${c.id}">${c.name}</option>`;
    });
    select.innerHTML = options;
}

function populateUnitSelect() {
    const select = document.getElementById('product_unit');
    let options = '';
    unitsData.forEach(u => {
        options += `<option value="${u.id}">${u.name} (${u.short_name})</option>`;
    });
    select.innerHTML = options;
}

function populateAdjustmentSelect() {
    const adjSelect = document.getElementById('adj_product_select');
    if (!adjSelect) return;

    const options = productsData.map(p => `<option value="${p.id}" data-cost="${p.cost_price}">${p.name} (${p.sku || 'No SKU'})</option>`).join('');
    adjSelect.innerHTML = options;
}

function populateMovementProductFilter() {
    const filter = document.getElementById('filterMovementProduct');
    if (!filter) return;

    const currentVal = $(filter).val();
    const productNames = [...new Set(allMovementsData.map(m => m.product_name))].sort();

    let options = '<option value="">All Products</option>';
    productNames.forEach(name => {
        options += `<option value="${name}">${name}</option>`;
    });

    filter.innerHTML = options;
    if (productNames.includes(currentVal)) {
        $(filter).val(currentVal);
    }
    $(filter).trigger('change.select2');
}

function filterCatalog() {
    const search = document.getElementById('inventorySearch').value;
    const catId = document.getElementById('filterCategory').value;
    
    const table = $('#productsTable').DataTable();
    table.search(search);
    
    if (catId) {
        table.column(2).search(catId).draw();
    } else {
        table.column(2).search('').draw();
    }
}

function filterMovements() {
    const productName = document.getElementById('filterMovementProduct').value;
    const table = $('#movementsTable').DataTable();
    table.column(2).search(productName).draw();
}

function exportCatalog(format) {
    window.location.href = `api/export_reports.php?report=inventory&format=${format}`;
}

function exportMovements(format) {
    window.location.href = `api/export_reports.php?report=inventory-movements&format=${format}`;
}

function getPreviewSKU() {
    let template = appSettings.sku_template || 'PROD-{MMYYYY}-00000';
    let nextNum = parseInt(appSettings.sku_next_number || 1);

    let now = new Date();
    let mm = String(now.getMonth() + 1).padStart(2, '0');
    let yyyy = String(now.getFullYear());
    let yy = yyyy.slice(-2);

    // 1. Mask Date placeholders
    let sku = template.replace('{MMYYYY}', '[MMYYYY]')
        .replace('{MM}', '[MM]')
        .replace('{YYYY}', '[YYYY]')
        .replace('{YY}', '[YY]');

    // 2. Handle Serial Zeros
    sku = sku.replace(/\{?(0+)\}?/, (match, zeros) => {
        return String(nextNum).padStart(zeros.length, '0');
    });

    // 3. Unmask and replace with real dates
    sku = sku.replace('[MMYYYY]', mm + yyyy)
        .replace('[MM]', mm)
        .replace('[YYYY]', yyyy)
        .replace('[YY]', yy);

    return sku;
}

// Global scope functions for buttons
window.resetProductForm = () => {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';

    // Set Preview SKU
    document.getElementById('product_sku').value = getPreviewSKU();
    document.getElementById('product_sku').disabled = true;
    document.getElementById('product_sku').classList.add('bg-light');

    $('.select2-init').val('').trigger('change');
};

window.editProduct = (id) => {
    const p = productsData.find(x => x.id == id);
    if (!p) return;

    document.getElementById('product_id').value = p.id;
    document.getElementById('product_name').value = p.name;
    document.getElementById('product_barcode').value = p.barcode || '';

    // Set Existing SKU
    document.getElementById('product_sku').value = p.sku || '';
    document.getElementById('product_sku').disabled = true;
    document.getElementById('product_sku').classList.add('bg-light');

    $('#product_category').val(p.category_id || '').trigger('change');
    $('#product_unit').val(p.unit_id || '').trigger('change');

    document.getElementById('product_cost').value = parseFloat(p.cost_price).toLocaleString('en-US', { minimumFractionDigits: 0 });
    document.getElementById('product_selling').value = parseFloat(p.selling_price).toLocaleString('en-US', { minimumFractionDigits: 0 });
    document.getElementById('product_min_stock').value = p.min_stock_level || 5;

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
    modal.show();
};

window.adjustProductStock = (id) => {
    document.getElementById('adjustmentForm').reset();
    $('#adj_product_select').val(id).trigger('change');
    $('#adjustmentForm .select2-init').trigger('change');

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('adjustmentModal'));
    modal.show();
};

async function saveProduct(e) {
    e.preventDefault();
    const id = document.getElementById('product_id').value;
    const action = id ? 'update' : 'create';

    const fd = new FormData(e.target);

    try {
        const res = await fetch(`api/products.php?action=${action}`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            showToast(data.message);
            
            // Refresh settings to get the latest SKU sequence
            const newSettings = await getAppSettings();
            if (newSettings) appSettings = newSettings;
            
            loadInventoryData();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Communication failure', 'error');
    }
}

async function saveCategory(e) {
    e.preventDefault();
    const id = document.getElementById('category_id').value;
    const action = id ? 'update_category' : 'create_category';
    const fd = new FormData(e.target);
    const res = await fetch(`api/products.php?action=${action}`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        e.target.reset();
        document.getElementById('category_id').value = '';
        loadInventoryData();
        showToast(data.message);
    }
}

window.editCategory = (id) => {
    const c = categoriesData.find(x => x.id == id);
    if (!c) return;
    document.getElementById('category_id').value = c.id;
    document.getElementById('category_name').value = c.name;
};

async function deleteCategory(id) {
    const { isConfirmed } = await Swal.fire({ title: 'Remove Category?', showCancelButton: true });
    if (isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        await fetch('api/products.php?action=delete_category', { method: 'POST', body: fd });
        loadInventoryData();
    }
}

async function saveUnit(e) {
    e.preventDefault();
    const id = document.getElementById('unit_id').value;
    const action = id ? 'update_unit' : 'create_unit';
    const fd = new FormData(e.target);
    const res = await fetch(`api/products.php?action=${action}`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        e.target.reset();
        document.getElementById('unit_id').value = '';
        loadInventoryData();
        showToast(data.message);
    }
}

window.editUnit = (id) => {
    const u = unitsData.find(x => x.id == id);
    if (!u) return;
    document.getElementById('unit_id').value = u.id;
    document.getElementById('unit_name').value = u.name;
    document.getElementById('unit_short_name').value = u.short_name;
};

async function deleteUnit(id) {
    const { isConfirmed } = await Swal.fire({ title: 'Remove Unit?', showCancelButton: true });
    if (isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        await fetch('api/products.php?action=delete_unit', { method: 'POST', body: fd });
        loadInventoryData();
    }
}

async function deleteProduct(id) {
    const { isConfirmed } = await Swal.fire({
        title: 'Decommission SKU?',
        text: "This product will be removed from catalog!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it'
    });

    if (isConfirmed) {
        const fd = new FormData();
        fd.append('id', id);
        const res = await fetch('api/products.php?action=delete', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            loadInventoryData();
        }
    }
}



async function applyAdjustment(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    try {
        const res = await fetch('api/inventory.php?action=adjust_stock', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('adjustmentModal')).hide();
            e.target.reset();
            Swal.fire('Success', data.message, 'success');
            loadInventoryData();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Update failed', 'error');
    }
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
    return `${code} ${formatted}`;
}

async function importProducts(e) {
    e.preventDefault();
    const btn = document.getElementById('btnImport');
    const resultDiv = document.getElementById('importResult');
    const fd = new FormData(e.target);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    try {
        const res = await fetch('api/products.php?action=import', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            resultDiv.classList.remove('d-none');
            document.getElementById('import-total').innerText = data.details.total;
            document.getElementById('import-success').innerText = data.details.success;
            document.getElementById('import-failed').innerText = data.details.failed;

            Swal.fire('Success', data.message, 'success').then(() => {
                loadInventoryData();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Import failed: Server communication error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Start Import';
    }
}
