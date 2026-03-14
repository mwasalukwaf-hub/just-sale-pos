/* assets/js/mobile-pos.js */

let productsCache = [];
let categoriesCache = [];
let cart = [];
let selectedCategoryId = 'all';
let paymentMethod = 'Cash';
let selectedCustomer = null;

document.addEventListener('DOMContentLoaded', async () => {
    // Register Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').then(() => console.log("SW Registered"));
    }

    // PWA Installation Detection
    initPWAInstallation();

    // Initial load
    await checkAuth();
    await checkShiftStatus();
    await loadCategories();
    await loadProducts();
    
    // Binds
    document.getElementById('productSearch').addEventListener('input', handleSearch);
    document.getElementById('customerSearch').addEventListener('input', handleCustomerSearch);
    document.getElementById('addCustomerForm').addEventListener('submit', handleAddCustomer);
});

async function checkAuth() {
    try {
        const response = await fetch('api/auth.php?action=me');
        const data = await response.json();
        if (!data.success || !data.user) {
            window.location.href = 'login';
            return null;
        }
        return data.user;
    } catch (e) {
        console.error("Auth check failed", e);
        return null;
    }
}

async function checkShiftStatus() {
    try {
        const res = await fetch('api/shifts.php?action=current&t=' + new Date().getTime());
        const data = await res.json();
        const overlay = document.getElementById('shiftOverlay');
        
        if (data.success && data.shift) {
            overlay.style.display = 'none';
        } else {
            overlay.style.display = 'flex';
        }
    } catch (e) {
        console.error("Shift check failed");
    }
}

async function loadCategories() {
    try {
        const r = await fetch('api/products.php?action=list_categories');
        const data = await r.json();
        if (data.success) {
            categoriesCache = data.data;
            renderCategories();
        }
    } catch (e) { console.error("Load Categories failed", e); }
}

async function loadProducts() {
    try {
        const r = await fetch('api/products.php?action=list');
        const data = await r.json();
        if (data.success) {
            productsCache = data.data;
            renderProducts();
        }
    } catch (e) { console.error("Load Products failed", e); }
}

function renderCategories() {
    const container = document.getElementById('categoryTabs');
    const existingAll = container.querySelector('[data-id="all"]');
    container.innerHTML = '';
    container.appendChild(existingAll);

    existingAll.onclick = () => filterByCategory('all');

    categoriesCache.forEach(cat => {
        const tab = document.createElement('div');
        tab.className = 'category-tab';
        tab.innerText = cat.name;
        tab.dataset.id = cat.id;
        tab.onclick = () => filterByCategory(cat.id);
        container.appendChild(tab);
    });
}

function filterByCategory(id) {
    selectedCategoryId = id;
    document.querySelectorAll('.category-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.id == id);
    });
    renderProducts();
}

function handleSearch(e) {
    const term = e.target.value.toLowerCase();
    renderProducts(term);
}

function renderProducts(searchTerm = '') {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';

    const filtered = productsCache.filter(p => {
        const matchesCategory = selectedCategoryId === 'all' || p.category_id == selectedCategoryId;
        const matchesSearch = p.name.toLowerCase().includes(searchTerm) || p.sku.toLowerCase().includes(searchTerm);
        return matchesCategory && matchesSearch;
    });

    filtered.forEach(p => {
        const item = document.createElement('li');
        item.className = 'list-group-item product-list-item d-flex align-items-center justify-content-between px-3 py-3';
        item.style.borderLeft = 'none';
        item.style.borderRight = 'none';
        item.style.cursor = 'pointer';
        item.onclick = () => addToCart(p);
        item.innerHTML = `
            <div class="fw-bold" style="font-size: 15px; color: #1a1a1a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; padding-right: 15px;">${p.name}</div>
            <div class="fw-bold text-success" style="font-size: 16px;">${formatPrice(p.selling_price)}</div>
        `;
        grid.appendChild(item);
    });
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            qty: 1
        });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Cart is empty</td></tr>';
        updateTotal();
        return;
    }

    container.innerHTML = '';
    cart.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="item-name-cell">${item.name}</span>
            </td>
            <td>${formatPrice(item.price)}</td>
            <td>
                <div class="qty-control">
                    <div class="btn-qty" onclick="updateQty(${index}, -1)">-</div>
                    <span class="qty-val">${item.qty}</span>
                    <div class="btn-qty" onclick="updateQty(${index}, 1)">+</div>
                </div>
            </td>
            <td class="text-center">
                <i class="fa-solid fa-trash-can btn-delete" onclick="removeItem(${index})"></i>
            </td>
        `;
        container.appendChild(tr);
    });
    updateTotal();
    
    // Auto scroll to bottom of cart
    const cartSection = document.querySelector('.pos-cart-section');
    cartSection.scrollTop = cartSection.scrollHeight;
}

function updateQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) {
        cart.splice(index, 1);
    }
    renderCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

// Utility: Custom Mobile Alert/Confirm
function showAlert(message, title = 'Alert', type = 'info') {
    return new Promise(resolve => {
        const modal = document.getElementById('mobileModal');
        const icon = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const msgEl = document.getElementById('modalMessage');
        const primaryBtn = document.getElementById('modalPrimaryBtn');
        const secondaryBtn = document.getElementById('modalSecondaryBtn');

        // Set Icon based on type
        icon.innerHTML = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');
        titleEl.innerText = title;
        msgEl.innerText = message;
        
        secondaryBtn.style.display = 'none';
        primaryBtn.innerText = 'OK';
        primaryBtn.onclick = () => {
            modal.classList.remove('modal-active');
            resolve(true);
        };

        modal.classList.add('modal-active');
    });
}

function showConfirm(message, title = 'Confirm') {
    return new Promise(resolve => {
        const modal = document.getElementById('mobileModal');
        const icon = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const msgEl = document.getElementById('modalMessage');
        const primaryBtn = document.getElementById('modalPrimaryBtn');
        const secondaryBtn = document.getElementById('modalSecondaryBtn');

        icon.innerHTML = '❓';
        titleEl.innerText = title;
        msgEl.innerText = message;
        
        secondaryBtn.style.display = 'block';
        secondaryBtn.innerText = 'Cancel';
        primaryBtn.innerText = 'Confirm';

        primaryBtn.onclick = () => {
            modal.classList.remove('modal-active');
            resolve(true);
        };
        secondaryBtn.onclick = () => {
            modal.classList.remove('modal-active');
            resolve(false);
        };

        modal.classList.add('modal-active');
    });
}

async function clearCart() {
    if (await showConfirm("Are you sure you want to clear all items?", "Clear Cart")) {
        cart = [];
        renderCart();
    }
}

function updateTotal() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    document.getElementById('totalAmount').innerText = formatPrice(total);
}

function formatPrice(p) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(p);
}

// Customer Management
function openCustomerModal() {
    const modal = document.getElementById('customerModal');
    modal.style.display = 'flex';
    modal.classList.add('modal-visible');
    handleCustomerSearch(); // Initial load
}

function closeCustomerModal() {
    const modal = document.getElementById('customerModal');
    modal.classList.remove('modal-visible');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function openAddCustomerModal() {
    const modal = document.getElementById('addCustomerModal');
    modal.style.display = 'flex';
    modal.classList.add('modal-visible');
}

function closeAddCustomerModal() {
    const modal = document.getElementById('addCustomerModal');
    modal.classList.remove('modal-visible');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

async function handleCustomerSearch() {
    const term = document.getElementById('customerSearch').value;
    try {
        const r = await fetch(`api/customers.php?action=list&search=${encodeURIComponent(term)}`);
        const data = await r.json();
        if (data.success) {
            renderCustomerList(data.data);
        }
    } catch (e) { console.error("Customer search failed", e); }
}

function renderCustomerList(customers) {
    const container = document.getElementById('customerList');
    container.innerHTML = '';

    // Walk-in option
    const walkIn = document.createElement('div');
    walkIn.style = 'padding:18px; border-bottom:1px solid #eee; display:flex; align-items:center; cursor:pointer;';
    walkIn.innerHTML = `<i class="fa-solid fa-user-circle me-3" style="font-size:24px; color:#999;"></i> <div><div class="fw-bold">Walk-in Customer</div></div>`;
    walkIn.onclick = () => selectCustomer(null);
    container.appendChild(walkIn);

    customers.forEach(c => {
        const item = document.createElement('div');
        item.style = 'padding:18px 15px; border-bottom:1px solid #f1f3f5; display:flex; align-items:center; justify-content:space-between; cursor:pointer;';
        item.innerHTML = `
            <div style="display:flex; align-items:center;">
                <i class="fa-solid fa-user-circle me-3" style="font-size:26px; color:var(--pos-green);"></i>
                <div class="fw-bold" style="font-size:17px; color:#1a1a1a; letter-spacing:-0.3px;">${c.name}</div>
            </div>
            <div class="fw-bold" style="font-size:14px; color:#666; letter-spacing:0.5px;">${c.mobile || '—'}</div>
        `;
        item.onclick = () => selectCustomer(c);
        container.appendChild(item);
    });
}

function selectCustomer(customer) {
    selectedCustomer = customer;
    const display = document.getElementById('selectedCustomer');
    if (customer) {
        display.innerHTML = `<i class="fa-solid fa-user-circle me-1"></i> ${customer.name}`;
    } else {
        display.innerHTML = `<i class="fa-solid fa-user-circle me-1"></i> Walk-in Customer`;
    }
    closeCustomerModal();
}

// PWA Management
let deferredPrompt;
function initPWAInstallation() {
    const overlay = document.getElementById('pwaInstallOverlay');
    const androidBtn = document.getElementById('androidInstallBtn');
    const iosBox = document.getElementById('iosInstallInstructions');

    // Check if already in standalone mode
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) return;

    // Detection for iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        overlay.style.display = 'flex';
        iosBox.style.display = 'block';
    }

    // Detection for Android/Chrome
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        overlay.style.display = 'flex';
        androidBtn.style.display = 'block';
    });

    androidBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            overlay.style.display = 'none';
        }
        deferredPrompt = null;
    });
}

async function handleAddCustomer(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const r = await fetch('api/customers.php?action=add', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showAlert("Customer registered successfully!", "Success", "success");
            selectCustomer({ id: data.id, name: fd.get('name'), mobile: fd.get('mobile') });
            closeAddCustomerModal();
            closeCustomerModal();
        } else {
            showAlert(data.message, "Error", "error");
        }
    } catch (e) {
        showAlert("Failed to register customer", "Error", "error");
    }
}

// Checkout Logic
function openCheckout() {
    if (cart.length === 0) return;
    const modal = document.getElementById('checkoutModal');
    modal.style.display = 'flex';
    modal.classList.add('modal-visible');
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    document.getElementById('finalTotal').value = formatPrice(total);
}

function closeCheckout() {
    const modal = document.getElementById('checkoutModal');
    modal.classList.remove('modal-visible');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function setPaymentMethod(method) {
    paymentMethod = method;
    document.querySelectorAll('.btn-payment').forEach(btn => {
        btn.classList.toggle('active', btn.innerText.includes(method.toUpperCase()));
    });
}

async function submitOrder() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    
    const orderData = {
        action: 'create',
        items: cart.map(i => ({ product_id: i.id, quantity: i.qty, price: i.price })),
        tax: 0,
        discount: 0,
        total_amount: total,
        payment_method: paymentMethod,
        customer_id: selectedCustomer ? selectedCustomer.id : null
    };

    try {
        const r = await fetch('api/sales.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
        const data = await r.json();
        
        if (data.success) {
            await showAlert("Order has been placed successfully!", "Success", "success");
            const saleId = data.sale_id;
            
            // Fetch full sale data for printing
            const saleRes = await fetch(`api/sales.php?action=receipt&id=${saleId}`);
            const saleData = await saleRes.json();
            
            if (saleData.success) {
                printThermal(saleData.data);
            }

            cart = [];
            renderCart();
            closeCheckout();
        } else {
            showAlert(data.message, "Order Failed", "error");
        }
    } catch (e) {
        showAlert("Server communication error. Please try again.", "Error", "error");
    }
}

async function printThermal(sale) {
    // Load settings first
    const sRes = await fetch('api/settings.php?action=get');
    const sData = await sRes.json();
    const settings = sData.success ? sData.data : {};
    const symbol = settings.company_currency_code || '$';

    const html = `
        <div style="font-family: monospace; width: 300px; margin: 0 auto; padding: 10px; font-size: 14px;">
            <h2 style="text-align: center; margin-bottom: 5px;">${settings.company_name || 'JUSTSALE'}</h2>
            ${settings.company_address ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">${settings.company_address}</div>` : ''}
            ${(settings.company_city || settings.company_country) ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">${[settings.company_city, settings.company_country].filter(Boolean).join(', ')}</div>` : ''}
            ${settings.company_tin ? `<div style="text-align: center; font-size: 12px; margin-bottom: 2px;">TIN: ${settings.company_tin}</div>` : ''}

            ${sale.customer_name ? `
            <div style="border-top: 1px solid #000; margin-top: 10px; padding-top: 8px; text-align: center;">
                <div style="font-weight: bold; font-size: 16px;">${sale.customer_name.toUpperCase()}</div>
                ${sale.customer_tin ? `<div style="font-size: 12px;">TIN: ${sale.customer_tin}</div>` : ''}
            </div>
            ` : ''}

            <div style="text-align: center; margin-bottom: 10px; font-weight: bold; margin-top: 5px;">Receipt #${sale.id}</div>
            <div style="border-bottom: 1px dashed #000; margin-bottom: 10px;"></div>
            <table style="width: 100%; border-collapse: collapse;">
                ${sale.items.map(i => `
                    <tr><td colspan="3">${i.name}</td></tr>
                    <tr>
                        <td>${i.quantity}x</td>
                        <td style="text-align: right;">${symbol} ${parseFloat(i.price).toFixed(2)}</td>
                        <td style="text-align: right;">${symbol} ${parseFloat(i.subtotal).toFixed(2)}</td>
                    </tr>
                `).join('')}
            </table>
            <div style="border-bottom: 1px dashed #000; margin: 10px 0;"></div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px;">
                <span>TOTAL</span>
                <span>${symbol} ${parseFloat(sale.total_amount).toFixed(2)}</span>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 12px;">
                Cashier: ${sale.username}<br>
                ${sale.sale_date}
            </div>
            <div style="text-align: center; margin-top: 10px;">Thank you for your visit!</div>
        </div>
    `;

    let printIframe = document.getElementById('silentPrintIframe');
    if (!printIframe) {
        printIframe = document.createElement('iframe');
        printIframe.id = 'silentPrintIframe';
        printIframe.style.display = 'none';
        document.body.appendChild(printIframe);
    }

    const doc = printIframe.contentWindow.document;
    doc.open();
    doc.write(`<html><body onload="window.print()">${html}</body></html>`);
    doc.close();
}
