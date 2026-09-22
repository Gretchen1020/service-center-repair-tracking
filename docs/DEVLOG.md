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

- See `Day6_Payment_Module_Testing.docx`

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

---

## Day 8 — Print Job Card Module

### Completed

- `print_job.php` — print-friendly job card / receipt (`?id=X`), session-protected via the existing `requireLogin()`:
  - `id` validated with `filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)` (positive whole numbers only), then fetched with a prepared statement over the same `jobs` ⋈ `customers` join used by `job_details.php`
  - Standalone page (does not include `header.php`/nav) showing: Job No + status, customer name/mobile/address, device, model, technician, complaint, estimate, final cost, paid amount, and balance, followed by Customer/Authorized signature lines
  - Balance is calculated in PHP (`final_cost - paid_amount`), never stored — unchanged rule from Day 4/6; amounts formatted with the ₹ symbol to match `job_details.php`
  - Empty optional fields (model, technician, address) display `-`; every value is passed through `htmlspecialchars()`; complaint line breaks are preserved
  - A missing, non-numeric, negative, or non-existent id shows a "Job not found" message with a back-link to `jobs.php`
  - Page `<title>` set to `JobCard_<job_no>` so "Save as PDF" prefills the filename
- `assets/css/print.css` — separate stylesheet for the print page: screen styling (toolbar, bordered sheet, stacked header under 600px width) plus an `@media print` block (A4, toolbar hidden via `.no-print`, no page break inside a section or the signature block)
- `assets/js/job-print.js` — wires the Print button to `window.print()`, and fills a "Printed: date, time" line from the viewer's local clock on load and again on the `beforeprint` event
- `job_details.php` — added a "Print Job Card" link beside "Back to Job List", opening `print_job.php?id=X` in a new tab

### Design Decisions

- **Separate `print.css` and a standalone page**, rather than a print stylesheet bolted onto `style.css` or a print mode of `job_details.php` — the printout needs none of the nav, forms, or status/payment controls, so a dedicated page keeps both the markup and the print CSS small.
- **`@page { margin: 0 }` with the visible margin supplied by 15mm of body padding.** The first test print showed the browser's own header/footer (date/time, page title, URL, page number) sitting outside the content margin. A zero page margin makes the browser drop them entirely, and the date/time is now printed as part of the card itself so it lines up with the content.
- **Printed timestamp via JS (`toLocaleString()`), not PHP `date()`** — PHP would report the server's timezone and the time the page was loaded, not the time of printing; JS uses the viewer's local time and refreshes on `beforeprint`.
- **Filename set through the page `<title>`** — the only hook a web page has over the default "Save as PDF" filename.
- **`job-print.js` named with a hyphen**, following the JS naming convention recorded after Day 4.

### Pending / Blocker

- None blocking.
- **Known limitation (documented, not a defect):** the prefilled filename only works with Chrome's own **Save as PDF** destination. Windows' **Microsoft Print to PDF** is a printer driver that opens its own save dialog and never receives the page title, so its filename box always starts empty. Recorded in the testing doc (TC-17 precondition) rather than left as an open bug.

### Pending Resolved

- **Print output had misaligned browser header/footer text.** The first test PDF showed the date/time at the top left outside the content margin, the page title ("Job Card JC-0011") at the top centre, and the URL and "1/1" at the bottom. Fixed with the zero `@page` margin + body padding change above, then the date/time was re-added inside the page as the "Printed:" line.
- **Signature space was too tight** against the Cost & Payment table in the first print. Increased the gap above the signature lines (30mm in print, 90px on screen).
- **Empty filename box when saving (test-environment note, not a code bug).** Saving from the print dialog showed an empty "File name" field even though the title was already set — the Destination was Microsoft Print to PDF. Switching to Save as PDF prefilled `JobCard_JC-0011` as expected.

### Testing Evidence

- See `Day8_PrintJobCard_Module_Testing.docx` 

---

## Day 9 — Testing & Responsive Polish

### Completed Today

- Responsive pass across all 7 pages at 360px (phone), 768px (tablet) and 1280px (desktop) (assets/css/style.css)
  - Customer and job lists become labelled cards on phones instead of scrolling sideways (.stack-table + data-label attributes in includes/customer_rows.php / includes/job_rows.php); tablet/desktop keep the normal table
  - Nav links wrap with a gap; buttons and nav links get at least a 40px tap target on phones
  - Job details table uses a fixed layout so long text wraps; status/payment forms and buttons go full width on phones
  - Dashboard cards go two per row on phones; duplicate .dashboard-cards/.card rules merged
  - jobcard.php form wrapped in .card to match the other pages
- Validation fixes found while testing:
  - job_details.php payment update - final_cost/paid_amount must be numeric and at most 99,999,999.99; UPDATE wrapped in try/catch; rejected submits keep what was typed instead of reverting
  - customers.php - name capped at 100 characters, address at 255, matching the schema columns
  - jobcard.php - customer id must be a positive integer; device name/model/technician capped at 100 characters; estimate capped at 99,999,999.99
  - jobcard.php - error list changed from <ul><li> to <div> blocks, matching the style used on every other page (no bullet points)
- Session/structure fixes: session_regenerate_id(true) on login; APP_BASE in includes/auth.php now computed from the folder's position under the document root instead of hardcoded, so the project works under any folder name; dashboard.php now includes includes/footer.php
- Print job card page (print_job.php, assets/css/print.css) kept as a fixed 800px print-preview rather than reflowed to fit the phone width, since a print preview should represent the actual printed page. On phones it now scrolls both sideways and vertically; long text wraps inside table cells so it can't force the page wider than necessary; a small (12px) margin is kept on both sides via a min-width on body; a minimum-scale=1.0 viewport tag stops the browser auto-zooming the page out, which removes a large empty area that used to appear below the sheet. Actual printing is unchanged (A4, unaffected by any of the above)
- CSS/JS cache-busting: the print.css link (used by print_job.php) carries a ?v=<file modified time> so browsers pick up changes without a manual hard refresh.
- Day9_Responsive_Regression_Module_Testing.docx - 30 test cases across 6 sections (phone layout, tablet/desktop/zoom, validation, security/session, end-to-end, sample data/fresh install)

### Design Decisions

- Rows become cards on phones instead of scrolling the table sideways, so the Edit/Delete/View buttons stay on screen instead of sitting off to the right where they're easy to miss.
- The print page keeps its fixed print layout on phones rather than reflowing, so the on-screen preview matches what actually prints; the trade-off is that a phone user scrolls to see the whole sheet instead of seeing it shrink to fit.
- Server-side checks are the real guard everywhere; maxlength/required attributes are only a convenience, since a hand-made POST bypasses them.
- APP_BASE is computed with the old hardcoded value kept as a fallback.

### Pending / Blocker

- None blocking.
- Known limitations (documented, not defects): no CSRF tokens (out of scope for this build); a failed job insert still consumes an auto-increment id, so job numbers can skip; docs/schema.sql does not reset existing data on a repeat import (see Pending Resolved).

### Pending Resolved

- Payment form wiped stored amounts on blank/non-numeric input - fixed with numeric validation.
- Huge amounts and long names/addresses caused uncaught PHP exceptions (HTTP 500) - fixed with length/range checks matching the schema.
- Login redirect broke when the project folder wasn't named service_center - fixed via computed APP_BASE.
- Session ID not renewed at login; dashboard.php missing its footer include - both fixed.
- jobcard.php error messages showed as a bulleted list, inconsistent with every other page - changed to plain div blocks.
- Print page opened wider than the phone screen and left a large empty area below the sheet on phones - resolved by keeping the fixed layout with horizontal scroll and adding minimum-scale=1.0 to the viewport tag.
- docs/schema.sql now uses CREATE TABLE IF NOT EXISTS and INSERT IGNORE, so re-running it doesn't error out or duplicate the admin login (does not reset existing data - use DROP DATABASE first for a clean slate).

### Testing Evidence

- See `Day9_Responsive_Regression_Module_Testing.docx` 

---

## Day 10 — README & Final Submission

### Completed Today

- Rebuilt docs/sample_data.sql with fixed/explicit ids so job numbers (JC-0001–JC-0007) match what the Day 10 test doc references by name. Added missing-optional-field cases on a couple of rows to exercise those paths during the live demo: one customer with no address, one job with no technician, one job with no model. Covers all 5 statuses and the three payment states named in TC-03 (fully paid, partially paid, quoted-but-unpaid).
- Reviewed the full codebase against documented decisions: confirmed prepared statements, session protection, status whitelist validation, and PHP-calculated (never stored) balance are consistent across every page and both AJAX endpoints.

- Documented a setup gotcha: since docs/schema.sql uses CREATE TABLE IF NOT EXISTS / INSERT IGNORE (Day 9), re-importing it does not clear existing rows. Loading docs/sample_data.sql onto a database with leftover data will hit duplicate-key errors on the fixed ids (1–7). Correct procedure for a clean slate: drop service_center_db entirely in phpMyAdmin (Operations tab → Drop the database), re-import docs/schema.sql, then docs/sample_data.sql.

### Design Decisions

- Kept explicit id values in sample_data.sql (rather than auto-increment + name lookup) so job numbers are guaranteed to match the ones already referenced by name in the test doc.

### Pending / Blocker

- N/A

### Pending Resolved

- N/A

### Testing Evidence

- See `Day10_FinalDemo_Module_Testing.docx`