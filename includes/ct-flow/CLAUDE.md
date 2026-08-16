# CT Flow — working notes

Custom listing-submission system for CoffeeTrail, layered on the MyListing WP theme.
Hebrew / RTL UI. Loaded from `functions.php:7` → `ct-flow.php`.

Everything below was derived from the code, not from the comments. Where a comment
claims behaviour the surrounding code does not implement, it is called out explicitly.

> **Project stage.** In active build-out on the client's development site.
> Never deployed live — no real users, no real payments have occurred yet.
> Read findings below as *build/completion status*, not production-incident
> severity, unless explicitly marked otherwise.

> **Full analysis lives in `FINDINGS.md`.** This file is session context
> only — architecture, current state, and a risk index. **Read
> `FINDINGS.md` before working on any risk item (R1–R20)** — it has the full
> file:line trace for each one; this file only summarizes.

---

## 1. Architecture overview

**Entry URL** — `/add-listing/?listing_type=cc&listing_package={24|25}&skip_selection=1`
(built in `class-dashboard-hooks.php:43-50`; also reachable as a bare `/add-listing/`).

**Guest** — `CT_Flow_Registration::redirect_guests_to_register` (`template_redirect`,
default priority 10) fires only when **all three** query params are present, and
redirects to the `ct-register` page with `?redirect=<current url>`. A guest hitting a
bare `/add-listing/` is not redirected by CT Flow at all.

**Page takeover** — two paths:
1. `CT_Flow_Wizard_Page::maybe_output_wizard` on `template_redirect` **priority 1**:
   includes `templates/wizard-shell.php` (a full standalone `<html>` document) and
   `exit`s. Page detection: `general_add_listing_page` setting ID, else `is_page('add-listing')`.
2. Fallback `maybe_intercept_widget` on filter `mylisting/show-add-listing-widget`
   (priority 20): includes `templates/wizard-inline.php` as a fixed full-viewport
   overlay and returns `false` to suppress MyListing's widget. Guarded by the
   `$_taken_over` flag so it can't double-render.

Both skip when `action=edit|switch` or `listing_type !== 'cc'`. In shell mode,
`dequeue_wizard_shell_assets()` strips Elementor frontend assets and popups.

**State** — a transient, `ct_wizard_state_{user_id}`, TTL 7 days
(`class-wizard-controller.php:32-35`). Shape:
`current_step`, `completed_steps[]`, `job_id`, `listing_package`, `data[step][field]`.
Cleared on `ct_flow/listing_submitted_free` and `ct_flow/grow/payment_charged`.

**Step order** (`STEPS`, `class-wizard-controller.php:55-73`):
`landing → intro-1 → basics → contact → location → intro-2 → amenities →
menu-categories → images → menu-upload → menu-details → social-links → intro-3 →
hours → terms → [payment: PRO only] → success`

**Per-step transition** (`ajax_save_step`, `class-wizard-controller.php:261`):
1. nonce + login check; step key must exist in `STEPS`.
2. `_validate_step()` — server mirror of the JS rules (basics / contact / location /
   images / terms / payment only).
3. `_sanitize_fields()` recursive sanitise → merged into `state['data'][step]`.
4. `_ensure_draft()` — creates a `job_listing` draft **on the very first save**, i.e.
   as soon as the user clicks *מתחילים* on the landing step. Sets `_listing_type` and
   `_case27_listing_type` = `cc`.
5. `_persist_fields_to_draft()` — blanket write: every posted field becomes postmeta
   `_{key}`, except `job_title` → `post_title` and `job_description` → `post_content`.
6. **Early publish**: if step is `location` and the post isn't public yet, run
   `finalize_listing()` then `wp_update_post(publish)` — **for Free *and* PRO**.
   For any later step on an already-public post, `_sync_published_step()` routes only
   that step's data to the relevant native saver (deliberately never touching package
   assignment — see the comment at `:742-750`).
7. Advance `current_step`; fire `ct_flow/listing_submitted_free` when a Free run
   reaches `success`; write `_ct_listing_package = pro` when a PRO run enters `payment`.

The whole persistence body is wrapped in `try/catch(\Throwable)` returning a JSON error
plus a `debug` string.

**Save & exit** — `ajax_save_exit` persists the current step and redirects to the
WooCommerce `my-listings` endpoint. `render_resume_banner()` shows a resume link there.

**Resume** — `wizard-shell.php:42`: if `has_draft()` and (`ct_resume` present **or**
`ct_tab` absent), jump to the saved step. `ct_tab` is an admin-panel param, never
present on the front end, so in practice a user with a draft can never reach the
landing screen again.

**Payment (PRO)** — `templates/wizard/payment.php` renders the plan summary and an
empty `#ct-grow-wallet-container`. `ct-grow-wallet.js` listens for `ct:stepLoaded`,
calls `ct_grow_init` → `CT_Grow_Payment::create_charge()` → `createpaymentprocess`
(`chargeType=1`, `saveCardToken=1`), then `growPayment.renderPaymentOptions(authCode)`.
On success it sets `window.ctGrowPaid` and fires `ct:grow:payment_success`;
`ct-wizard.js` then re-runs `saveStepAndAdvance()` for the `payment` step. Server-side,
`_validate_step('payment')` requires `_ct_grow_transaction_id` to already exist —
written only by the webhook.

**Webhook** — `POST /wp-json/ct-flow/v1/grow-callback`, `permission_callback` is
`__return_true`. `statusCode=2` → store transaction meta (idempotent), reload the
author's transient, override package from `_ct_listing_package`, run
`finalize_listing()`, `wp_update_post(publish)`, fire `ct_flow/grow/payment_charged`.
`4`/`9` clear the transaction meta; `6` fires a refunded action.

**Admin** — `Listings → CoffeeTrail ✦`. Unpublish (refunds via
`refundtransaction` when a transaction exists) + per-field approve/reject of
`_ct_pending_changes` queued by `CT_Flow_Selective_Approval` on **edit-listing**
submissions only.

---

## 2. Field → storage map

`_persist_fields_to_draft()` writes **every** posted field as postmeta `_{key}`, so the
"draft meta" column below always happens. The "final target" column is the additional
write done by `finalize_listing()` / `_sync_published_step()`.

| Step | Field | Draft meta | Final target | Function |
|---|---|---|---|---|
| basics | `cart_type` | `_cart_type` | taxonomy `type` (by slug) | `_save_taxonomies` |
| basics | `job_title` | — | `post_title` | `_persist_fields_to_draft` |
| basics | `job_logo` | `_job_logo` (attachment ID) | `_job_logo` overwritten with GUID | `_save_files_native` |
| contact | `phone` | `_phone` | `_job_phone` | `_save_simple_fields` |
| contact | `whatsapp` | `_whatsapp` | `_whatsapp_number` | `_save_simple_fields` |
| contact | `ct_admin_phone` | `_ct_admin_phone` | same | `_save_simple_fields` |
| location | `address` | `_address` | `mylisting_locations` table + `_location_coffee` | `_save_location_native` |
| location | `lat` / `lng` | `_lat` / `_lng` | locations table + `_latitude` / `_longitude` | `_save_location_native` |
| location | `ct_roadside` | `_ct_roadside` | taxonomy `road` (**first term in the taxonomy**, not a mapped term) | `_save_taxonomies` |
| location | `ct_location_link` | `_ct_location_link` | same | `_save_simple_fields` |
| amenities | `amenities[]` | `_amenities` | taxonomy `case27_job_listing_tags`, `append=true` | `_save_taxonomies` |
| menu-categories | `menu_categories[]` | `_menu_categories` | taxonomy `foodtype` | `_save_taxonomies` |
| images | `cover_image` | `_cover_image` (ID) | `_job_cover` (GUID) | `_save_files_native` |
| images | `gallery[]` | `_gallery` (ID array) | `_job_gallery` (GUID array) | `_save_files_native` |
| menu-upload | `menu_type` | `_menu_type` | — | — |
| menu-upload | `menu_image` | `_menu_image` (ID) | **none** — deliberately skipped, no cc field | see `:1040-1041` |
| menu-upload | `menu_pdf` | `_menu_pdf` (ID) | `_menupdf` (GUID) | `_save_files_native` |
| menu-details | `popular_items[]`, `popular_other`, `dietary_options[]`, `kids_options[]`, `kids_other`, `special_dishes`, `is_kosher`, `kosher_type[]`, `kosher_certificate` | `_popular_items` … `_kosher_certificate` | **none** — postmeta only, no cc field, no `_sync_published_step` case | — |
| social-links | `instagram` / `facebook` / `website` | `_instagram` / `_facebook` / `_website` | `_links` (serialized `[{network,url}]`) | `_save_links_native` |
| social-links | `tiktok` | `_tiktok` | `_tiktok_url` | `_save_links_native` |
| hours | `ct_google_biz_link` | `_ct_google_biz_link` | same | `_save_simple_fields` |
| hours | `hours[day][open\|close]`, `day_active[day]` | see risk R1 — arrives mangled | `mylisting_workhours` table | `_save_work_hours_native` |
| terms | `ct_listing_terms` | `_ct_listing_terms` + `_ct_terms_agreed_at` (post) + `_ct_terms_agreed_at` (user) | `add_post_meta(..., unique)` preserves the first timestamp | `_persist_fields_to_draft` / `_save_simple_fields` |
| terms | `ct_cancellation_fee` (PRO) | `_ct_cancellation_fee` | — | — |
| any | — | `_ct_wizard_step_{step}_saved_at` | — | `_persist_fields_to_draft` |

Payment/package meta: `_ct_listing_package`, `_ct_package_assigned`, `_user_package_id`
(set by MyListing's `use-free-package`), `_ct_grow_process_id`, `_ct_grow_process_token`,
`_ct_grow_charge_amount`, `_ct_grow_charge_at`, `_ct_grow_transaction_id`,
`_ct_grow_transaction_token`, `_ct_grow_card_token`, `_ct_grow_refunded_at`,
`_ct_grow_refund_failed`, `_ct_unpublish_reason`.

Uploads go through `ajax_upload_file` → `media_handle_upload`, returning an attachment
ID; MIME is verified with `finfo`, 3 MB cap, PDF allowed only for `field_key=menu_pdf`.

---

## 3. Risk index

Full analysis for every item below is in `FINDINGS.md`. Severity: **DEFECT**
confirmed bug · **OPEN BUILD ITEM** unfinished, not a regression · **HARDENING**
needed before go-live, not urgent pre-launch · **TECH DEBT** lower-priority cleanup.

| ID | Summary | Severity |
|---|---|---|
| R1 | Work-hours data mangled in transit — every day saved as closed | DEFECT |
| R2 | Grow SDK never enqueued on the wizard payment step | OPEN BUILD ITEM |
| R3 | PRO listings publish before payment (tier-blind by design) | HARDENING |
| R4 | No webhook race fallback if the SDK success event beats the callback | HARDENING |
| R5 | Unbounded postmeta write from client input, no whitelist | HARDENING |
| R6 | No ownership check on upload target; no orphan-attachment cleanup | DEFECT |
| R7 | Webhook has no signature verification (comment claims otherwise) | HARDENING |
| R8 | Orphan drafts — no cleanup for abandoned wizard runs | TECH DEBT |
| R9 | PDF kosher certificates rejected despite being accepted client-side | DEFECT |
| R10 | `ct_roadside` maps to an arbitrary taxonomy term | DEFECT |
| R11 | Duplicate/stale meta pairs (phone, cover image, gallery) | TECH DEBT |
| R12 | Inline fallback loses the header after the landing step | DEFECT |
| R13 | Resume logic can't be escaped — draft users can't reach landing | DEFECT |
| R14 | Dead code carried in the bundle (unhooked methods, unused files) | TECH DEBT |
| R15 | Debug output echoed into `wp_footer` in production | TECH DEBT |
| R16 | External CDN hard dependencies, unpinned, no SRI | TECH DEBT |
| R17 | Possible package-ID inversion outside ct-flow (unverified) | TECH DEBT |
| R18 | Selective-approval feature fully built but never wired on | DEFECT |
| R19 | Registration has three parallel, inconsistent implementations | DEFECT |
| R20 | `_sync_published_step()` missing cases block the edit-mode feature | **RESOLVED** |
| R21 | Terms acceptance shown at registration but never recorded there — no consent record at all if the wizard's `terms` step is never reached | DEFECT |
| R22 | `$pending['redirect']` in the my-account OTP signup flow is captured and never read — dead code from an unfinished implementation | TECH DEBT |
| R23 | `_ct_registered_plan` never written by the my-account OTP signup path — admin plan column blank for those users | DEFECT |
| R24 | `mlog()->error()`/`mlog()->warning()` call undefined methods — catch blocks that claim to degrade gracefully crash on an uncaught fatal instead (19 call sites remain; 6 fixed alongside R20) | DEFECT |

---

## 4. Build status

Classification legend: **DONE** implemented and appears correct · **PARTIAL**
implemented but incomplete/unverified (note says what would settle it) ·
**NOT STARTED** referenced but never built · **DEFECT** implemented incorrectly.

| Area | Status | Note |
|---|---|---|
| Registration | PARTIAL | Three parallel implementations, DB-state-dependent which one is live (R19). Runtime check needed: which page/template is actually assigned to `ct-register`. |
| Wizard shell & interception (entry, page takeover, edit/switch bypass) | PARTIAL | Dual entry (shell + inline) correctly skips `action=edit\|switch`. R12 (inline loses header on landing), R13 (resume can't be escaped) are real defects. |
| Wizard steps — content-only (landing, intro-1/2/3, success) | DONE | No data collected; tier-specific copy confirmed correct. |
| Wizard steps — basics, contact, location, amenities, menu-categories, images, menu-upload/pdf, social-links, terms | DONE | Full field→storage chain verified (draft meta + native sync, §2 table) for first publish. Minor: `ct_roadside` assigns an arbitrary taxonomy term, not a mapped one (R10). |
| Wizard step — menu-details | PARTIAL | Saved as postmeta only; no native sync case exists. Whether the parent theme renders these fields on the public listing page is unverified — check the single-listing template. |
| Wizard step — hours | **DEFECT (confirmed)** | Field-name mangling forces every day to "closed." See R1. |
| Wizard step — payment (PRO) | PARTIAL (open build item) | Server-side Grow integration built; client-side SDK enqueue missing. See R2. |
| State persistence & resume | PARTIAL | Save/load transient itself works (DONE). Resume UX defect: a user with a draft can never reach landing or switch package via URL (R13). |
| File uploads | DEFECT | MIME/size validation solid (real `finfo` sniff, 3MB cap). No ownership check on `job_id` — any logged-in user can attach a file to another user's listing (R6). No orphan-attachment cleanup (NOT STARTED). |
| Early publish (location step, both tiers) | DONE (by design) | Works as coded. Tier-blind by design (R3, documented in the code's own comment) — confirm this is an intended product decision, not an oversight, before go-live. |
| Native field persistence — first publish (`finalize_listing`) | DONE | Syncs all steps collected so far to native taxonomies/tables at the location-step auto-publish. Duplicate meta pairs persist harmlessly (R11). |
| Native field persistence — edits after publish (`_sync_published_step`) | **RESOLVED** (partial) | `basics` and `location` now route to the same native savers used elsewhere (R20 fixed). `menu-details` still has no native target — separate field-definition question, deliberately left open. |
| Free path | DONE (core flow) | Draft → early-publish → product-24 assignment traced end to end in code. Gap: no "your listing is live" email for free tier (NOT STARTED — `ct_flow/listing_submitted_free` has no email listener). |
| PRO path | PARTIAL | Blocked today by the payment-step SDK gap; tier-trust hardening also needed before go-live (R2, package bullet in FINDINGS.md Appendix B). Refund-on-unpublish is DONE. |
| Grow payment — server side (charge creation, webhook, refund) | DONE | `create_charge()`, webhook handler, and refund logic are implemented and internally consistent. Gaps: webhook signature verification NOT STARTED (comment claims it exists; code doesn't do it — R7); no SDK-vs-webhook race fallback (R4). |
| Emails | DONE (5 core: unpublish, field approved/rejected, admin pending-changes, PRO listing-live) | Coverage gap: free-tier listing-live, payment-failed, and refund-confirmation emails are NOT STARTED — the WP actions fire but nothing listens. |
| Admin panel (menu, unpublish+refund, moderation UI) | DONE | Capability + nonce checks correct throughout; refund failure handled gracefully. |
| Selective approval (pending-changes review) | **DEFECT** | Every stage implemented correctly, but the required `ct_approval_required_fields` filter is never populated anywhere — the whole feature is a silent no-op today. See R18. |
| Legacy edit-listing flow (`action=edit\|switch` → native `Edit_Listing_Form`) | DONE | Wizard correctly steps aside; falls through to MyListing's native form unmodified — confirmed by code trace, not assumed. Selective approval is the only ct-flow hook on this path. **Do not confuse with editing via the wizard itself** — that path is the `_sync_published_step` defect above. |

---

## 5. Path to a shippable v1

**A. Finishing incomplete work**
- Wire the Grow SDK into the wizard payment template (R2).
- Fix the hours-step field-name mangling in `collectStepFields` (R1).
- Decide + implement menu-details field rendering (or confirm postmeta-only is intentional).
- Populate `ct_approval_required_fields`, or remove the selective-approval admin UI if not wanted for v1 (R18).
- Add the missing free-tier "listing is live" email.
- Resolve the registration situation: pick one canonical implementation, fix the duplicate `Template Name`, fix the my-account OTP module's hard-coded PRO redirect (R19).
- ~~Add `basics` and `location` cases to `_sync_published_step()`~~ — **done (R20)**. Section C's prerequisite is cleared.
- Fix the remaining 19 `mlog()->error()`/`mlog()->warning()` call sites across the module so error handling actually degrades gracefully instead of fataling (R24).

**B. Hardening required before go-live**
- Server-side tier verification — stop trusting client-posted `package` for assignment.
- Add ownership/capability check to `ajax_upload_file` (R6).
- Add webhook signature verification (R7).
- Fix the open redirect in `page-ct-register.php:117` (R19).
- Confirm tier-blind early publish is the intended product behavior (R3).
- Add orphan-attachment / orphan-draft cleanup (R8).
- Resume-flow escape hatch — let a user with a draft reach landing / switch package (R13).

**C. Step-URL / edit-mode feature — not yet analysed**
Out of scope for this investigation; needs its own trace once requirements are described.
Prerequisite **R20 is now fixed** — `_sync_published_step()` has cases for `basics`/`location`,
so the feature's premise (jump to and re-save any step on a published listing) is no longer
blocked for those two steps. `menu-details` still has no native sync target; confirm whether
the edit-mode feature needs one before including that step.
