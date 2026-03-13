// assets/js/pos.js

let productsCache = [];
let categoriesCache = [];
let companySettings = {};
let expectedCashAmount = 0;
let activeShiftId = null;
let lastSaleId = null;

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
}

// Queue Management
let queues = JSON.parse(localStorage.getItem('pos_queues')) || [
    { id: Date.now(), name: 'Sale 1', cart: [], customer: null }
];
let activeQueueIndex = parseInt(localStorage.getItem('pos_active_queue')) || 0;
if (activeQueueIndex >= queues.length) activeQueueIndex = 0;

document.addEventListener('DOMContentLoaded', async () => {
    // POS is accessible by Cashier, Admin, Accounts
    const user = await checkAuth();
    if (!user) return;

    if (document.getElementById('lockedUserName')) {
        document.getElementById('lockedUserName').innerText = user.fullname || user.username;
    }

    // Load config
    await loadCompanySettings();

    // Check screen lock persistence
    if (localStorage.getItem('pos_screen_locked') === 'true') {
        lockScreen(false); // lock without refreshing user name again if unnecessary, but let's just call it
    }

    // Check shift status
    await checkShiftStatus();

    // Load data
    await loadCategories();
    await loadProducts();

    // Initial view: Categories
    renderCategoriesGrid();
    renderQueues();
    renderCart();
    updateCustomerDisplay();

    // Form binds
    document.getElementById('openShiftForm').addEventListener('submit', openShift);
    document.getElementById('closeShiftForm').addEventListener('submit', closeShift);
    document.getElementById('addCustomerForm').addEventListener('submit', handleAddCustomer);
    const unlockForm = document.getElementById('unlockForm');
    if (unlockForm) {
        unlockForm.addEventListener('submit', handleUnlock);
    }

    // Close customer search when clicking outside
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('customerSearchDropdown');
        const display = document.getElementById('customer-display');
        if (dropdown && !dropdown.contains(e.target) && !display.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Auto-fullscreen on first interaction
    document.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {
                // Ignore errors
            });
        }
    }, { once: true });
});

function saveState() {
    localStorage.setItem('pos_queues', JSON.stringify(queues));
    localStorage.setItem('pos_active_queue', activeQueueIndex.toString());
}

function renderQueues() {
    const container = document.getElementById('queueTabs');
    if (!container) return;

    const addButton = container.querySelector('button');
    container.innerHTML = '';

    queues.forEach((q, i) => {
        const tab = document.createElement('div');
        tab.className = `queue-tab ${i === activeQueueIndex ? 'active' : ''}`;
        tab.onclick = () => switchQueue(i);
        tab.innerHTML = `
            <span>${q.name}</span>
            ${queues.length > 1 ? `<i class="fa-solid fa-times btn-close-tab" onclick="event.stopPropagation(); removeQueue(${i})"></i>` : ''}
        `;
        container.appendChild(tab);
    });

    container.appendChild(addButton);
}

function addNewQueue() {
    const nextNum = queues.length + 1;
    queues.push({
        id: Date.now(),
        name: `Sale ${nextNum}`,
        cart: [],
        customer: null
    });
    activeQueueIndex = queues.length - 1;
    saveState();
    renderQueues();
    renderCart();
    updateCustomerDisplay();
}

function switchQueue(index) {
    activeQueueIndex = index;
    saveState();
    renderQueues();
    renderCart();
    updateCustomerDisplay();
}

function removeQueue(index) {
    if (queues.length <= 1) return;
    queues.splice(index, 1);
    if (activeQueueIndex >= queues.length) activeQueueIndex = queues.length - 1;
    saveState();
    renderQueues();
    renderCart();
    updateCustomerDisplay();
}

// Customer Management
function toggleCustomerSearch() {
    const dropdown = document.getElementById('customerSearchDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    if (dropdown.style.display === 'block') {
        document.getElementById('customerSearchInput').focus();
        searchCustomers('');
    }
}

async function searchCustomers(term) {
    try {
        const res = await fetch(`api/customers.php?action=list&search=${encodeURIComponent(term)}`);
        const data = await res.json();
        const list = document.getElementById('customerResultsList');
        list.innerHTML = '';

        // Always include Walk-in
        const walkIn = document.createElement('div');
        walkIn.className = 'customer-item fw-bold text-primary';
        walkIn.innerHTML = '<i class="fa-solid fa-user-circle me-2"></i>Walk-in Customer';
        walkIn.onclick = () => selectCustomer(null);
        list.appendChild(walkIn);

        if (data.success) {
            data.data.forEach(c => {
                const item = document.createElement('div');
                item.className = 'customer-item';
                item.innerHTML = `
                    <div class="fw-bold">${c.name}</div>
                    <div class="small text-muted">${c.mobile || 'No mobile'}</div>
                `;
                item.onclick = () => selectCustomer(c);
                list.appendChild(item);
            });
        }
    } catch (e) {
        console.error("Customer search failed");
    }
}

function selectCustomer(customer) {
    queues[activeQueueIndex].customer = customer;
    saveState();
    updateCustomerDisplay();
    document.getElementById('customerSearchDropdown').style.display = 'none';
}

function updateCustomerDisplay() {
    const customer = queues[activeQueueIndex].customer;
    const info = document.getElementById('selectedCustomerInfo');
    if (customer) {
        info.innerHTML = `
            <i class="fa-solid fa-user-circle me-1 text-primary"></i>
            <span class="fw-bold text-primary">${customer.name}</span>
        `;
    } else {
        info.innerHTML = `
            <i class="fa-solid fa-user-circle me-1 text-muted"></i>
            <span class="text-muted">Walk-in Customer</span>
        `;
    }
}

function showAddCustomerModal() {
    document.getElementById('addCustomerForm').reset();
    new bootstrap.Modal(document.getElementById('addCustomerModal')).show();
}

async function handleAddCustomer(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const res = await fetch('api/customers.php?action=add', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
            selectCustomer({
                id: data.id,
                name: fd.get('name'),
                mobile: fd.get('mobile')
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Failed to add customer', 'error');
    }
}

async function loadCompanySettings() {
    try {
        const res = await fetch('api/settings.php?action=get');
        const data = await res.json();
        if (data.success) {
            companySettings = data.data;
            updateCurrencyLabels();
        }
    } catch (e) {
        console.error("Failed to load settings");
    }
}

function updateCurrencyLabels() {
    const symbol = companySettings.company_currency_code || '$';
    const labels = ['currencySymbolOpen', 'currencySymbolClose', 'currencySymbolTendered'];
    labels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = symbol;
    });
}

async function checkShiftStatus() {
    const res = await fetch('api/shifts.php?action=current&t=' + new Date().getTime());
    const data = await res.json();

    const info = document.getElementById('shiftStatusInfo');
    const btnOpen = document.getElementById('btnOpenShift');
    const btnClose = document.getElementById('btnCloseShift');
    const overlay = document.getElementById('shiftOverlay');

    if (data.success && data.shift) {
        activeShiftId = data.shift.id;
        expectedCashAmount = parseFloat(data.shift.expected_cash) || 0;
        
        if (info) info.innerHTML = `<i class="fa-solid fa-lock-open me-1 text-success"></i> Shift Open`;
        if (btnOpen) btnOpen.style.display = 'none';
        if (btnClose) btnClose.style.display = 'inline-block';
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.removeProperty('display'); // Clear any inline display
            overlay.style.setProperty('display', 'none', 'important'); // Force hide
        }
    } else {
        activeShiftId = null;
        if (info) info.innerHTML = `<i class="fa-solid fa-lock me-1 text-danger"></i> Shift Closed`;
        if (btnOpen) btnOpen.style.display = 'inline-block';
        if (btnClose) btnClose.style.display = 'none';
        if (overlay) {
            overlay.style.setProperty('display', 'flex', 'important');
        }
    }
}

function openShiftModal() {
    new bootstrap.Modal(document.getElementById('openShiftModal')).show();
}

function closeShiftModal() {
    const expectedEl = document.getElementById('expectedInDrawer');
    if (expectedEl) {
        expectedEl.innerText = (companySettings.company_currency_code || 'TSh') + ' ' + formatNumber(expectedCashAmount);
    }
    
    // Clear previous inputs
    const actualInput = document.getElementById('actualCashInput');
    if (actualInput) actualInput.value = '';
    
    const diffWrap = document.getElementById('shiftDifferenceWrap');
    if (diffWrap) diffWrap.style.setProperty('display', 'none', 'important');

    new bootstrap.Modal(document.getElementById('closeShiftModal')).show();
}

function calculateShiftDifference() {
    const input = document.getElementById('actualCashInput');
    let raw = input.value.replace(/[^0-9]/g, '');
    
    if (raw) {
        input.value = parseInt(raw).toLocaleString('en-US');
    } else {
        input.value = '';
    }

    const actual = parseFloat(raw) || 0;
    const diff = actual - expectedCashAmount;
    
    const diffWrap = document.getElementById('shiftDifferenceWrap');
    const diffVal = document.getElementById('shiftDifferenceVal');
    
    if (diffWrap) diffWrap.style.setProperty('display', 'flex', 'important');
    
    if (diffVal) {
        diffVal.innerText = (companySettings.company_currency_code || 'TSh') + ' ' + formatNumber(diff);
        if (diff === 0) {
            diffVal.className = 'fw-bold h5 mb-0 text-success';
        } else if (diff < 0) {
            diffVal.className = 'fw-bold h5 mb-0 text-danger';
        } else {
            diffVal.className = 'fw-bold h5 mb-0 text-primary';
        }
    }
}

async function openShift(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('api/shifts.php?action=open', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        showToast(data.message);
        const modalEl = document.getElementById('openShiftModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        console.log("Shift opened successfully, refreshing status...");
        setTimeout(async () => {
            await checkShiftStatus();
        }, 500);
    } else {
        Swal.fire('Error', data.message, 'error');
    }
}

async function closeShift(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const balance = fd.get('closing_balance').replace(/,/g, '');
    fd.set('closing_balance', balance);

    const res = await fetch('api/shifts.php?action=close', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        showToast(data.message);
        bootstrap.Modal.getInstance(document.getElementById('closeShiftModal')).hide();
        await checkShiftStatus();
    } else {
        Swal.fire('Error', data.message, 'error');
    }
}

// Screen Lock Logic
function lockScreen(saveState = true) {
    const overlay = document.getElementById('screenLockOverlay');
    const userDisplay = document.querySelector('.fw-bold.lh-1.small.text-start'); // From app.js context
    const userName = userDisplay ? userDisplay.innerText : 'Cashier';

    const sessionUserEl = document.getElementById('lockedSessionUser');
    if (sessionUserEl) sessionUserEl.innerText = userName;

    const passInput = document.getElementById('unlockPassword');
    if (passInput) passInput.value = '';

    if (overlay) {
        overlay.style.setProperty('display', 'flex', 'important');
    }

    if (saveState) {
        localStorage.setItem('pos_screen_locked', 'true');
    }
}

async function handleUnlock(e) {
    e.preventDefault();
    const password = document.getElementById('unlockPassword').value;
    const btn = e.target.querySelector('button');

    btn.disabled = true;
    btn.innerText = 'Verifying...';

    try {
        const formData = new FormData();
        formData.append('password', password);

        const res = await fetch('api/auth.php?action=verify_password', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            const overlay = document.getElementById('screenLockOverlay');
            overlay.style.setProperty('display', 'none', 'important');
            localStorage.removeItem('pos_screen_locked');
            showToast('Screen Unlocked');
        } else {
            Swal.fire('Error', data.message || 'Invalid Password', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Verification failed', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Unlock Terminal';
    }
}

function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            showToast(`Error: ${err.message}`, 'error');
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Data loading
async function loadCategories() {
    const res = await fetch('api/products.php?action=list_categories');
    const data = await res.json();
    if (data.success) {
        categoriesCache = data.data;
        const strip = document.getElementById('categoryStrip');
        categoriesCache.forEach(c => {
            strip.innerHTML += `<div class="category-btn" onclick="filterCategory(${c.id}, this)">${c.name}</div>`;
        });
    }
}

async function loadProducts() {
    const res = await fetch('api/products.php?action=list');
    const data = await res.json();
    if (data.success) {
        productsCache = data.data;
    }
}

function renderCategoriesGrid() {
    const grid = document.getElementById('productGrid');
    if (!grid) return;
    grid.innerHTML = '';

    // Hide the category strip but leave the parent row (search bar) visible
    const strip = document.getElementById('categoryStrip');
    if (strip) strip.style.display = 'none';

    categoriesCache.forEach(c => {
        const count = productsCache.filter(p => p.category_id == c.id).length;
        grid.innerHTML += `
            <div class="product-card border-0 shadow-sm" onclick="filterCategory(${c.id})" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                <div class="d-flex flex-column align-items-center justify-content-center h-100">
                    <i class="fa-solid fa-folder-open text-primary mb-3" style="font-size: 2rem;"></i>
                    <div class="product-name fw-bold" style="height: auto; font-size: 1.1rem;">${c.name}</div>
                    <div class="text-muted small">${count} Products</div>
                </div>
            </div>
        `;
    });
}

function renderProducts(products, isFiltered = false) {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = '';

    if (isFiltered || document.getElementById('searchProduct').value) {
        grid.innerHTML = `
            <div class="product-card border-primary" onclick="renderCategoriesGrid()">
                <div class="d-flex flex-column align-items-center justify-content-center h-100">
                    <i class="fa-solid fa-arrow-left text-primary mb-2"></i>
                    <div class="product-name text-primary fw-bold" style="height: auto;">Back to Categories</div>
                </div>
            </div>
        `;
    }

    products.forEach(p => {
        // Disabled if out of stock
        const noStock = p.stock_quantity <= 0;
        const symbol = companySettings.company_currency_code || '$';
        grid.innerHTML += `
            <div class="product-card ${noStock ? 'opacity-50' : ''}" onclick="addToCart(${p.id})">
                <div class="product-name">${p.name}</div>
                <div class="product-price">${symbol} ${formatNumber(p.selling_price)}</div>
                <div class="small fw-bold ${noStock ? 'text-danger' : 'text-muted'}">${p.stock_quantity} in stock</div>
            </div>
        `;
    });
}

function filterCategory(id) {
    if (id === 'all') {
        renderCategoriesGrid();
    } else {
        renderProducts(productsCache.filter(p => p.category_id == id), true);
    }
}

function searchProducts(term) {
    if (!term) return renderCategoriesGrid();
    const lower = term.toLowerCase();
    const filtered = productsCache.filter(p => p.name.toLowerCase().includes(lower) || (p.barcode && p.barcode.includes(term)));
    renderProducts(filtered, true);
}

// Cart Logic
function addToCart(pId) {
    if (!activeShiftId) return Swal.fire('Error', 'Please open a shift first.', 'warning');

    const product = productsCache.find(p => p.id == pId);
    if (!product) return;
    if (product.stock_quantity <= 0) return showToast('Out of stock', 'error');

    const cart = queues[activeQueueIndex].cart;
    const existing = cart.find(c => c.id == pId);
    if (existing) {
        if (existing.quantity + 1 > product.stock_quantity) return showToast('Not enough stock', 'error');
        existing.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            quantity: 1,
            max: product.stock_quantity
        });
    }
    saveState();
    renderCart();
}

function removeItem(index) {
    queues[activeQueueIndex].cart.splice(index, 1);
    saveState();
    renderCart();
}

function clearCart() {
    if (queues[activeQueueIndex].cart.length === 0) return;
    Swal.fire({
        title: 'Clear cart?',
        text: 'Are you sure you want to clear current items?',
        icon: 'warning',
        showCancelButton: true
    }).then(r => {
        if (r.isConfirmed) {
            queues[activeQueueIndex].cart = [];
            saveState();
            renderCart();
        }
    });
}

function renderCart() {
    const list = document.getElementById('cartList');
    const cart = queues[activeQueueIndex].cart;

    if (cart.length === 0) {
        list.innerHTML = `
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                <i class="fa-solid fa-basket-shopping fa-3x mb-3"></i>
                <p>Cart is empty</p>
            </div>`;
        const symbol = companySettings.company_currency_code || '$';
        document.getElementById('cartCount').innerText = '0';
        document.getElementById('cartSubtotal').innerText = `${symbol} 0`;
        document.getElementById('cartTotal').innerText = `${symbol} 0`;
        document.getElementById('btnPayAmount').innerText = `${symbol} 0`;
        document.getElementById('btnPay').disabled = true;
        return;
    }

    let html = '';
    let total = 0;
    let items = 0;

    cart.forEach((item, i) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        items += item.quantity;
        const symbol = companySettings.company_currency_code || '$';

        html += `
            <div class="cart-item py-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="cart-item-name fw-bold mb-0 d-flex align-items-center justify-content-between gap-1">
                        <span class="text-truncate flex-grow-1" style="max-width: 130px;">${item.name}</span>
                        <span class="badge bg-light text-dark border font-monospace flex-shrink-0">x${item.quantity}</span>
                    </div>
                    <div class="text-primary small fw-bold">${symbol} ${formatNumber(item.price)}</div>
                </div>
                <div class="text-end me-3">
                    <div class="fw-bold">${symbol} ${formatNumber(subtotal)}</div>
                </div>
                <div>
                    <button class="btn btn-link text-danger p-0" onclick="removeItem(${i})">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
    document.getElementById('cartCount').innerText = items;
    document.getElementById('cartSubtotal').innerText = `${companySettings.company_currency_code || '$'} ${formatNumber(total)}`;
    document.getElementById('cartTotal').innerText = `${companySettings.company_currency_code || '$'} ${formatNumber(total)}`;
    document.getElementById('btnPayAmount').innerText = `${companySettings.company_currency_code || '$'} ${formatNumber(total)}`;
    document.getElementById('btnPay').disabled = false;
}

function getCartTotal() {
    return queues[activeQueueIndex].cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
}

// Checkout
function showCheckoutModal() {
    const total = getCartTotal();
    document.getElementById('checkoutTotalDue').innerText = `${companySettings.company_currency_code || '$'} ${formatNumber(total)}`;
    document.getElementById('amountTendered').value = ''; // Clear for fresh entry
    document.getElementById('checkoutChange').innerText = `${companySettings.company_currency_code || '$'} 0`;
    document.getElementById('payCash').checked = true;
    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
}

function appendNumber(n) {
    const input = document.getElementById('amountTendered');
    // Remove existing commas to get raw number
    let raw = input.value.replace(/,/g, '');

    if (n === '00') {
        raw += '00';
    } else {
        raw += n;
    }

    // Format back with commas
    if (raw) {
        input.value = parseInt(raw).toLocaleString('en-US');
    }
    calculateChange();
}

function clearNumber() {
    const input = document.getElementById('amountTendered');
    let val = input.value.replace(/[^0-9]/g, '');
    if (val.length > 0) {
        input.value = val.substring(0, val.length - 1);
    }
    calculateChange();
}

function calculateChange() {
    const input = document.getElementById('amountTendered');
    const total = getCartTotal();

    // Format the input value while typing/changing
    let raw = input.value.replace(/[^0-9]/g, '');
    if (raw) {
        input.value = parseInt(raw).toLocaleString('en-US');
    } else {
        input.value = '';
    }

    const tendered = parseFloat(input.value.replace(/,/g, '')) || 0;
    const change = Math.max(0, tendered - total);
    document.getElementById('checkoutChange').innerText = `${companySettings.company_currency_code || '$'} ${formatNumber(change)}`;
}

async function processPayment() {
    const btn = document.getElementById('processPaymentBtn');
    const total = getCartTotal();
    const tenderedRaw = document.getElementById('amountTendered').value.replace(/,/g, '');
    const tendered = parseFloat(tenderedRaw) || 0;
    if (tendered < total) {
        return showToast('Amount tendered is less than total due', 'error');
    }

    btn.disabled = true;
    btn.innerText = 'Processing...';

    const method = document.querySelector('input[name="payment_method"]:checked').value;
    const currentQueue = queues[activeQueueIndex];

    const payload = {
        shift_id: activeShiftId,
        payment_method: method,
        amount_paid: tendered,
        customer_id: currentQueue.customer ? currentQueue.customer.id : null,
        items: currentQueue.cart
    };

    try {
        const res = await fetch('api/sales.php?action=checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        btn.disabled = false;
        btn.innerText = 'CONFIRM PAYMENT';

        if (data.success) {
            showToast('Payment successful!', 'success');
            showReceiptOptions(data.sale_id);
        } else {
            Swal.fire('Payment Failed', data.message || 'Error processing transaction', 'error');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerText = 'CONFIRM PAYMENT';
        console.error("Checkout Error:", err);
        Swal.fire('Server Error', 'Could not complete the transaction. Check your connection.', 'error');
    }
}

function showShiftSales() {
    if (!activeShiftId) return Swal.fire('Error', 'No active shift found.', 'warning');

    const modalEl = document.getElementById('shiftSalesModal');
    if (!modalEl) {
        console.error("Shift sales modal element not found.");
        return;
    }

    fetch(`api/sales.php?action=list_shift_sales&shift_id=${activeShiftId}`)
        .then(res => res.json())
        .then(data => {
            console.log("Shift Sales Data:", data);
            if (data.success) {
                const list = document.getElementById('shiftSalesList');
                
                // Destroy existing DataTable instance if it exists
                if ($.fn.DataTable.isDataTable('#shiftSalesTable')) {
                    $('#shiftSalesTable').DataTable().destroy();
                }

                if (list) {
                    let totalAmount = 0;
                    if (data.data && data.data.length > 0) {
                        list.innerHTML = data.data.map(s => {
                            totalAmount += parseFloat(s.total_amount) || 0;
                            return `
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#${s.id}</td>
                                <td class="small">${new Date(s.sale_date).toLocaleTimeString()}</td>
                                <td class="small"><span class="badge bg-light text-dark border">${s.payment_method}</span></td>
                                <td class="small">${s.customer_name || '<span class="text-muted">Walk-in</span>'}</td>
                                <td class="text-end pe-4 fw-bold">${formatNumber(s.total_amount)}</td>
                            </tr>
                            `;
                        }).join('');
                    } else {
                        list.innerHTML = '';
                    }
                    
                    document.getElementById('shiftSalesTotal').textContent = formatNumber(totalAmount);
                    
                    // Populate Reconciliation cards
                    const opening = parseFloat(data.opening_balance) || 0;
                    const cashSales = parseFloat(data.cash_total) || 0;
                    const expectedTotal = opening + cashSales;

                    document.getElementById('shiftHistoryOpening').textContent = formatNumber(opening);
                    document.getElementById('shiftHistoryTotalAll').textContent = formatNumber(totalAmount);
                    document.getElementById('shiftHistoryTotalCash').textContent = formatNumber(cashSales);
                    document.getElementById('shiftHistoryExpected').textContent = formatNumber(expectedTotal);
                }
                
                // Initialize modern DataTable (Basic without export plugins)
                $('#shiftSalesTable').DataTable({
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    ordering: false,
                    pageLength: 10,
                    language: {
                        emptyTable: "No sales yet for this shift."
                    },
                    footerCallback: function (row, data, start, end, display) {
                        const api = this.api();
                        
                        const intVal = function (i) {
                            return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };

                        const total = api.column(4, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        $(api.column(4).footer()).html(formatNumber(total));
                    }
                });
                
                // Show modal
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                
                // Brute force fallback if it doesn't show
                setTimeout(() => {
                    if (!modalEl.classList.contains('show')) {
                        console.warn("Bootstrap modal failed to show, forcing visibility.");
                        modalEl.style.display = 'block';
                        modalEl.classList.add('show');
                        document.body.classList.add('modal-open');
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }, 500);
            } else {
                Swal.fire('Error', data.message || 'Failed to load sales history.', 'error');
            }
        })
        .catch(err => {
            console.error("Shift sales fetch error:", err);
            Swal.fire('Error', 'Failed to communicate with the server.', 'error');
        });
}

function exportShiftSales(format) {
    if (!activeShiftId) return Swal.fire('Error', 'No active shift found.', 'warning');
    // Offload the heavy lifting to our custom PHP builder script
    window.location.href = `api/export_reports.php?report=shift-sales&shift_id=${activeShiftId}&format=${format}`;
}

function showReceiptOptions(saleId) {
    lastSaleId = saleId;
    
    // 1. Hide the checkout modal immediately
    const checkoutEl = document.getElementById('checkoutModal');
    const checkoutInst = bootstrap.Modal.getInstance(checkoutEl);
    if (checkoutInst) checkoutInst.hide();

    // 2. Clear current queue for next sale immediately
    if (queues.length > 1) {
        removeQueue(activeQueueIndex);
    } else {
        queues[activeQueueIndex].cart = [];
        queues[activeQueueIndex].customer = null;
        saveState();
        renderQueues();
        renderCart();
        updateCustomerDisplay();
    }
    loadProducts(); // Refresh stock

    // 3. Directly trigger Thermal Print
    printReceipt('thermal');
}

function sendToCustomerEmail() {
    const iframe = document.getElementById('receiptIframe');
    const url = iframe.src;
    const saleId = url.split('id=')[1];

    Swal.fire({
        title: 'Send Receipt to Email',
        target: document.getElementById('receiptPreviewModal'),
        input: 'email',
        inputLabel: 'Customer Email Address',
        inputPlaceholder: 'Enter email address',
        showCancelButton: true,
        confirmButtonText: 'Send Email',
        showLoaderOnConfirm: true,
        preConfirm: (email) => {
            return fetch('api/sales.php?action=email_receipt', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sale_id: saleId, email: email })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Sent!', 'The receipt has been emailed successfully.', 'success');
        }
    });
}

function fullscreenReceipt() {
    const iframe = document.getElementById('receiptIframe');
    if (iframe) {
        const printWin = window.open(iframe.src, '_blank');
        printWin.focus();
    }
}

function startNewSale() {
    // Hide all possible receipt modals
    ['receiptPreviewModal', 'receiptModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        }
    });

    // Safety cleanup
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    }, 300);
}

async function printReceipt(type) {
    if (!lastSaleId) return;

    if (type === 'a4') {
        const receiptModalEl = document.getElementById('receiptModal');
        const receiptInst = bootstrap.Modal.getInstance(receiptModalEl);
        if (receiptInst) receiptInst.hide();

        setTimeout(() => {
            const previewEl = document.getElementById('receiptPreviewModal');
            let previewInst = bootstrap.Modal.getInstance(previewEl);
            if (!previewInst) {
                previewInst = new bootstrap.Modal(previewEl);
            }
            previewInst.show();
            // Fallback for bootstrap
            setTimeout(() => {
                if (!previewEl.classList.contains('show')) {
                    previewEl.style.display = 'block';
                    previewEl.classList.add('show');
                    document.body.classList.add('modal-open');
                    if (document.querySelectorAll('.modal-backdrop').length === 0) {
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }
            }, 500);
        }, 300);
        return;
    }

    const res = await fetch(`api/sales.php?action=receipt&id=${lastSaleId}`);
    const data = await res.json();

    if (!data.success) return showToast('Error loading receipt', 'error');

    const sale = data.data;

    let html = '';
    if (type === 'thermal') {
        html = `
            <div style="font-family: monospace; width: 300px; margin: 0 auto; padding: 10px; font-size: 14px;">
                <h2 style="text-align: center; margin-bottom: 5px;">${companySettings.company_name || 'JUSTSALE'}</h2>
                ${companySettings.company_address ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">${companySettings.company_address}</div>` : ''}
                ${(companySettings.company_city || companySettings.company_country) ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">${[companySettings.company_city, companySettings.company_country].filter(Boolean).join(', ')}</div>` : ''}
                ${companySettings.company_phone ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">Tel: ${companySettings.company_phone}</div>` : ''}
                ${companySettings.company_email ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">Email: ${companySettings.company_email}</div>` : ''}
                ${companySettings.company_website ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">Web: ${companySettings.company_website}</div>` : ''}
                ${companySettings.company_tin ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">TIN: ${companySettings.company_tin}</div>` : ''}
                ${companySettings.company_vrn ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">VRN: ${companySettings.company_vrn}</div>` : ''}
                <div style="text-align: center; margin-bottom: 20px; font-weight: bold; margin-top: 5px;">Receipt #${sale.id}</div>
                <div style="border-bottom: 1px dashed #000; margin-bottom: 10px;"></div>
                <table style="width: 100%; border-collapse: collapse;">
                    ${sale.items.map(i => `
                        <tr>
                            <td colspan="3">${i.name}</td>
                        </tr>
                        <tr>
                            <td>${i.quantity}x</td>
                            <td style="text-align: right;">${companySettings.company_currency_code || '$'} ${parseFloat(i.price).toFixed(2)}</td>
                            <td style="text-align: right;">${companySettings.company_currency_code || '$'} ${parseFloat(i.subtotal).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </table>
                <div style="border-bottom: 1px dashed #000; margin: 10px 0;"></div>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px;">
                    <span>TOTAL</span>
                    <span>${companySettings.company_currency_code || '$'} ${parseFloat(sale.total_amount).toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Tendered (${sale.payment_method})</span>
                    <span>${companySettings.company_currency_code || '$'} ${parseFloat(sale.amount_paid).toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Change</span>
                    <span>${companySettings.company_currency_code || '$'} ${parseFloat(sale.change_amount).toFixed(2)}</span>
                </div>
                <div style="text-align: center; margin-top: 20px; font-size: 12px;">
                    ${sale.customer_name ? `Customer: ${sale.customer_name}<br>` : ''}
                    Cashier: ${sale.username}<br>
                    ${sale.sale_date}
                </div>
                <div style="text-align: center; margin-top: 10px;">Thank you for your visit!</div>
            </div>
        `;
    } else {
        // A4 format
        html = `
            <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        ${companySettings.company_logo ? `<img src="${companySettings.company_logo}" style="max-height: 80px; margin-bottom: 10px;">` : ''}
                        <h1 style="color: #4361ee; margin-bottom: 5px; margin-top: 0;">${companySettings.company_name || 'JUSTSALE'}</h1>
                        ${companySettings.company_address ? `<div style="color: #555;">${companySettings.company_address}</div>` : ''}
                        ${(companySettings.company_city || companySettings.company_country) ? `<div style="color: #555;">${[companySettings.company_city, companySettings.company_country].filter(Boolean).join(', ')}</div>` : ''}
                        ${companySettings.company_phone ? `<div style="color: #555;">Tel: ${companySettings.company_phone}</div>` : ''}
                        ${companySettings.company_email ? `<div style="color: #555;">Email: ${companySettings.company_email}</div>` : ''}
                        ${companySettings.company_website ? `<div style="color: #555;">Web: ${companySettings.company_website}</div>` : ''}
                        ${companySettings.company_tin ? `<div style="color: #555;">TIN: ${companySettings.company_tin}</div>` : ''}
                        ${companySettings.company_vrn ? `<div style="color: #555;">VRN: ${companySettings.company_vrn}</div>` : ''}
                    </div>
                    <div style="text-align: right;">
                        <h3 style="color: #666; font-weight: 300; margin-top: 0;">Tax Invoice / Receipt</h3>
                        <strong>Invoice Number:</strong> INV-${sale.id}<br>
                        <strong>Date:</strong> ${sale.sale_date}<br>
                        <strong>Payment Method:</strong> ${sale.payment_method}
                    </div>
                </div>
                
                <div style="margin-top: 40px; border-top: 1px solid #dee2e6; padding-top: 20px;">
                    <strong>Billed To:</strong><br>
                    ${sale.customer_name || 'Walk-in Customer'}<br><br>
                    <strong>Cashier:</strong> ${sale.username}
                </div>
                
                <table style="width: 100%; margin-top: 40px; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 12px; text-align: left;">Item Description</th>
                            <th style="padding: 12px; text-align: center;">Qty</th>
                            <th style="padding: 12px; text-align: right;">Unit Price</th>
                            <th style="padding: 12px; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sale.items.map(i => `
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td style="padding: 12px;">${i.name}</td>
                                <td style="padding: 12px; text-align: center;">${i.quantity}</td>
                                <td style="padding: 12px; text-align: right;">${companySettings.company_currency_code || '$'} ${parseFloat(i.price).toFixed(2)}</td>
                                <td style="padding: 12px; text-align: right;">${companySettings.company_currency_code || '$'} ${parseFloat(i.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <table style="width: 300px;">
                        <tr><td style="padding: 5px 0;">Subtotal</td><td style="text-align: right;">${companySettings.company_currency_code || '$'} ${parseFloat(sale.total_amount).toFixed(2)}</td></tr>
                        <tr><td style="padding: 5px 0; font-weight: bold; font-size: 1.2em; border-top: 2px solid #000;">Total Paid</td><td style="text-align: right; font-weight: bold; font-size: 1.2em; border-top: 2px solid #000;">${companySettings.company_currency_code || '$'} ${parseFloat(sale.total_amount).toFixed(2)}</td></tr>
                    </table>
                </div>
                <div style="margin-top: 50px; text-align: center; color: #888;">Thank you for your business!</div>
            </div>
        `;
    }

    // Use hidden iframe for printing to avoid window focus disruptions that break fullscreen
    let printIframe = document.getElementById('silentPrintIframe');
    if (!printIframe) {
        printIframe = document.createElement('iframe');
        printIframe.id = 'silentPrintIframe';
        printIframe.style.position = 'fixed';
        printIframe.style.right = '0';
        printIframe.style.bottom = '0';
        printIframe.style.width = '0';
        printIframe.style.height = '0';
        printIframe.style.border = '0';
        document.body.appendChild(printIframe);
    }

    const doc = printIframe.contentWindow.document;
    doc.open();
    doc.write(`
        <html>
        <head>
            <title>Print Receipt</title>
            <style>
                @page { margin: 0; }
                body { margin: 0; padding: 10px; font-family: sans-serif; }
                @media print {
                    header, footer { display: none !important; }
                }
            </style>
        </head>
        <body>
            ${html}
            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    doc.close();

    // Immediately return focus to the parent to help keep the print dialog "behind" or skipped
    window.focus();
}
