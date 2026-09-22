# Service Center Job Card & Repair Tracking System

A simple web application for a device service center to register customers, create repair job cards, track repair status, record cost/payment, view a dashboard, and print job cards.

Built as a 10-day internship task for **Wingtrix Engineering Solutions**.

## Tech Stack

- **Frontend:** HTML5, CSS3, vanilla JavaScript
- **Backend:** PHP 8+, PDO, PHP Sessions
- **Database:** MySQL (3 tables only — `admins`, `customers`, `jobs`)
- **Environment:** XAMPP / WAMP / local Apache + MySQL

## Features

| Module | Description |
|---|---|
| Admin Login | Session-protected login/logout |
| Customer Management | Add, edit, search, delete customers |
| Job Card | Create a repair job card with device, complaint, technician, and estimate; auto-generated unique job number (`JC-0001` style) |
| Job List & Details | View all jobs (with live search) and a full job details page |
| Repair Status | Update status through Received → Checking → Repairing → Ready → Delivered, with a status filter on the job list |
| Cost & Payment | Record final cost and paid amount; balance is calculated in PHP, not stored |
| Dashboard | Live counts of customers, jobs by status, and total pending payment |
| Print Job Card | Print-friendly job card page (A4-ready) |

## Database

Only **3 tables**, kept deliberately simple:

- **admins** — `id, username, password`
- **customers** — `id, name, mobile, address`
- **jobs** — `id, job_no, customer_id (FK → customers.id), device_name, model, complaint, technician, estimate, final_cost, paid_amount, status`

No separate device/technician/history/payment tables, and no `created_at`/`updated_at` fields — this matches the project's mandatory rules. Balance (`final_cost - paid_amount`) is always calculated in PHP, never stored.

The full schema is provided in `docs/schema.sql`, with sample data in `docs/sample_data.sql`.

## Folder Structure

```
service_center/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── print.css
│   └── js/
│       ├── customer-search.js
│       ├── job-search.js
│       ├── jobcard-validate.js
│       ├── status-confirm.js
│       ├── cost-payment.js
│       ├── job-print.js
│       └── protect-bfcache.js
├── ajax/
│   ├── search_customers.php
│   └── search_jobs.php
├── config/
│   └── db.php
├── includes/
│   ├── auth.php
│   ├── header.php
│   ├── footer.php
│   ├── customer_rows.php
│   └── job_rows.php
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── customers.php
├── jobcard.php
├── jobs.php
├── job_details.php
├── print_job.php
├── docs/
│   ├── schema.sql
│   ├── sample_data.sql
│   └── DEVLOG.md
└── README.md
```

## Setup Instructions

1. Copy the project folder into your XAMPP/WAMP `htdocs` directory.
2. Start Apache and MySQL.
3. Open phpMyAdmin and use **Import** to run `docs/schema.sql`, then `docs/sample_data.sql`, in that order. `docs/schema.sql` creates the database itself (`service_center_db`), so you don't need to create one first.
4. Open `config/db.php` and confirm the database host, name, username, and password match your local MySQL setup.
5. Visit `http://localhost/service_center/` in your browser.
6. Log in with the sample admin account:
   - **Username:** `admin`
   - **Password:** `admin123`

## Usage Guide

1. **Login** with the admin credentials above.
2. **Add a customer** on the Customers page (name and mobile are required).
3. **Create a job card** from the Job Card page — select the customer, enter device details and an estimate. A unique job number is generated automatically.
4. **Track status** from the job details page — update status as the repair progresses (Received → Checking → Repairing → Ready → Delivered).
5. **Record payment** on the same job details page — enter the final cost and paid-to-date amount; the balance updates automatically.
6. **View the dashboard** for a live overview of customers, jobs by status, and pending payments.
7. **Print a job card** from the job details page — opens a print-ready page in a new tab.

## Sample Data

`docs/schema.sql` includes one sample admin account. `docs/sample_data.sql` adds at least 5 sample customers with job cards covering a range of statuses and payment states, for demo/testing purposes.

## Testing

Each module was tested against a written test plan (`Day{N}_*_Module_Testing.docx`) covering functional cases, validation, and login protection. See `docs/DEVLOG.md` for the day-by-day development log, decisions, and testing evidence.

## Author

Gretchen — Wingtrix Engineering Solutions internship submission.