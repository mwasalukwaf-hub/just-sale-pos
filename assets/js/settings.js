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

    // Update listeners
    document.getElementById('btn-check-updates').addEventListener('click', checkUpdates);
    document.getElementById('btn-start-update').addEventListener('click', startUpdate);
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
                if (document.getElementById('receipt_customer_pos')) {
                    document.getElementById('receipt_customer_pos').value = s.receipt_customer_pos || 'top';
                }
                if (document.getElementById('receipt_show_tin')) {
                    document.getElementById('receipt_show_tin').value = s.receipt_show_tin || 'yes';
                }
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

async function checkUpdates() {
    const btn = document.getElementById('btn-check-updates');
    const statusText = document.getElementById('update-status-text');
    const detailsDiv = document.getElementById('update-details');
    const originalContent = btn.innerHTML;

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Checking...';
    btn.disabled = true;

    try {
        const res = await fetch('api/updater.php?action=check');
        const data = await res.json();

        if (data.update_available) {
            statusText.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> A new update (${data.latest_version}) is available!</span>`;
            document.getElementById('new-version-number').innerText = data.latest_version;
            document.getElementById('new-version-changelog').innerText = data.changelog;
            detailsDiv.style.display = 'block';
            
            window.latestUpdateInfo = data; // Store globally
        } else {
            statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check"></i> You are running the latest version.</span>';
            detailsDiv.style.display = 'none';
        }
    } catch (err) {
        Swal.fire('Error', 'Update server is currently unavailable.', 'error');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

async function startUpdate() {
    if (!window.latestUpdateInfo) return;

    const confirm = await Swal.fire({
        title: 'System Update',
        text: 'This will download and replace system files. Please ensure you have a database backup before proceeding.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed with update',
        cancelButtonText: 'Not now'
    });

    if (!confirm.isConfirmed) return;

    const btn = document.getElementById('btn-start-update');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Downloading update...';
    btn.disabled = true;

    try {
        // Step 1: Download
        const fd = new FormData();
        fd.append('url', window.latestUpdateInfo.download_url);
        fd.append('version', window.latestUpdateInfo.latest_version);

        const dlRes = await fetch('api/updater.php?action=download', {
            method: 'POST',
            body: fd
        });
        const dlData = await dlRes.json();

        if (!dlData.success) {
            throw new Error(dlData.message);
        }

        // Step 2: Apply
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Applying updates... (Don\'t close this window)';
        
        const applyFd = new FormData();
        applyFd.append('zip_path', dlData.zip_path);

        const applyRes = await fetch('api/updater.php?action=apply', {
            method: 'POST',
            body: applyFd
        });
        const applyData = await applyRes.json();

        if (applyData.success) {
            await Swal.fire({
                title: 'Update Successful!',
                text: applyData.message,
                icon: 'success'
            });
            window.location.reload();
        } else {
            throw new Error(applyData.message);
        }

    } catch (err) {
        Swal.fire('Update Failed', err.message, 'error');
        btn.innerHTML = '<i class="fa-solid fa-download me-1"></i> Download & Install Update';
        btn.disabled = false;
    }
}
