# DEVLOG — Service Center Job Card & Repair Tracking System

Wingtrix Engineering Solutions Internship — 10-Day Build

---

## Day 1 — Setup & Login

### Completed

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

### Completed 

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

---

## Day 3 — Job Card Module

### Completed

- `jobcard.php` — single self-posting page for creating a new job card:
  - Customer dropdown populated from the `customers` table (name + mobile) — a job card can only be created against an existing customer
  - Required fields: customer selection, device name, complaint, estimate (non-negative number); model and technician are optional
  - Server-side validation with an inline error list; submitted values are preserved in the form on error
  - New job inserted with `status = 'Received'`, `final_cost = 0`, `paid_amount = 0` (final cost/payment handled on Day 6)
  - Unique `job_no` generated right after insert: a placeholder (`'TEMP'`) is written first, then updated to a `JC-0001`-style number derived from the new row's auto-increment id via `lastInsertId()` — avoids a separate sequence table, keeping to the 3-table limit
  - Redirect-after-POST (`jobcard.php?success=JC-000X`), same pattern as `customers.php`, to avoid duplicate submits on refresh
  - Empty-customers edge case handled: the form is hidden and a message links to the Customer module if no customers exist yet
  - Uses PDO prepared statements throughout (INSERT + UPDATE)
- `assets/js/jobcard-validate.js` — client-side check for required fields (customer, device name, complaint) and a valid non-negative estimate before allowing submit

### Design Decisions

- **Single self-posting page again**, consistent with Day 1/2's flat structure — no separate create/list/detail files yet (job list + detail view is Day 4 scope).
- **Customer picked via a plain `<select>` dropdown**, not the Day 2 live-search AJAX pattern — simplest fit for the current dataset size; can be revisited if the customer list grows large.
- **`job_no` generated from the row's own auto-increment id** (insert-then-update) instead of a separate counter/sequence table, to respect the 3-table limit.

### Pending / Blocker

- No job list/detail view yet to confirm saved jobs visually (Day 4) — verified in the interim via direct SQL (`SELECT id, job_no FROM jobs ORDER BY id`).

### Testing Evidence

- See `Day3_JobCard_Module_Testing.docx` 

---

## Day 4 — Job List & Details Module

### Completed

- `jobs.php` — job list page joined against `customers` so each row shows a readable name/mobile instead of a bare `customer_id`; columns: Job No, Customer, Device, Status, Action (View)
- `includes/job_rows.php` — shared partial rendering `<tr>` rows, reused by both the initial page load and the AJAX search endpoint (same pattern as Day 2's `customer_rows.php`)
- `ajax/search_jobs.php` — session-protected AJAX endpoint; `LIKE` search across `job_no`, customer `name`, and customer `mobile` via the same `jobs` ⋈ `customers` join, returning HTML row fragments `assets/js/job-search.js` — debounced (300ms) live search-as-you-type, mirroring `customer-search.js`
- `job_details.php` — single job's full record (`?id=X`): customer name/mobile/address, device, model, complaint, technician, estimate, final cost, paid amount, and balance; shows a "Job not found." message with a back-link for a bad or missing `id`
- Nav updated (`includes/header.php`) with a "Jobs" link; one CSS rule added so `#jobSearch` shares `#customerSearch`'s width

### Design Decisions

- **Reused the Day 2 customer-module patterns** (shared row partial, AJAX returning HTML not JSON, debounced search) rather than inventing a new approach, to keep the codebase consistent.
- **Balance is calculated in PHP** (`final_cost - paid_amount`) at display time, not stored as a column — per the project's no-derived-columns rule. Since editing final cost/paid amount is Day 6 scope, TC-09 (balance calculation) was tested against values set directly via SQL.
- **No status filter/dropdown on the list page yet** — deliberately deferred to Day 5 (Repair Status), to keep Day 4 scoped to list + search + details.

### Pending / Blocker

- None blocking.

### Pending Resolved

- **Bug: "No jobs found" message never appeared on a zero-result search.**
`job-search.js` checked `html.trim() === ''`, but `job_rows.php`'s empty branch always prints an HTML comment rather than a true empty string, so the check never passed. Fixed by checking `tableBody.querySelector('tr')` against the actual DOM after insertion instead of the raw response text. Caught via TC-07.
- **Bug: `customer-search.js` (Day 2) had the identical "No jobs found" detection bug** 
as the first item above — `html.trim() === ''` against `customer_rows.php`'s identical comment-on-empty output. Confirmed and fixed alongside the Day 4 fix, using the same `tableBody.querySelector('tr')` check.

### Testing Evidence

- See `Day4_JobList_Module_Testing.docx` (TC-01 to TC-14, all Pass)

---

## Naming Conventions

- File names use **underscores**: `job_details.php`, `customer_rows.php`, `search_jobs.php`
- JS file names use **hyphens**: `customer-search.js`, `job-search.js`, `jobcard-validate.js`

---

## Day 5 — Repair Status Module

### Completed

- `job_details.php` — added a status update section below the details table:
  - `<select>` of the five allowed statuses (Received/Checking/Repairing/Ready/Delivered), defined as a PHP whitelist array (no new table, per the 3-table limit)
  - Self-posting form, redirect-after-POST (`job_details.php?id=X&updated=1`), matching the pattern from `customers.php`/`jobcard.php`
  - Server-side validation via `in_array($_POST['status'], $statuses, true)` — rejects any value outside the whitelist with an "Invalid status selected." error, protecting against a tampered/raw POST bypassing the dropdown
- `assets/js/status-confirm.js` — `confirm()` dialog before the status-update form submits, naming the chosen status
- `jobs.php` — added a "Filter by status" `<select>` (GET-based `?status=X`, `onchange="this.form.submit()"`), using the same whitelist; query switches between the full list and a `WHERE j.status = ?` prepared statement
- `ajax/search_jobs.php` — updated to accept the same `status` param as the filter, combining it with the existing search-term `LIKE` condition via a dynamically-built `WHERE` clause (both conditions optional, ANDed together when both present) — live search now respects an active status filter instead of ignoring it
- `assets/js/job-search.js` — updated to read the current filter dropdown's value and include it in every AJAX search request alongside the search term

### Design Decisions

- **Status whitelist kept as a PHP array**, not a lookup table — consistent with the 3-table limit, same approach as `jobcard.php`'s job_no generation avoiding an extra sequence table.
- **Filter implemented as a full GET page reload**, not AJAX — simplest fit, and it composes with the existing AJAX search endpoint by sharing the same `status` query param rather than needing a second, separate filtering mechanism.
- **AbortController over a manual request-counter** for cancelling superseded search requests — native browser API, no extra state to track.

### Pending / Blocker

- None blocking.

### Pending Resolved

- **Bug: live search ignored the active status filter.** `ajax/search_jobs.php` ran its own query with no knowledge of the filter, so typing in the search box while a status filter was active showed results across all statuses instead of just the filtered one. Fixed by having `job-search.js` send the filter's current value alongside the search term, and `search_jobs.php` build its `WHERE` clause from both together.
- **Bug: fast typing/deleting in the search box could leave a stale result on screen.** Two AJAX requests fired close together (e.g. one for a typed letter, one right after deleting it) could resolve out of order — an older, slower response landing after a newer one and overwriting it with wrong results. Only reproduced at real typing speed, not when stepping through slowly in DevTools. Fixed with `AbortController`: any in-flight request is cancelled the moment a newer one is about to fire, so an older response can never land after a newer one.

### Testing Evidence

- See `Day5_Status_Module_Testing.docx`

---

## Day 6 — Cost & Payment Module

### Completed

- `job_details.php` — added a second form below the status-update section for editing a job's Final Cost and Paid Amount:
  - Self-posting form (`action=update_payment` hidden field), redirect-after-POST (`job_details.php?id=X&paymentUpdated=1`), same pattern as the status form
  - Server-side validation: Final Cost and Paid Amount must both be non-negative; Paid Amount cannot exceed Final Cost — rejected outright with an inline error, no partial update applied
  - Form fields are pre-filled from the current `final_cost`/`paid_amount` values on every load, so the admin edits the real stored numbers rather than starting from blank
  - Balance (`final_cost - paid_amount`) continues to be calculated in PHP and never stored, unchanged since Day 4
- `assets/js/cost-payment.js` — live balance preview: recalculates and displays the balance on every keystroke in either field (no page reload), turning red if the currently-typed values would produce a negative balance. Visual cue only — the real enforcement is server-side.
- CSS fix: `.success` and `.error` banners (`assets/css/style.css`) had no `margin-top`, so they sat flush against the form above them (e.g. directly under the "Update Status" button). Added `margin-top: 20px;` to both rules.

### Design Decisions

- **Paid Amount is a single editable cumulative total, not a running payment log.** Consistent with the 3-table limit (no separate `payments` table) — the admin enters the new total-paid-to-date each time rather than an incremental add-on.
- **Payment form lives on the same `job_details.php` page** as the status form, not a separate page, matching the existing pattern and keeping the project flat/simple.
- **Overpayment blocked outright** rather than allowed through with a negative-balance/refund-due display, to keep validation simple and avoid a confusing UI state.

### Pending / Blocker

- None blocking.

### Pending Resolved

- N/A — no carryover blockers from Day 5.

### Testing Evidence

- See `Day6_Test_Payment_Module.docx`

---

## Day 7 — Dashboard Module

### Completed

- `dashboard.php` — replaced the Day 1 placeholder with the real dashboard, session-protected via the existing `requireLogin()`:
  - Total Customers and Total Jobs counts (`COUNT(*)` on `customers` and `jobs`)
  - Per-status counts for all five statuses (Received/Checking/Repairing/Ready/Delivered), grouped via `SELECT status, COUNT(*) ... GROUP BY status` and mapped onto a PHP-side whitelist array so a status with zero jobs still shows a 0 card
  - Total Pending Payment: `SUM(final_cost - paid_amount)` across jobs where `final_cost > paid_amount` — reuses the same balance formula as `job_details.php`, just aggregated
  - All figures are calculated live on every page load; nothing is pre-aggregated or stored, consistent with the no-derived-columns rule already applied to per-job balance
- CSS: `.dashboard-cards` grid block appended to `assets/css/style.css` (responsive `auto-fit` grid of count cards, plus a `.highlight` style for the Pending Payment card)

### Design Decisions

- **Status cards are hard-whitelisted to the same five statuses** used in `job_details.php`'s status dropdown and `jobs.php`'s filter, rather than displaying whatever distinct values happen to exist in the `status` column — keeps the dashboard's status breakdown in lockstep with the one place status values are allowed to come from.
- **Pending Payment reuses the existing per-job balance formula** (`final_cost - paid_amount`) rather than introducing a second definition of "balance" — the dashboard total and a single job's balance on `job_details.php` will always agree.

### Pending / Blocker

- None blocking.

### Pending Resolved

- N/A — no carryover blockers from Day 6.

### Testing Evidence

- See `Day7_Dashboard_Module_Testing.docx`