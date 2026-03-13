/* assets/js/mobile-pos.js */

let productsCache = [];
let categoriesCache = [];
let cart = [];
let selectedCategoryId = 'all';
let paymentMethod = 'Cash';

document.addEventListener('DOMContentLoaded', async () => {
    updateTime();
    setInterval(updateTime, 60000);

    // Initial load
    await checkAuth();
    await checkShiftStatus();
    await loadCategories();
    await loadProducts();
    
    // Binds
    document.getElementById('productSearch').addEventListener('input', handleSearch);
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
        const r = await fetch('api/inventory.php?action=list_categories');
        const data = await r.json();
        if (data.success) {
            categoriesCache = data.categories;
            renderCategories();
        }
    } catch (e) { console.error("Load Categories failed", e); }
}

async function loadProducts() {
    try {
        const r = await fetch('api/products.php?action=list');
        const data = await r.json();
        if (data.success) {
            productsCache = data.products;
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
        const card = document.createElement('div');
        card.className = 'product-card';
        card.onclick = () => addToCart(p);
        card.innerHTML = `
            <img src="${p.image_path || 'assets/img/placeholder.png'}" class="product-img" onerror="this.src='assets/img/placeholder.png'">
            <div class="product-info">
                <span class="product-title">${p.name}</span>
                <span class="product-variant">${p.category_name || ''}</span>
                <span class="product-price">${formatPrice(p.selling_price)}</span>
            </div>
        `;
        grid.appendChild(card);
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
            qty: 1,
            image: product.image_path
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

function clearCart() {
    if (confirm("Clear cart?")) {
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

function updateTime() {
    const now = new Date();
    let hours = now.getHours();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; 
    const minutes = now.getMinutes().toString().padStart(2, '0');
    document.getElementById('pos-time').innerText = `${hours}:${minutes} ${ampm}`;
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
        customer_id: null
    };

    try {
        const r = await fetch('api/sales.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
        const data = await r.json();
        
        if (data.success) {
            alert("Order Placed Successfully!");
            cart = [];
            renderCart();
            closeCheckout();
            
            // Auto print if desktop silent printing is on, 
            // but on mobile we usually use the device's thermal printer via SDK or window.print()
            window.print(); 
        } else {
            alert("Error: " + data.message);
        }
    } catch (e) {
        alert("Server error. Please try again.");
    }
}
