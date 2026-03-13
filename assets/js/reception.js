// assets/js/reception.js

let currentPO = null;
let currentItems = [];
let appSettings = {};

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Inventory');
    if (!user) return;

    appSettings = await getAppSettings() || { company_currency_code: 'USD' };

    // Auto-search if PO ID in URL
    const urlParams = new URLSearchParams(window.location.search);
    const poId = urlParams.get('po_id');
    if (poId) {
        document.getElementById('poSearch').value = poId;
        lookupPO(poId);
    }
});

async function lookupPO(poId = null) {
    const id = poId || document.getElementById('poSearch').value.trim();
    if (!id) return Swal.fire('Error', 'Please enter a PO ID.', 'warning');

    try {
        const res = await fetch(`api/purchases.php?action=details&id=${id}`);
        const data = await res.json();

        if (data.success) {
            currentPO = data.data;

            if (currentPO.status === 'Cancelled' || currentPO.status === 'Received') {
                Swal.fire('PO Status', `This order is already ${currentPO.status}.`, 'info');
                return;
            }

            document.getElementById('poSummary').style.display = 'block';
            document.getElementById('receptionSection').style.display = 'block';
            document.getElementById('dispSupplier').innerText = currentPO.supplier_name;
            document.getElementById('dispTotal').innerText = `${appSettings.company_currency_code} ${new Intl.NumberFormat('en-US', { minimumFractionDigits: 0 }).format(currentPO.total_amount)}`;

            loadPOItems(id);
        } else {
            Swal.fire('Not Found', 'PO not exists.', 'error');
            document.getElementById('poSummary').style.display = 'none';
            document.getElementById('receptionSection').style.display = 'none';
        }
    } catch (e) {
        console.error(e);
    }
}

async function loadPOItems(id) {
    try {
        const res = await fetch(`api/receptions.php?action=po_items&id=${id}`);
        const data = await res.json();
        if (data.success) {
            currentItems = data.data;
            renderItems();
        }
    } catch (e) {
        console.error(e);
    }
}

function renderItems() {
    const tbody = document.getElementById('itemBody');
    tbody.innerHTML = '';

    currentItems.forEach((item, index) => {
        const balance = item.quantity - item.total_received;
        // Pre-fill received_now in the object
        item.received_now = balance;

        const tr = document.createElement('tr');
        tr.className = "align-middle";
        tr.innerHTML = `
            <td class="ps-4">
                <div class="fw-bold">${item.product_name}</div>
                <div class="small text-muted">${item.sku || 'N/A'}</div>
            </td>
            <td class="text-center fw-bold fs-6">${item.quantity}</td>
            <td class="text-center text-muted">${item.total_received}</td>
            <td class="text-center">
                <span class="badge ${balance > 0 ? 'bg-primary' : 'bg-success'} px-3 py-2">${balance}</span>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control fw-bold border p-2" 
                           onchange="updateArrival(${index}, this.value)"
                           id="arrival_${index}" min="0" max="${balance}" value="${balance}">
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if ($.fn.DataTable.isDataTable('#receptionTable')) {
        $('#receptionTable').DataTable().destroy();
    }
    $('#receptionTable').DataTable({
        paging: false,
        ordering: true,
        info: false,
        dom: 'ft'
    });

    document.getElementById('submitReceptionBtn').onclick = finalizeReception;
}

function updateArrival(index, val) {
    currentItems[index].received_now = parseInt(val) || 0;
}

async function finalizeReception() {
    const itemsToRecord = [];
    currentItems.forEach((item) => {
        const arrival = item.received_now || 0;
        if (arrival > 0) {
            itemsToRecord.push({
                purchase_item_id: item.id,
                product_id: item.product_id,
                received_now: arrival,
                cost_price: item.cost_price // From PO
            });
        }
    });

    if (itemsToRecord.length === 0) {
        return Swal.fire('Wait', 'Enter quantity for at least one item.', 'warning');
    }

    const confirm = await Swal.fire({
        title: 'Confirm Delivery',
        text: `Record ${itemsToRecord.length} types of items into stock?`,
        icon: 'question',
        showCancelButton: true
    });

    if (!confirm.isConfirmed) return;

    const payload = {
        purchase_id: currentPO.id,
        items: itemsToRecord,
        notes: document.getElementById('notes').value
    };

    try {
        const res = await fetch('api/receptions.php?action=record', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            await Swal.fire('Success', data.message, 'success');
            window.location.href = 'purchases';
        } else {
            Swal.fire('Failed', data.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Communication failure.', 'error');
    }
}
