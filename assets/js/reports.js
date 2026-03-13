// assets/js/reports.js

let currentReport = 'sales';
let reportsDataTable = null;
let appSettings = {};
let reportChart = null;
let distChart = null;

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth();
    if (!user) return;

    // Accounts or Admin check
    if (user.role !== 'Admin' && user.role !== 'Accounts') {
        Swal.fire('Access Denied', 'You do not have permission to view reports.', 'error').then(() => {
            window.location.href = 'admin';
        });
        return;
    }

    appSettings = await getAppSettings() || { company_currency_code: 'USD' };
    
    initFilters();
    loadFilterData();
    setupEventListeners();
    
    // Default report
    runReport();
});

function initFilters() {
    // Set default dates to current month
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const today = now.toISOString().split('T')[0];
    
    document.getElementById('filter-start-date').value = firstDay;
    document.getElementById('filter-end-date').value = today;

    $('.select2-init').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
}

async function loadFilterData() {
    try {
        const [usersRes, customersRes, suppliersRes] = await Promise.all([
            fetch('api/users.php?action=list').catch(() => null),
            fetch('api/customers.php?action=list').catch(() => null),
            fetch('api/suppliers.php?action=list').catch(() => null)
        ]);

        if (usersRes) {
            const data = await usersRes.json();
            if (data.success) {
                const select = document.getElementById('filter-sales-user');
                data.data.forEach(u => {
                    const opt = new Option(u.username, u.id);
                    select.add(opt);
                });
            }
        }

        if (customersRes) {
            const data = await customersRes.json();
            if (data.success) {
                const select = document.getElementById('filter-sales-customer');
                data.data.forEach(c => {
                    const opt = new Option(c.name, c.id);
                    select.add(opt);
                });
            }
        }

        if (suppliersRes) {
            const data = await suppliersRes.json();
            if (data.success) {
                const select = document.getElementById('filter-purchase-supplier');
                data.data.forEach(s => {
                    const opt = new Option(s.name, s.id);
                    select.add(opt);
                });
            }
        }
    } catch (err) {
        console.error('Error loading filters:', err);
    }
}

function setupEventListeners() {
    // Sidebar navigation
    document.querySelectorAll('.report-nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.report-nav-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            const reportType = item.getAttribute('data-report');
            switchReport(reportType);
        });
    });

    // Date preset change
    document.getElementById('filter-date-preset').addEventListener('change', (e) => {
        const preset = e.target.value;
        const customRange = document.querySelectorAll('.custom-date-range');
        
        if (preset === 'custom') {
            customRange.forEach(el => el.style.display = 'block');
        } else {
            customRange.forEach(el => el.style.display = 'none');
            setPresetDates(preset);
        }
    });

    // Run report button
    document.getElementById('btn-run-report').addEventListener('click', runReport);
}

function setPresetDates(preset) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    switch (preset) {
        case 'today':
            break;
        case 'yesterday':
            start.setDate(today.getDate() - 1);
            end.setDate(today.getDate() - 1);
            break;
        case 'last7':
            start.setDate(today.getDate() - 7);
            break;
        case 'thisMonth':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
        case 'lastMonth':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
    }

    document.getElementById('filter-start-date').value = start.toISOString().split('T')[0];
    document.getElementById('filter-end-date').value = end.toISOString().split('T')[0];
}

function switchReport(type) {
    currentReport = type;
    
    // Update Title & Description
    const titles = {
        'sales': { t: 'Sales Intelligence', d: 'Comprehensive tracking and analysis of all transaction data.' },
        'purchases': { t: 'Purchase Analytics', d: 'Monitor procurement costs and supplier performance.' },
        'inventory': { t: 'Inventory Insights', d: 'Valuation, stock levels and movement history.' },
        'profit-loss': { t: 'Profit & Loss (P&L)', d: 'Consolidated financial statement of revenue vs expenses.' }
    };
    
    document.getElementById('active-report-title').innerText = titles[type].t;
    document.querySelector('.reports-main p.text-muted').innerText = titles[type].d;

    // Update Filter Visibility
    document.querySelectorAll('.filter-item').forEach(el => el.style.display = 'none');
    if (type === 'sales') {
        document.querySelectorAll('.filter-sales').forEach(el => el.style.display = 'block');
    } else if (type === 'purchases') {
        document.querySelectorAll('.filter-purchases').forEach(el => el.style.display = 'block');
    }

    runReport();
}

async function runReport() {
    const startDate = document.getElementById('filter-start-date').value;
    const endDate = document.getElementById('filter-end-date').value;
    
    let url = `api/reports.php?action=`;
    let params = `&start_date=${startDate}&end_date=${endDate}`;

    if (currentReport === 'sales') {
        url += 'sales_report';
        const user = document.getElementById('filter-sales-user').value;
        const customer = document.getElementById('filter-sales-customer').value;
        if (user) params += `&user_id=${user}`;
        if (customer) params += `&customer_id=${customer}`;
    } else if (currentReport === 'purchases') {
        url += 'purchase_report';
        const supplier = document.getElementById('filter-purchase-supplier').value;
        if (supplier) params += `&supplier_id=${supplier}`;
    } else if (currentReport === 'profit-loss') {
        url += 'profit_loss';
    } else if (currentReport === 'inventory') {
        // We reuse part of inventory API or add specific report
        url += 'inventory_valuation';
    }

    try {
        const res = await fetch(url + params);
        const data = await res.json();
        
        if (data.success) {
            renderReport(data.data);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        console.error('Report execution failed:', err);
    }
}

function renderReport(data) {
    // Clear summary and chart
    document.getElementById('report-summary').innerHTML = '';
    const chartsCont = document.getElementById('charts-container');
    chartsCont.style.display = 'none';

    if (currentReport === 'sales') {
        renderSalesTable(data);
        renderSalesSummary(data);
    } else if (currentReport === 'purchases') {
        renderPurchasesTable(data);
        renderPurchasesSummary(data);
    } else if (currentReport === 'profit-loss') {
        renderPnL(data);
    } else if (currentReport === 'inventory') {
        renderInventoryValuation(data);
    }
}

function renderInventoryValuation(data) {
    const summaryHtml = `
        <div class="col-md-4">
            <div class="card stat-card card-report p-3 border-start border-4 border-info">
                <div class="text-muted small fw-bold text-uppercase">Inventory Cost Value</div>
                <h4 class="fw-bold mb-0">${formatCurrency(data.total_cost_value)}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card card-report p-3 border-start border-4 border-primary">
                <div class="text-muted small fw-bold text-uppercase">Inventory Retail Value</div>
                <h4 class="fw-bold mb-0">${formatCurrency(data.total_retail_value)}</h4>
            </div>
        </div>
    `;
    document.getElementById('report-summary').innerHTML = summaryHtml;

    const table = document.getElementById('reports-table');
    table.innerHTML = `
        <thead class="bg-light">
            <tr>
                <th>Valuation Metric</th>
                <th class="text-end">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Asset Value (at Cost)</td>
                <td class="text-end fw-bold">${formatCurrency(data.total_cost_value)}</td>
            </tr>
            <tr>
                <td>Total Potential Revenue (at MSRP)</td>
                <td class="text-end fw-bold text-primary">${formatCurrency(data.total_retail_value)}</td>
            </tr>
            <tr class="table-light">
                <td class="fw-bold text-success">POTENTIAL GROSS PROFIT MARGIN</td>
                <td class="text-end fw-bold text-success">${formatCurrency(data.total_retail_value - data.total_cost_value)}</td>
            </tr>
        </tbody>
    `;
    if ($.fn.DataTable.isDataTable('#reports-table')) {
        $('#reports-table').DataTable().destroy();
    }
}


function renderSalesTable(data) {
    const columns = [
        { title: 'Date/Time', data: 'sale_date', render: d => new Date(d).toLocaleString() },
        { title: 'Receipt #', data: 'id', render: d => `<span class="fw-bold text-primary">#${d}</span>` },
        { title: 'User', data: 'username' },
        { title: 'Customer', data: 'customer_name', render: d => d || 'Guest' },
        { title: 'Method', data: 'payment_method', render: d => `<span class="badge bg-light text-dark border">${d}</span>` },
        { title: 'Total', data: 'total_amount', className: 'text-end fw-bold', render: d => formatCurrency(d) }
    ];

    initDataTable(data, columns);
}

function renderSalesSummary(data) {
    const totalRevenue = data.reduce((sum, s) => sum + parseFloat(s.total_amount), 0);
    const totalTransactions = data.length;
    const avgTicket = totalTransactions > 0 ? (totalRevenue / totalTransactions) : 0;

    const summaryHtml = `
        <div class="col-md-3">
            <div class="card stat-card card-report p-3 border-start border-4 border-primary">
                <div class="text-muted small fw-bold text-uppercase">Total Revenue</div>
                <h4 class="fw-bold mb-0 text-primary">${formatCurrency(totalRevenue)}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card card-report p-3 border-start border-4 border-success">
                <div class="text-muted small fw-bold text-uppercase">Transactions</div>
                <h4 class="fw-bold mb-0 text-success">${totalTransactions}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card card-report p-3 border-start border-4 border-info">
                <div class="text-muted small fw-bold text-uppercase">Avg. Ticket Value</div>
                <h4 class="fw-bold mb-0 text-info">${formatCurrency(avgTicket)}</h4>
            </div>
        </div>
    `;
    document.getElementById('report-summary').innerHTML = summaryHtml;
}

function renderPurchasesTable(data) {
    const columns = [
        { title: 'PO Date', data: 'purchase_date', render: d => new Date(d).toLocaleDateString() },
        { title: 'PO #', data: 'id', render: d => `<span class="fw-bold text-dark">PO-${d}</span>` },
        { title: 'Supplier', data: 'supplier_name' },
        { title: 'Created By', data: 'creator' },
        { title: 'Status', data: 'status', render: d => {
            let cls = 'bg-secondary';
            if (d === 'Received') cls = 'bg-success';
            if (d === 'Pending') cls = 'bg-warning text-dark';
            if (d === 'Cancelled') cls = 'bg-danger';
            return `<span class="badge ${cls}">${d}</span>`;
        }},
        { title: 'Total Amount', data: 'total_amount', className: 'text-end fw-bold', render: d => formatCurrency(d) }
    ];

    initDataTable(data, columns);
}

function renderPurchasesSummary(data) {
    const totalSpend = data.filter(p => p.status !== 'Cancelled').reduce((sum, p) => sum + parseFloat(p.total_amount), 0);
    const pendingPO = data.filter(p => p.status === 'Pending').length;

    const summaryHtml = `
        <div class="col-md-3">
            <div class="card stat-card card-report p-3 border-start border-4 border-dark">
                <div class="text-muted small fw-bold text-uppercase">Total Procurement Spend</div>
                <h4 class="fw-bold mb-0">${formatCurrency(totalSpend)}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card card-report p-3 border-start border-4 border-warning">
                <div class="text-muted small fw-bold text-uppercase">Pending Orders</div>
                <h4 class="fw-bold mb-0 text-warning">${pendingPO}</h4>
            </div>
        </div>
    `;
    document.getElementById('report-summary').innerHTML = summaryHtml;
}

function renderPnL(data) {
    const start = data.period.start;
    const end = data.period.end;

    const summaryHtml = `
        <div class="col-md-4">
            <div class="card stat-card card-report p-3 border-start border-4 border-primary">
                <div class="text-muted small fw-bold text-uppercase">Total Sales (Revenue)</div>
                <h4 class="fw-bold mb-0">${formatCurrency(data.total_revenue)}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card card-report p-3 border-start border-4 border-danger">
                <div class="text-muted small fw-bold text-uppercase">Total Outflow (Purchases)</div>
                <h4 class="fw-bold mb-0 text-danger">${formatCurrency(data.total_purchases)}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card card-report p-3 border-start border-4 border-${data.net_profit >= 0 ? 'success' : 'danger'}">
                <div class="text-muted small fw-bold text-uppercase">Net Cash Flow</div>
                <h4 class="fw-bold mb-0 text-${data.net_profit >= 0 ? 'success' : 'danger'}">${formatCurrency(data.net_profit)}</h4>
            </div>
        </div>
    `;
    document.getElementById('report-summary').innerHTML = summaryHtml;

    // Render detailed P&L Table
    const table = document.getElementById('reports-table');
    table.innerHTML = `
        <thead class="bg-light">
            <tr>
                <th class="py-3">Description</th>
                <th class="py-3 text-end">Amount (${appSettings.company_currency_code || 'USD'})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold text-dark">TOTAL REVENUE (Sales)</td>
                <td class="text-end fw-bold text-primary">${formatCurrency(data.total_revenue)}</td>
            </tr>
            <tr>
                <td class="ps-4 text-muted">Cost of Goods Sold (Est.)</td>
                <td class="text-end text-danger">(${formatCurrency(data.cogs)})</td>
            </tr>
            <tr class="table-light">
                <td class="fw-bold">ESTIMATED GROSS PROFIT</td>
                <td class="text-end fw-bold">${formatCurrency(data.gross_profit)}</td>
            </tr>
            <tr><td colspan="2" class="py-4"></td></tr>
            <tr>
                <td class="fw-bold text-dark">ACTUAL CASH OUTFLOW (Purchases)</td>
                <td class="text-end fw-bold text-danger">(${formatCurrency(data.total_purchases)})</td>
            </tr>
            <tr class="${data.net_profit >= 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10'}">
                <td class="fw-bold text-uppercase py-3">NET CASH POSITION</td>
                <td class="text-end fw-bold py-3" style="font-size: 1.2rem;">${formatCurrency(data.net_profit)}</td>
            </tr>
        </tbody>
    `;
    if ($.fn.DataTable.isDataTable('#reports-table')) {
        $('#reports-table').DataTable().destroy();
    }
}

function initDataTable(data, columns) {
    if (reportsDataTable) {
        reportsDataTable.destroy();
        document.getElementById('reports-table').innerHTML = '';
    }

    reportsDataTable = $('#reports-table').DataTable({
        data: data,
        columns: columns,
        dom: 'Bfrtip',
        buttons: [
            {
                text: '<i class="fa-solid fa-file-excel me-1"></i> Excel',
                className: 'btn btn-outline-success btn-sm',
                action: function (e, dt, node, config) {
                    const startDate = document.getElementById('filter-start-date').value;
                    const endDate = document.getElementById('filter-end-date').value;
                    let url = `api/export_reports.php?report=${currentReport}&format=excel&start_date=${startDate}&end_date=${endDate}`;
                    
                    if (currentReport === 'sales') {
                        const user = document.getElementById('filter-sales-user').value;
                        const customer = document.getElementById('filter-sales-customer').value;
                        if (user) url += `&user_id=${user}`;
                        if (customer) url += `&customer_id=${customer}`;
                    } else if (currentReport === 'purchases') {
                        const supplier = document.getElementById('filter-purchase-supplier').value;
                        if (supplier) url += `&supplier_id=${supplier}`;
                    }
                    window.location.href = url;
                }
            },
            {
                text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF',
                className: 'btn btn-outline-danger btn-sm',
                action: function (e, dt, node, config) {
                    const startDate = document.getElementById('filter-start-date').value;
                    const endDate = document.getElementById('filter-end-date').value;
                    let url = `api/export_reports.php?report=${currentReport}&format=pdf&start_date=${startDate}&end_date=${endDate}`;
                    
                    if (currentReport === 'sales') {
                        const user = document.getElementById('filter-sales-user').value;
                        const customer = document.getElementById('filter-sales-customer').value;
                        if (user) url += `&user_id=${user}`;
                        if (customer) url += `&customer_id=${customer}`;
                    } else if (currentReport === 'purchases') {
                        const supplier = document.getElementById('filter-purchase-supplier').value;
                        if (supplier) url += `&supplier_id=${supplier}`;
                    }
                    window.open(url, '_blank');
                }
            },
            {
                extend: 'print',
                text: '<i class="fa-solid fa-print me-1"></i> Print',
                className: 'btn btn-outline-dark btn-sm'
            }
        ],
        pageLength: 25,
        order: [[0, 'desc']],
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search report data..."
        }
    });

    // Style the search box
    $('.dataTables_filter input').addClass('form-control form-control-sm border-0 bg-light px-3 py-2').css('width', '250px');
}

function formatCurrency(num) {
    const code = appSettings.company_currency_code || 'USD';
    const val = parseFloat(num) || 0;
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);
    return `${code} ${formatted}`;
}
