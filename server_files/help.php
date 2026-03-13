<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual & Documentation | JUSTSALE POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --dark: #0f172a;
            --light: #f8fafc;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--light); color: var(--dark); }
        
        .docs-sidebar {
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            padding: 20px;
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .nav-link-docs {
            display: block;
            padding: 8px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .nav-link-docs:hover { background: rgba(67, 97, 238, 0.05); color: var(--primary); }
        .nav-link-docs.active { background: var(--primary); color: white; font-weight: 600; }
        
        .docs-content {
            background: white;
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            line-height: 1.7;
        }
        .docs-section { margin-bottom: 80px; scroll-margin-top: 100px; }
        .docs-section h2 { font-weight: 800; margin-bottom: 25px; color: var(--dark); border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .docs-section h3 { font-weight: 600; margin-top: 40px; }
        
        .tip-box {
            background: #f0f7ff;
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 0.95rem;
        }
        
        code { background: #f1f5f9; color: #e11d48; padding: 2px 6px; border-radius: 4px; }
        .img-doc { border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); margin: 30px 0; border: 1px solid #e2e8f0; }
        
        .navbar-brand { font-weight: 800; color: var(--primary) !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom py-3 sticky-top">
    <div class="container">
        <a class="navbar-brand fs-4" href="index.php"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE <span class="text-dark opacity-50 fw-normal fs-6">| Docs</span></a>
        <div class="ms-auto d-flex align-items-center">
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2">Back to Website</a>
            <a href="login.php" class="btn btn-sm btn-primary rounded-pill px-3">Portal Login</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="docs-sidebar">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">Introduction</h6>
                <a href="#welcome" class="nav-link-docs active">Welcome to JUSTSALE</a>
                <a href="#installation" class="nav-link-docs">Installation Wizard</a>
                <a href="#activation" class="nav-link-docs">Activating Your License</a>
                
                <h6 class="fw-bold text-uppercase small text-muted mb-3 mt-4">Core Modules</h6>
                <a href="#pos" class="nav-link-docs">POS Terminal (Selling)</a>
                <a href="#inventory" class="nav-link-docs">Inventory & Stock</a>
                <a href="#procurement" class="nav-link-docs">Procurement (PO)</a>
                <a href="#reports" class="nav-link-docs">Reports & Analytics</a>
                
                <h6 class="fw-bold text-uppercase small text-muted mb-3 mt-4">Security & Settings</h6>
                <a href="#users" class="nav-link-docs">User Roles & Access</a>
                <a href="#settings" class="nav-link-docs">System Settings</a>
                <a href="#backup" class="nav-link-docs">Data Security</a>
                
                <h6 class="fw-bold text-uppercase small text-muted mb-3 mt-4">Support</h6>
                <a href="#faq" class="nav-link-docs">FAQ</a>
                <a href="#contact" class="nav-link-docs">Get Technical Help</a>
            </div>
        </div>

        <!-- Documentation Content -->
        <div class="col-lg-9">
            <div class="docs-content">
                
                <!-- Section: Welcome -->
                <section class="docs-section" id="welcome">
                    <h2>Welcome to JUSTSALE</h2>
                    <p class="lead">Congratulations on choosing the most intelligent POS system for your business. JUSTSALE is designed to simplify your retail operations, tracking every cent and every item in real-time.</p>
                    <p>This manual will guide you through setting up the system, managing your inventory, and maximizing your sales efficiency.</p>
                    <div class="tip-box">
                        <strong><i class="fa-solid fa-lightbulb me-2"></i> Pro Tip:</strong> You can access this manual anytime by visiting our portal or clicking "Help" in your system settings.
                    </div>
                </section>

                <!-- Section: Installation -->
                <section class="docs-section" id="installation">
                    <h2>Installation Wizard</h2>
                    <p>When you start JUSTSALE for the first time, you will be greeted by the <strong>Setup Wizard</strong>. This process automates the database configuration so you don't have to touch any code.</p>
                    
                    <h3>Step 1: Database Setup</h3>
                    <ul class="text-muted">
                        <li>Enter your Database Host (usually <code>localhost</code>).</li>
                        <li>Enter your Database Name, Username, and Password.</li>
                        <li>Click "Test Connection" to ensure everything is correct.</li>
                    </ul>

                    <h3>Step 2: Business Identity</h3>
                    <p>Enter your Company Name and your preferred Currency (e.g., TZS, USD). This information will appear on your receipts and reports.</p>

                    <h3>Step 3: Master Admin</h3>
                    <p>Create your primary Administrator account. Choose a strong password. This account will have full power over the system.</p>
                </section>

                <!-- Section: Activation -->
                <section class="docs-section" id="activation">
                    <h2>Activating Your License</h2>
                    <p>JUSTSALE requires a valid license key to operate. Licensing is bound to your hardware, ensuring your data remains on your designated machine.</p>
                    <ol class="text-muted">
                        <li>Copy your <strong>Installation ID</strong> (HWID) from the activation screen.</li>
                        <li>Go to the <a href="login.php">JUSTSALE Portal</a> and purchase or request a key.</li>
                        <li>Enter the provided License Key into the software.</li>
                        <li>Click "Activate System" to start using the POS.</li>
                    </ol>
                    <div class="alert alert-warning rounded-4 p-3 small">
                        <strong>Note:</strong> Activation requires an internet connection. Once activated, the system can run offline for up to <strong>30 days</strong>. After this period, a brief internet connection is required to refresh the license sync.
                    </div>
                </section>

                <!-- Section: POS -->
                <section class="docs-section" id="pos">
                    <h2>POS Terminal (Selling)</h2>
                    <p>The POS screen is where the magic happens. It's built for speed and supports touchscreens, barcode scanners, and keyboard shortcuts.</p>
                    
                    <h3>How to Sell:</h3>
                    <ul>
                        <li><strong>Find Products:</strong> Use the search bar or scan a barcode.</li>
                        <li><strong>Quantities:</strong> Click an item in the cart to increase quantity or change price (if authorized).</li>
                        <li><strong>Custom Amounts:</strong> Use the on-screen keypad for fast number entry.</li>
                        <li><strong>Hold Sales:</strong> You can open multiple tabs (Orders) for different customers at the same time.</li>
                        <li><strong>Checkout:</strong> Click the green "Checkout" button, enter the amount received, and print the receipt.</li>
                    </ul>
                </section>

                <!-- Section: Inventory -->
                <section class="docs-section" id="inventory">
                    <h2>Inventory & Stock</h2>
                    <p>Accurate stock tracking is the backbone of your profit. JUSTSALE manages your products across categories.</p>
                    <h3>Managing Products:</h3>
                    <ul>
                        <li><strong>Stock-In:</strong> Use the "Goods Reception" module to add new stock when deliveries arrive.</li>
                        <li><strong>Low Stock Alerts:</strong> Items below their minimum threshold will be highlighted in red.</li>
                        <li><strong>Category Control:</strong> Group items (e.g., Drinks, Snacks) to organize your POS and filter reports.</li>
                    </ul>
                </section>

                <!-- Section: Reports -->
                <section class="docs-section" id="reports">
                    <h2>Reports & Analytics</h2>
                    <p>Knowledge is power. The Reports section gives you deep insight into your business performance.</p>
                    <ul>
                        <li><strong>Daily Sales:</strong> Total revenue, net profit, and tax collection.</li>
                        <li><strong>Purchase vs Sales:</strong> See if your margins are healthy.</li>
                        <li><strong>Attendance:</strong> Track when your users clock in and out for their shifts.</li>
                        <li><strong>Inventory Value:</strong> The total worth of all items currently on your shelves.</li>
                    </ul>
                </section>

                <!-- Section: User Roles -->
                <section class="docs-section" id="users">
                    <h2>User Roles & Access</h2>
                    <p>Protect your business by restricting access to sensitive data.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Admin</strong></td>
                                    <td>Full access to everything, including settings and user deletion.</td>
                                </tr>
                                <tr>
                                    <td><strong>Accounts</strong></td>
                                    <td>Can view all reports, manage inventory, but cannot sell or edit users.</td>
                                </tr>
                                <tr>
                                    <td><strong>Cashier</strong></td>
                                    <td>Can only use the POS screen and update their own profile. Restricted from seeing profit metrics.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <hr class="my-5">
                <div class="text-center">
                    <p class="text-muted">Still have questions?</p>
                    <a href="index.php#contact" class="btn btn-primary rounded-pill px-4">Contact Franklin Support</a>
                </div>

            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="small opacity-50 mb-0">&copy; 2026 JUSTSALE POS Documentation. Powered by Franklin.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Smooth scroll for anchors
    document.querySelectorAll('.nav-link-docs').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            document.querySelector(targetId).scrollIntoView({ behavior: 'smooth' });
            
            // Update active state
            document.querySelectorAll('.nav-link-docs').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Highight active link on scroll
    window.addEventListener('scroll', () => {
        let current = "";
        const sections = document.querySelectorAll(".docs-section");
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 150) {
                current = section.getAttribute("id");
            }
        });

        document.querySelectorAll(".nav-link-docs").forEach(nav => {
            nav.classList.remove("active");
            if (nav.getAttribute("href") === "#" + current) {
                nav.classList.add("active");
            }
        });
    });
</script>
</body>
</html>
