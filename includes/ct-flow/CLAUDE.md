# CT Flow — working notes

Custom listing-submission system for CoffeeTrail, layered on the MyListing WP theme.
Hebrew / RTL UI. Loaded from `functions.php:7` → `ct-flow.php`.

Everything below was derived from the code, not from the comments. Where a comment
claims behaviour the surrounding code does not implement, it is called out explicitly.

> **Project stage.** In active build-out on the client's development site.
> Never deployed live — no real users, no real payments have occurred yet.
> Read findings below as *build/completion status*, not production-incident
> severity, unless explicitly marked otherwise.

---

## 1. Status of `CT-FLOW-DETAILED-SPEC.md`

**Largely obsolete.** It documents the *previous* system: custom steps injected into
MyListing's `Add_Listing_Form` via `mylisting/submission-steps`. That was replaced by a
standalone 17-step wizard (`class-wizard-controller.php` + `class-wizard-page.php`),
which the spec does not mention at all.

Concretely wrong:

| Spec claim | Reality |
|---|---|
| §4 step sequence at priorities 7 / 10 / 22 / 30 | Real flow is `CT_Flow_Wizard_Controller::STEPS` (17 steps). `class-terms-step.php` only fires if wizard interception fails entirely |
| §5 pre-auth model, `chargeType=2`, `settlesuspendedtransaction` | `create_charge()` sends `chargeType=1` (immediate charge) — `class-grow-payment.php:81`. No settle/capture method exists anywhere |
| §5 "listing goes to pending → admin approves → publish" | **No admin approval step exists.** Listings publish automatically at the `location` step for both tiers (`class-wizard-controller.php:319-327`); PRO republishes via the webhook (`class-grow-webhook.php:143-150`) |
| §6 "Tab 1: New Listings" with Approve/Reject | Panel tabs are *פורסמו לאחרונה* (already published) and *שינויים ממתינים*. The only listing action is **Unpublish** (`class-admin-panel.php:88-111`) |
| §3 registration form customisations (owner notice, plan badge, phone field, marketing consent, `save_user_meta`) | Those methods exist but **are not hooked**. `CT_Flow_Registration::init()` registers only `redirect_guests_to_register` (`class-registration-hooks.php:31-34`). Everything from `render_owner_notice()` down is dead code |
| §3 guests redirected to `/my-account/?redirect=` | Redirect target is the `ct-register` page, falling back to `MyListing\get_register_url()` (`class-registration-hooks.php:72-77`) |
| §4 "Auto-Save: debounced AJAX every 3 s" | `ct-auto-save.js` binds to `#submit-job-form` and self-exits when absent (`ct-auto-save.js:21,32`). The wizard never renders that form → auto-save never runs in the new flow |
| §4 "Locked Fields: Free users see PRO teasers" | Hooks `mylisting/submission/fields` (`class-locked-fields.php:50`), which the wizard bypasses. No locked fields in the wizard |
| §8 email table (listing approved / rejected, new listing → admin) | Do not exist. Actual set: `class-email-notifications.php:27-40` |
| §9 `_ct_grow_auth_amount`, `_ct_grow_captured_at` | Not written anywhere. Real keys: `_ct_grow_charge_amount`, `_ct_grow_charge_at`, `_ct_grow_process_id`, `_ct_grow_process_token`, `_ct_grow_transaction_token`, `_ct_grow_refunded_at`, `_ct_grow_refund_failed` |
| §9 user meta `_ct_marketing_consent`, `_ct_registered_plan` | Written only by the unhooked `save_user_meta()` → never written in practice |
| §10 file reference | Omits `class-wizard-controller.php`, `class-wizard-page.php`, and the whole `templates/wizard/` tree |

Still accurate: product IDs 24/25, entry-point URLs, webhook route, Grow wp-config
constants, the selective-approval mechanism and its filter, dashboard buttons,
`_ct_pending_changes` / `_ct_has_pending_changes`.

---

## 2. End-to-end flow

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

## 3. Free vs PRO — every branch point

**Real branches**

| Where | Behaviour |
|---|---|
| `class-wizard-controller.php:162-172` | `payment` step removed from the order for Free |
| `class-wizard-controller.php:565-568` | `terms` step: PRO additionally requires `ct_cancellation_fee` |
| `class-wizard-controller.php:345-347` | Free only: `do_action('ct_flow/listing_submitted_free')` on entering `success` |
| `class-wizard-controller.php:351-353` | PRO only: `_ct_listing_package = 'pro'` written on entering `payment` |
| `class-wizard-controller.php:1161-1170` | Product 25 vs 24; PRO assignment deferred until a Grow transaction exists |
| `templates/wizard/terms.php:19,37-105` | Different terms copy; extra cancellation checkbox for PRO |
| `templates/wizard/success.php:19,32-77` | Different copy; PRO gets "view as customers", Free gets "upgrade to Pro" |
| `templates/wizard/landing.php:40-44` | PRO gets an extra "payment at the end" line |
| `templates/wizard/payment.php` | PRO-only template |
| `wizard-shell.php:29-36` / `wizard-inline.php:37-44` | Numeric product ID → `'free'`/`'pro'` mapping |
| `class-admin-panel.php:92-105` | Refund attempted on unpublish only when a charge is on record |
| `class-locked-fields.php:88-94` | Free-only PRO-field teasers — **legacy form path only, never runs in the wizard** |
| `class-terms-step.php:85-92` | PRO-only payment placeholder step — **legacy path only** |

**Looks like it should branch, but doesn't**

- **Early publish is tier-blind** (`class-wizard-controller.php:317-327`). A PRO user
  who abandons at the payment step keeps a live, published listing without paying.
  The comment documents this as intentional; confirm it is a product decision.
- **No PRO-only fields anywhere in the wizard.** Every step between `location` and
  `terms` (amenities, images, menu-*, social-links, hours) is byte-identical for both
  tiers. Validation rules and required flags are identical too.
- **`package` is client-controlled — open hardening item, required before go-live.**
  It comes from `$_POST['package']` (`class-wizard-controller.php:270`) and is only
  whitelisted against `free|pro` — never checked against a purchase, `_ct_registered_plan`,
  or the entry-URL product ID. As built today, posting `package=free` through a
  PRO-intent run skips payment and assigns product 24. Not an active incident (no real
  traffic exists yet), but must be closed — tie tier assignment to something the client
  can't override — before any real payment goes live.
- **No Free-path email at all.** `send_listing_live()` is hooked to
  `ct_flow/grow/payment_charged` only (`class-email-notifications.php:39`).
  `ct_flow/listing_submitted_free` has exactly one listener — state cleanup.
- **`ct_flow/listing_published_early` has no listeners.** Extension point only.
- **Upgrade path doesn't exist.** `success.php:21,73` links to
  `/add-listing/?listing_package=25`, which starts a brand-new wizard rather than
  upgrading the listing just created.
- **Price display is not tier-derived.** `payment.php:20-21` hard-codes ₪150 / ₪1500
  while the real charge uses `wc_get_product(25)->get_price()`
  (`class-grow-payment.php:182-185`).

---

## 4. Field → storage map

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

## 5. Risks and technical debt

Documented, not fixed.

**R1 — work-hours data is mangled in transit (confirmed defect).**
`ct-wizard.js:647` builds a *flat* key `fields['day_active[sun]']`, and the hour inputs
are named `hours[sun][open]`. jQuery encodes these as `fields[hours[sun][open]]`; PHP's
bracket parser stops at the first `]`, yielding `$_POST['fields']['hours[sun']['open']`,
and `sanitize_key()` then flattens the key to `hourssun`. Net effect:
`$state['data']['hours']['hours']` and `['day_active']` never exist, so
`_save_work_hours_native` (`class-wizard-controller.php:878-893`) marks **every day**
`closed-all-day` and writes that into `mylisting_workhours` — actively worse than a
no-op. This directly contradicts the `_sanitize_fields` docblock
(`class-wizard-controller.php:676-683`), which claims recursion fixed exactly this case.
Verify by dumping the transient after saving the hours step. Note real JS arrays
(`gallery`, `amenities`, `menu_categories`) are unaffected — only bracket-in-string keys break.

⚠️ **Until fixed:** any manual testing of the hours step will look like it saved
successfully (no error shown) but will silently store every day as closed. Do not trust
hours data when testing other features. Any listing already created on the dev site has
a corrupted `mylisting_workhours` row for its hours step and will need reseeding, not
just a code fix, once R1 is resolved.

**R2 — Grow SDK client-side loading is not yet wired into the wizard [OPEN BUILD ITEM, not a defect].**
Server-side Grow integration (`create_charge()`, webhook handler, refund) is built and
internally consistent (see §7). The client-side SDK `<script>` tag exists only in the
legacy `templates/payment-placeholder.php:96`, which the active wizard path never
reaches — `templates/wizard/payment.php` was never given an equivalent enqueue. Until it
is, `ct-grow-wallet.js:115`'s `typeof growPayment === 'undefined'` check will always be
true and the payment step will show "רכיב התשלום לא נטען". This is unfinished wiring, not
a regression — nobody has attempted a real payment through this flow yet.

**R3 — PRO listings are published before payment.** `class-wizard-controller.php:319-327`.

**R4 — no webhook race fallback in the wizard.** `_validate_step('payment')`
(`class-wizard-controller.php:571-578`) requires `_ct_grow_transaction_id`, which only the
webhook writes. If the SDK success event beats the server-to-server callback the user
sees "לא נמצאה אסמכתא לתשלום" with no retry path. The *legacy* handler had a
`get_transaction_status()` fallback (`class-terms-step.php:283-297`); the wizard does not.

**R5 — unbounded postmeta write from client input.** `_persist_fields_to_draft`
(`class-wizard-controller.php:641-659`) writes `_{sanitize_key($key)}` for any posted
field with no whitelist. A logged-in user can set arbitrary underscore-prefixed meta on
their own listing (e.g. `fields[job_cover]` → `_job_cover`). Compare `class-auto-save.php:128`,
which does whitelist against the listing type's fields.

**R6 — no ownership check on the upload target, and no orphan-attachment cleanup.**
`ajax_upload_file()` (`class-wizard-controller.php:437-498`) checks the requester is
logged in (`:440-442`) and holds a valid AJAX nonce (`:438`), but never checks that the
poster owns `job_id`: `$job_id = absint($_POST['job_id'] ?? 0)` (`:450`) is used as-is
both to call `media_handle_upload('file', $job_id)` (`:481`) and to re-parent the
attachment (`wp_update_post(['ID'=>$attachment_id,'post_parent'=>$job_id])`, `:489-491`).
Any logged-in user can attach a file to **any** existing `job_listing` post by supplying
its ID in the POST body. Fix: add a `current_user_can('edit_post', $job_id)` (or explicit
`post_author` match) check before both calls. Separately, there is no cleanup for
orphaned uploads: a file uploaded before `_ensure_draft()` has run (`job_id=0`) stays
parentless forever, and files tied to abandoned drafts (R8) are never removed — no cron,
no `wp_delete_attachment()` call anywhere in the module. MIME/size validation itself is
solid and not part of this risk: a real `finfo` MIME sniff (`:458-459`) against an
explicit whitelist (`:41-46`), 3 MB cap (`:452-455`).

**R7 — webhook has no signature verification.** `permission_callback => '__return_true'`
(`class-grow-webhook.php:51`); the comment says "signature validation is done inside",
but `handle()` performs none. Correlation is by `processId` lookup only.

**R8 — orphan drafts.** `_ensure_draft` runs on the very first `ajax_save_step`, i.e. on
the landing step. Every user who clicks *מתחילים* and leaves creates a `job_listing`
draft. No cleanup exists.

**R9 — PDF kosher certificates are rejected.** `menu-details.php:184` accepts `.pdf` for
`kosher_certificate`, but `ajax_upload_file` allows `application/pdf` only when
`field_key === 'menu_pdf'` (`class-wizard-controller.php:463-465`).

**R10 — `ct_roadside` maps to an arbitrary term.** `_save_taxonomies`
(`class-wizard-controller.php:960-969`) assigns `get_terms(['taxonomy'=>'road','number'=>1])[0]`
— whichever term happens to come first, not a named one.

**R11 — duplicate/stale meta.** `_phone` vs `_job_phone`, `_whatsapp` vs `_whatsapp_number`,
`_cover_image` (ID) vs `_job_cover` (GUID), `_gallery` (IDs) vs `_job_gallery` (GUIDs).
Both copies persist; only the second of each pair is read by MyListing.

**R12 — inline fallback loses the header.** `wizard-inline.php:79-81` omits `header.php`
on the landing step, but `updateHeaderForStep()` (`ct-wizard.js:532-537`) only toggles
buttons that must already be in the DOM. In the inline path, navigating off the landing
step leaves no header at all.

**R13 — resume logic can't be escaped.** `wizard-shell.php:42` keys on `ct_tab`, which
never appears on the front end, so a user with a draft always resumes and can neither
see the landing screen nor switch package via URL.

**R14 — dead code carried in the bundle.** `CT_Flow_Registration` methods from
`render_owner_notice()` down are unhooked; `templates/wizard/cart-type.php` is not in
`STEPS` (merged into `basics`); `ct-auto-save.js` + `CT_Flow_Auto_Save` never fire in the
wizard; `templates/theme-overrides/` duplicates `templates/add-listing/` and nothing
references it; `includes/ct-flow.zip` and `ct-flow.zip` are checked-in build artefacts.

**R15 — debug output in production.** `functions.php:10-21` echoes HTML comments into
`wp_footer` on every page load.

**R16 — external CDN hard dependencies.** `class-fixes.php:200-235` loads Google Fonts,
browser-image-compression, heic2any, Cropper.js and SortableJS from public CDNs on every
add-listing page load, unpinned to any SRI hash.

**R17 — possible package-ID inversion outside ct-flow.**
`templates/dashboard/{free,pro}-user/my-page.php:49-52` maps `_package_id === 24` to
`'pro'`, while ct-flow defines 24 as Free (`ct-flow.php:22`). Outside this module, but
it reads the same IDs — worth checking before touching either.

**R18 — selective-approval feature is fully built but never wired on (silent no-op).**
`class-selective-approval.php`'s snapshot → queue → admin-review → approve/reject →
apply → email pipeline is implemented correctly end to end and matches its own docblock.
But `get_approval_required_fields()` (`:67-69`) returns
`apply_filters('ct_approval_required_fields', [])`, and that filter is never added
anywhere in the theme (`functions.php` or elsewhere) — grepped, zero matches outside the
docblock's own example. Result: `snapshot_old_values()` returns immediately on every
edit-listing save (`:159-161`), nothing is ever queued, and the admin "שינויים ממתינים"
tab will always be empty. Every piece works in isolation; the one line that turns it on
was never written.

**R19 — registration has three parallel, inconsistent implementations.**
(a) `CT_Flow_Registration` (`class-registration-hooks.php`) hooks only
`redirect_guests_to_register`; 7 of 9 public methods are dead code (matches R14).
(b) `templates/page-ct-register.php` (plain form) and `templates/page-ct-register-otp.php`
(OTP form) both declare `Template Name: CT Register` — WordPress cannot distinguish them
in the page editor; which one actually renders for a given WP page is DB-state-dependent
and not verifiable by reading code. The plain form also has an open redirect:
`wp_redirect($redirect)` at `page-ct-register.php:117` uses a POSTed `$redirect` that is
only `esc_url_raw()`'d, never `wp_validate_redirect()`'d (the OTP variant does this
correctly). (c) A third system, `includes/my-account/register-otp.php` (loaded via the
dashboard login form), **always redirects new signups into the PRO wizard** (`:519-526`,
hard-codes `listing_package=25`) regardless of which plan the user picked, and ships a
dead "SMS registration" tab (`templates/auth/register-form.php:14-16,37`) with zero
listeners anywhere — pure UI scaffolding, not a partial backend. Needs a decision on
which registration implementation is canonical before go-live.

**R20 — `_sync_published_step()` has no case for `basics`, `location`, or
`menu-details`; post-publish edits to those steps silently never reach native storage.**
The switch statement (`class-wizard-controller.php:756-784`) only handles `amenities`,
`menu-categories`, `images`, `menu-upload`, `social-links`, `hours`, and `contact`. No
`default` case either. Concretely, once a listing is public (which happens automatically
at the `location` step for both tiers — R3):
- **basics**: `job_title`/`job_description` are safe — special-cased directly and
  unconditionally in `_persist_fields_to_draft()` (`:648-656`), independent of publish
  state. But `cart_type` and `job_logo` fall through to plain `update_post_meta()`
  (`:658`) with no path back to `_save_taxonomies()`/`_save_files_native()` — the `type`
  taxonomy term and the GUID-normalized `_job_logo` go stale.
- **location**: `address`/`lat`/`lng`/`ct_roadside` all fall through the same way — no
  path back to `_save_location_native()`/`_save_taxonomies()`, so the `mylisting_locations`
  table row, `_latitude`/`_longitude`, and the `road` taxonomy term never update again.
- **menu-details**: already has no native target at all, even on first publish
  (§4 table) — same root cause.
- **terms** is *not* actually affected, despite also missing a switch case: its only
  native-adjacent write (`_ct_terms_agreed_at`) is special-cased directly and
  unconditionally inside `_persist_fields_to_draft()` (`:662-668`), so it doesn't need
  `_sync_published_step()` at all.

This is reachable **today**, not just a future concern: the wizard has working backward
navigation (`data-prev-step` / `loadStep()`, `ct-wizard.js:181-183`), so a user who
proceeds past `location` (auto-publish fires) and then clicks back into `basics` or
`location` to fix something and saves again will have the edit silently accepted
client-side but never propagate past raw postmeta.

**This directly blocks the planned step-URL / edit-mode feature** — that feature's
premise is letting a user jump to and re-save arbitrary steps on an already-published
listing, and `_sync_published_step()` currently has no way to do that correctly for 3 of
the ~13 data-bearing steps. It needs `basics` and `location` cases added (routing to
`_save_taxonomies()` / `_save_files_native()` / `_save_location_native()` as appropriate)
before that feature can be built on top of it.

---

## 6. Things I could not determine from this codebase

- Whether the parent theme actually exposes the `mylisting/show-add-listing-widget`
  filter, and whether `mylisting-openstreetmap` is a registered handle
  (`class-fixes.php:270-271`). Both are assumed by the child theme; the parent theme was
  not read.
- Whether `finalize_listing()`'s `$_POST` injection trick for `Location_Field` /
  `Work_Hours_Field` matches the installed MyListing version's expectations.
- Which taxonomy terms actually exist for `type`, `foodtype`, `road`,
  `case27_job_listing_tags`. The wizard's hard-coded slugs (`amenities.php:23-49`,
  `menu-categories.php:20-29`) are matched by `get_term_by('slug', …)`; unmatched slugs
  are silently dropped with a warning to `mlog()`.
- Whether Grow's live API base URLs and endpoint paths (`class-grow-payment.php:29-30`)
  are current — the code's own comments flag them as unverified.

---

## 7. Build status

Classification legend: **DONE** implemented and appears correct · **PARTIAL**
implemented but incomplete/unverified (note says what would settle it) ·
**NOT STARTED** referenced but never built · **DEFECT** implemented incorrectly.

| Area | Status | Note |
|---|---|---|
| Registration | PARTIAL | Three parallel implementations, DB-state-dependent which one is live (R19). Runtime check needed: which page/template is actually assigned to `ct-register`. |
| Wizard shell & interception (entry, page takeover, edit/switch bypass) | PARTIAL | Dual entry (shell + inline) correctly skips `action=edit\|switch`. R12 (inline loses header on landing), R13 (resume can't be escaped) are real defects. |
| Wizard steps — content-only (landing, intro-1/2/3, success) | DONE | No data collected; tier-specific copy confirmed correct. |
| Wizard steps — basics, contact, location, amenities, menu-categories, images, menu-upload/pdf, social-links, terms | DONE | Full field→storage chain verified (draft meta + native sync, §4 table) for first publish. Minor: `ct_roadside` assigns an arbitrary taxonomy term, not a mapped one (R10). |
| Wizard step — menu-details | PARTIAL | Saved as postmeta only; no native sync case exists. Whether the parent theme renders these fields on the public listing page is unverified — check the single-listing template. |
| Wizard step — hours | **DEFECT (confirmed)** | Field-name mangling forces every day to "closed." See R1. |
| Wizard step — payment (PRO) | PARTIAL (open build item) | Server-side Grow integration built; client-side SDK enqueue missing. See R2. |
| State persistence & resume | PARTIAL | Save/load transient itself works (DONE). Resume UX defect: a user with a draft can never reach landing or switch package via URL (R13). |
| File uploads | DEFECT | MIME/size validation solid (real `finfo` sniff, 3MB cap). No ownership check on `job_id` — any logged-in user can attach a file to another user's listing (R6). No orphan-attachment cleanup (NOT STARTED). |
| Early publish (location step, both tiers) | DONE (by design) | Works as coded. Tier-blind by design (R3, documented in the code's own comment) — confirm this is an intended product decision, not an oversight, before go-live. |
| Native field persistence — first publish (`finalize_listing`) | DONE | Syncs all steps collected so far to native taxonomies/tables at the location-step auto-publish. Duplicate meta pairs persist harmlessly (R11). |
| Native field persistence — edits after publish (`_sync_published_step`) | **DEFECT** | Switch statement has no case for `basics`, `location`, or `menu-details` — re-saving those steps after the listing is already public writes postmeta only, never reaching the `type`/`road` taxonomies, `_job_logo` GUID, or the `mylisting_locations` table. Reachable today via the wizard's existing back-navigation. **Blocks the step-URL/edit-mode feature.** See R20. |
| Free path | DONE (core flow) | Draft → early-publish → product-24 assignment traced end to end in code. Gap: no "your listing is live" email for free tier (NOT STARTED — `ct_flow/listing_submitted_free` has no email listener). |
| PRO path | PARTIAL | Blocked today by the payment-step SDK gap; tier-trust hardening also needed before go-live (R2, package bullet in §3). Refund-on-unpublish is DONE. |
| Grow payment — server side (charge creation, webhook, refund) | DONE | `create_charge()`, webhook handler, and refund logic are implemented and internally consistent. Gaps: webhook signature verification NOT STARTED (comment claims it exists; code doesn't do it — R7); no SDK-vs-webhook race fallback (R4). |
| Emails | DONE (5 core: unpublish, field approved/rejected, admin pending-changes, PRO listing-live) | Coverage gap: free-tier listing-live, payment-failed, and refund-confirmation emails are NOT STARTED — the WP actions fire but nothing listens. |
| Admin panel (menu, unpublish+refund, moderation UI) | DONE | Capability + nonce checks correct throughout; refund failure handled gracefully. |
| Selective approval (pending-changes review) | **DEFECT** | Every stage implemented correctly, but the required `ct_approval_required_fields` filter is never populated anywhere — the whole feature is a silent no-op today. See R18. |
| Legacy edit-listing flow (`action=edit\|switch` → native `Edit_Listing_Form`) | DONE | Wizard correctly steps aside; falls through to MyListing's native form unmodified — confirmed by code trace, not assumed. Selective approval is the only ct-flow hook on this path. **Do not confuse with editing via the wizard itself** — that path is the `_sync_published_step` defect above. |

---

## 8. Path to a shippable v1

**A. Finishing incomplete work**
- Wire the Grow SDK into the wizard payment template (R2).
- Fix the hours-step field-name mangling in `collectStepFields` (R1).
- Decide + implement menu-details field rendering (or confirm postmeta-only is intentional).
- Populate `ct_approval_required_fields`, or remove the selective-approval admin UI if not wanted for v1 (R18).
- Add the missing free-tier "listing is live" email.
- Resolve the registration situation: pick one canonical implementation, fix the duplicate `Template Name`, fix the my-account OTP module's hard-coded PRO redirect (R19).
- Add `basics` and `location` cases to `_sync_published_step()` so post-publish edits to those steps actually reach native storage (R20) — **prerequisite for section C below**.

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
Known prerequisite already found: **R20 must be fixed first** — the feature's whole
premise (jump to and re-save any step on a published listing) is currently broken for
`basics`/`location` by `_sync_published_step()`'s missing cases.
