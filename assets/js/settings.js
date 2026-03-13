// assets/js/settings.js

document.addEventListener('DOMContentLoaded', async () => {
    const user = await checkAuth('Admin'); // Only Admins can edit settings
    if (!user) return;

    await loadSettings();

    // Handle hash-based tab switching
    const hash = window.location.hash;
    if (hash) {
        const tabEl = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }

    document.getElementById('settingsForm').addEventListener('submit', saveSettings);

    const testSmtpBtn = document.getElementById('testSmtpBtn');
    if (testSmtpBtn) {
        testSmtpBtn.addEventListener('click', async () => {
            const btn = testSmtpBtn;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Testing...';
            btn.disabled = true;

            try {
                const res = await fetch('api/settings.php?action=test_smtp', {
                    method: 'POST',
                    body: new FormData(document.getElementById('settingsForm'))
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Connection failed', 'error');
            } finally {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }
});

async function loadSettings() {
    try {
        const res = await fetch('api/settings.php?action=get');
        const data = await res.json();
        if (data.success && data.data) {
            const s = data.data;
            document.getElementById('company_name').value = s.company_name || '';
            document.getElementById('company_tin').value = s.company_tin || '';
            document.getElementById('company_vrn').value = s.company_vrn || '';
            document.getElementById('company_address').value = s.company_address || '';
            document.getElementById('company_phone').value = s.company_phone || '';
            document.getElementById('company_city').value = s.company_city || '';
            document.getElementById('company_country').value = s.company_country || '';
            document.getElementById('company_email').value = s.company_email || '';
            document.getElementById('company_website').value = s.company_website || '';
            document.getElementById('company_currency_code').value = s.company_currency_code || 'USD';
            document.getElementById('company_currency_name').value = s.company_currency_name || 'US Dollar';

            // SMTP Fields
            if (document.getElementById('smtp_host')) {
                document.getElementById('smtp_host').value = s.smtp_host || '';
                document.getElementById('smtp_port').value = s.smtp_port || '587';
                document.getElementById('smtp_user').value = s.smtp_user || '';
                document.getElementById('smtp_pass').value = s.smtp_pass || '';
                document.getElementById('smtp_encryption').value = s.smtp_encryption || 'tls';
                document.getElementById('smtp_from_email').value = s.smtp_from_email || '';
                document.getElementById('smtp_from_name').value = s.smtp_from_name || '';
            }

            // Inventory Setup
            if (document.getElementById('sku_template')) {
                document.getElementById('sku_template').value = s.sku_template || 'PROD-{MMYYYY}-00000';
                document.getElementById('sku_next_number').value = s.sku_next_number || '1';
            }

            // Receipt Setup
            if (document.getElementById('receipt_header')) {
                document.getElementById('receipt_header').value = s.receipt_header || '';
                document.getElementById('receipt_footer').value = s.receipt_footer || '';
                document.getElementById('tax_percent').value = s.tax_percent || '0';
                document.getElementById('receipt_show_logo').value = s.receipt_show_logo || 'yes';
            }

            if (s.company_logo && s.company_logo !== '') {
                document.getElementById('logoPreview').src = s.company_logo;
            }
        }
    } catch (e) {
        console.error("Error loading settings", e);
    }
}

function previewLogo(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('logoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...';
    btn.disabled = true;

    const fd = new FormData(e.target);

    try {
        const res = await fetch('api/settings.php?action=update', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message);
            await loadSettings(); // Reload to refresh image path if changed
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Connection failed', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
