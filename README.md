# JUSTSALE POS System

Advanced Inventory & Point of Sale Intelligence System designed for Speed, Security, and Scalability.

## 🚀 Key Features

- **Point of Sale (POS)**: Fast, intuitive terminal for cashiers with multi-tab support and screen locking.
- **Inventory Management**: Comprehensive SKU tracking, asset valuation, and stock movement audit trails.
- **Business Intelligence**: Real-time dashboard with trend analytics and financial KPIs.
- **Pro Reporting**: Unified export engine for PDF and Excel reports (Sales, Purchases, P&L, Inventory).
- **Goods Reception**: Integrated PO request and reception workflow.
- **User Auditing**: Secure shift-based audit logs with discrepancy tracking.

## 🛠️ Technology Stack

- **Backend**: PHP 8.x, MySQL (PDO)
- **Frontend**: Vanilla JavaScript (ES6+), Bootstrap 5, FontAwesome 6, DataTables
- **Libraries**: Chart.js, Select2, Dompdf, PHPMailer

## 🔑 Licensing System

This software is protected by a centralized licensing engine. To distribute the software:

1.  **Server Setup**: Deploy the contents of `server_files/` to your central domain (e.g., `https://licenses.yourdomain.com`).
2.  **Client Configuration**: Update the `$server_url` in `api/licensing_client.php` to point to your central licensing API.
3.  **Activation**: On first run, the software will require a valid license key registered in your central database.

## ⚙️ Installation

1.  Clone the repository to your local web server (XAMPP/WAMP/Laragon).
2.  Import `database.sql` into your MySQL database.
3.  Configure `api/db.php` with your local database credentials.
4.  Run `composer install` to install dependencies.
5.  Access the system via your browser and follow the activation prompts.

## 🔒 Security

- **HWID Correlation**: Software is locked to the specific hardware/environment of the customer.
- **Heartbeat Verification**: Periodic background checks with the central server.
- **RBAC**: Role-based access control (Admin, Accounts, Cashier).

## 📄 License

Professional Edition. Copyright (c) 2026. Powered by Franklin.
