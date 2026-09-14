# DEVLOG — Service Center Job Card & Repair Tracking System

Wingtrix Engineering Solutions Internship — 10-Day Build

---

## Day 1 — Setup & Login

### Completed Today

- Project folder structure created:
  - `config/` — database connection
  - `database/` — schema + seed SQL
  - `includes/` — shared auth helpers
  - `assets/css/` and `assets/js/` — base styles and client-side helpers
- MySQL schema (`database/schema.sql`) with the **only three allowed tables**:
  - `admins` (id, username, password)
  - `customers` (id, name, mobile, address)
  - `jobs` (id, job_no, customer_id, device_name, model, complaint, technician, estimate, final_cost, paid_amount, status)
- One sample admin seeded with a bcrypt-hashed password (`password_hash()` / `PASSWORD_DEFAULT`)
- PDO connection (`config/db.php`) using prepared statements, `ERRMODE_EXCEPTION`, and `FETCH_ASSOC`
- Session helpers (`includes/auth.php`):
  - `requireLogin()` — redirects unauthenticated users
  - `isLoggedIn()` — simple boolean check
- Admin login (`login.php`) and logout (`logout.php`)
- Protected dashboard placeholder (`dashboard.php`) that only renders when a valid session exists
- Base stylesheet (`assets/css/style.css`)
- Cache-control hardening for protected pages (`Cache-Control: no-store`) plus a small `pageshow` listener (`assets/js/protect-bfcache.js`) to reduce the chance of the browser’s back/forward cache showing a stale dashboard after logout

### Design Decisions

- **Three-table limit strictly enforced.** No `created_at`/`updated_at`, no separate device/technician/payment tables. Status values are stored as a simple string column on `jobs` (will be constrained in application code on Day 5).
- **Password handling.** Sample admin uses `password_hash()`. Login will use `password_verify()`. No plaintext comparison at any point.
- **Session-only auth.** No JWT or tokens — PHP sessions only, matching the stack requirements (PHP 8+, PDO, Sessions).
- **Front-controller not used.** This is a classic multi-page PHP app (not a pure API backend). Each page is its own `.php` file. Auth is enforced by including `requireLogin()` at the top of every protected page.

### Pending / Blocker

- None blocking progress.
- **Known cosmetic limitation (documented, not unresolved):** After logout, pressing the browser Back button can briefly flash a frozen visual snapshot of the dashboard from the browser’s back/forward cache (bfcache). Confirmed this is purely visual — no server request is made and the session is genuinely destroyed. Cache-Control headers + JS `pageshow` listener reduce but do not fully eliminate the flash. Recorded in the testing log rather than left as an open bug.

### Testing Evidence

- See `Day1_Testing_Log.docx`, `Day1_DB_Evidence.docx`, and `Day1_Login_Dashboard_Evidence.docx`