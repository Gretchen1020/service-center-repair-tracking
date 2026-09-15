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

---

## Day 2 — Customer Module

### Completed Today

- `customers.php` — single-page Customer module covering full CRUD (Add, Edit, Delete) plus live search:
  - Self-posting form (mirrors `login.php` pattern): hidden `customer_id` field distinguishes Add vs Update
  - Edit mode triggered via `?edit=ID` query param, prefills the form from the DB
  - Server-side validation: name + mobile required, mobile checked against a regex pattern; errors keep submitted values in the form
  - Delete handled via `action=delete` + `delete_id`, wrapped in a try/catch for PDO error code `23000` (foreign key violation) so a customer with existing job cards can't be deleted — shows "Cannot delete this customer - they still have job cards on record." instead
  - Redirect-after-POST (`Location: customers.php?saved=1` / `?deleted=1`) to avoid duplicate submits on refresh
  - Uses PDO prepared statements throughout (SELECT / INSERT / UPDATE / DELETE)
- `includes/customer_rows.php` — shared partial that renders `<tr>` rows, including the per-row Delete form with a `confirm()` dialog; reused by both the initial page load and the AJAX search endpoint to avoid duplicating markup
- `ajax/search_customers.php` — session-protected AJAX endpoint; returns HTML row fragments filtered by name/mobile (`LIKE` query), or the full list when the search box is empty
- `assets/js/customer-search.js` — debounced (300ms) `keyup` listener on the search box; fetches from the AJAX endpoint and swaps the table body content for live search-as-you-type
- Client-side validation: `required` + `pattern` attributes on name/mobile inputs (native HTML5, consistent with Day 1's approach on the login form)
- CSS additions: `.data-table`, `.success`, `.inline-form`, `.btn-danger`, search input width
- Nav updated (`includes/header.php`) with a "Customers" link
- **Bug fix (found via testing, TC-17):** `includes/auth.php`'s `requireLogin()` used a relative redirect (`Location: login.php`), which resolved incorrectly when called from a subfolder — a logged-out request to `ajax/search_customers.php` redirected to a nonexistent `ajax/login.php` (404) instead of the real login page. Fixed by introducing an `APP_BASE` constant and redirecting to an absolute root-relative path (`APP_BASE . '/login.php'`) instead.

### Design Decisions

- **One page, not three.** Chose a single `customers.php` handling list/search/add/edit/delete rather than separate `add_customer.php`/`edit_customer.php` pages, to keep the flat/simple structure consistent with Day 1 and avoid duplicating the form markup.
- **Live search returns HTML, not JSON.** The AJAX endpoint reuses `includes/customer_rows.php` to render the same `<tr>` markup used on initial page load, so there's exactly one place that defines what a customer row looks like.
- **Delete added despite initial Day 2 scope excluding it**, with FK protection so a customer already referenced by a job card can't be removed and silently orphan that job card.

### Pending / Blocker

- None blocking.
- **Known cosmetic limitation (documented, not yet fixed):** After a successful Edit or Delete, the form card heading still reads "Add Customer" even though the message underneath says "Customer updated successfully" or "Customer deleted successfully" — the heading resets because `$editId` clears on the post-action redirect. Purely cosmetic; edit/delete themselves work correctly. Deferred to a later cleanup pass.
- **Test-environment note (TC-16):** Typing a bare `customers.php` into the browser address bar while logged out triggered a browser web search instead of a real request to the app, initially looking like a failed redirect. Re-tested with the full URL (`http://localhost/service_center/customers.php`) and confirmed `requireLogin()` redirects correctly — not an app defect.

### Testing Evidence

- See `Day2_Customer_Module_Tests.docx`