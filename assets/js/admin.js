let companySettings = {};

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin'); // Will redirect if not Auth or restrict depending on role inside app.js
    if (!user) return; // redirect happens in app.js

    companySettings = await getAppSettings() || { company_currency_code: '$' };

    // Some roles like Accounts can see reports but maybe not manage users
    if (user.role !== 'Admin') {
        // Hide user management nav
        const userNav = document.querySelector('[href="users"]');
        if (userNav) userNav.style.display = 'none';
    }

    loadKPIs();
    loadSalesSummary();
    loadShiftReports();
    initTrendChart();
});

// settings already loaded in DOMContentLoaded

async function loadKPIs() {
    const res = await fetch('api/reports.php?action=dashboard_stats');
    const data = await res.json();
    if (data.success && data.data) {
        const symbol = companySettings.company_currency_code || '$';
        const stats = data.data;

        document.getElementById('kpiTodaySales').innerText = `${symbol} ${formatCurrency(stats.today_sales)}`;
        document.getElementById('kpiMonthlySales').innerText = `${symbol} ${formatCurrency(stats.mtd_sales)}`;
        document.getElementById('kpiLowStock').innerText = stats.low_stock;
        document.getElementById('kpiAssetValue').innerText = `${symbol} ${formatCurrency(stats.total_cost_value)}`;
        
        document.getElementById('kpiOutOfStock').innerText = stats.out_of_stock;
        document.getElementById('kpiTotalSKUs').innerText = stats.total_skus;
    }
}

async function initTrendChart() {
    const res = await fetch('api/reports.php?action=sales_summary');
    const data = await res.json();
    if (!data.success) return;

    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const labels = data.data.map(r => r.date).reverse();
    const values = data.data.map(r => r.revenue).reverse();

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Revenue',
                data: values,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            const symbol = companySettings.company_currency_code || '$';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += symbol + ' ' + formatCurrency(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { borderDash: [5, 5] },
                    ticks: {
                        callback: function(value) {
                            const symbol = companySettings.company_currency_code || '$';
                            return symbol + ' ' + formatCurrency(value);
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

async function loadSalesSummary() {
    const res = await fetch('api/reports.php?action=sales_summary');
    const data = await res.json();
    if (data.success) {
        const tbody = document.getElementById('salesBody');
        tbody.innerHTML = '';
        const symbol = companySettings.company_currency_code || '$';
        data.data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${row.date}</td>
                <td>${row.total_sales}</td>
                <td class="text-success fw-bold">${symbol} ${formatCurrency(row.revenue)}</td>
            `;
            tbody.appendChild(tr);
        });

        if ($.fn.DataTable.isDataTable('#salesTable')) {
            $('#salesTable').DataTable().destroy();
        }
        $('#salesTable').DataTable({
            dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            pageLength: 5,
            ordering: true,
            order: [[0, 'desc']],
            language: { emptyTable: "No sales data found." }
        });
    }
}

async function loadShiftReports() {
    const res = await fetch('api/reports.php?action=shift_report');
    const data = await res.json();
    if (data.success) {
        const tbody = document.getElementById('shiftsBody');
        tbody.innerHTML = '';
        const symbol = companySettings.company_currency_code || '$';
        data.data.forEach(s => {
            const expectedCash = parseFloat(s.opening_balance) + parseFloat(s.expected_cash || 0);
            const actualCash = s.closing_balance !== null ? parseFloat(s.closing_balance) : null;
            let disc = 0;
            let discHtml = '-';

            if (actualCash !== null) {
                disc = actualCash - expectedCash;
                let colorClass = disc < 0 ? 'text-danger fw-bold' : (disc > 0 ? 'text-warning fw-bold' : 'text-success');
                discHtml = `<span class="${colorClass}">${symbol} ${formatCurrency(disc)}</span>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold text-primary">#${s.id}</td>
                <td><span class="text-muted">${s.username}</span></td>
                <td>${discHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        if ($.fn.DataTable.isDataTable('#shiftsTable')) {
            $('#shiftsTable').DataTable().destroy();
        }
        $('#shiftsTable').DataTable({
            dom: 'rt<"row p-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            pageLength: 5,
            ordering: true,
            order: [[0, 'desc']],
            language: { emptyTable: "No shift reports found." }
        });
    }
}


function exportDashboardReport(report, format) {
    window.location.href = `api/export_reports.php?report=${report}&format=${format}`;
}
