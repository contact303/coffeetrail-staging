# CT Flow — findings detail

Full risk analyses (R1–R24) for the ct-flow module, plus supplementary detail
relocated from `CLAUDE.md` during its split into a lean session-context file
and this on-demand detail file. Read on demand — this file is not loaded as
session context automatically. See `CLAUDE.md` for the architecture overview,
meta-key table, build status, path to v1, and the risk index that points here.

---

## Risk register (R1–R24)

Documented, not fixed unless marked RESOLVED.

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
internally consistent (see CLAUDE.md's Build status section). The client-side SDK
`<script>` tag exists only in the legacy `templates/payment-placeholder.php:96`, which
the active wizard path never reaches — `templates/wizard/payment.php` was never given an
equivalent enqueue. Until it is, `ct-grow-wallet.js:115`'s
`typeof growPayment === 'undefined'` check will always be true and the payment step will
show "רכיב התשלום לא נטען". This is unfinished wiring, not a regression — nobody has
attempted a real payment through this flow yet.

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

**R20 — RESOLVED. `_sync_published_step()` had no case for `basics`, `location`, or
`menu-details`; post-publish edits to those steps silently never reached native storage.**
Fixed by adding explicit `basics` and `location` cases to the switch statement
(`class-wizard-controller.php:823-888`, current line numbers), routing to the existing
`_save_taxonomies()` / `_save_files_native()` / `_save_location_native()` savers — no new
saver logic was written; both cases reuse functions already shared across other step cases.
Every other step is now enumerated explicitly (either routed to a saver or marked as
intentionally requiring none), plus a `default` that logs via `mlog()->warn()` instead of
silently no-oping, so a future missing case is caught immediately instead of repeating this
defect. `menu-details` remains unresolved — see its own note below; it's a field-definition
gap, not a wiring gap, and was explicitly kept out of this fix.

The original analysis (kept for reference):
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
  (CLAUDE.md's Field → storage map table) — same root cause.
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

**R21 — Terms acceptance is displayed at registration but never recorded there.**
`_ct_terms_agreed_at` is written in exactly two places in the whole theme: the wizard's
own `terms` step (`class-wizard-controller.php:709/711/1186`) and the legacy, likely-dead
`class-terms-step.php:208` (part of the R19 parallel submit-form flow). All three
registration entry points — `templates/page-ct-register.php`, `templates/page-ct-register-otp.php`,
`includes/my-account/register-otp.php` — render a "בבחירת הסכמה והמשך, אני מסכים/ה ל..."
ToS paragraph via the shared `ct_auth_tos_text()` helper, but none of their user-creation
code writes any consent meta; only `_ct_marketing_consent`, `_ct_marketing_consent_date`,
and `_ct_registered_plan` are saved. Registration shows the terms text but does not log
that the user agreed to it.

Until now this was latent: every user necessarily passed through the wizard's `terms`
step to reach `success`, so a record existed by the time the flow completed even though
it was written at the wrong point (post-submission, not at registration). **The free-tier
exit-at-intro-2 feature makes this reachable by a real user for the first time** — a free
user can now register, see the ToS text (unrecorded), and finish their listing via the
intro-2 exit link without ever reaching the wizard's `terms` step, leaving zero consent
record anywhere for that user. Not fixed as part of that feature (out of scope, decided
separately) — flagging here since it's now a live gap, not just a latent one. Fix would be
an explicit consent write in each registration entry point's user-creation code,
independent of the wizard.

**R22 — `$pending['redirect']` in the my-account OTP signup flow is dead code.**
`includes/my-account/register-otp.php:652` captures the original `redirect` URL (posted
from the hidden field in `templates/auth/register-otp.php:190-191`) into the OTP
transient's `$pending` array at the `request_otp` stage. It is never read again: a
whole-file grep for `$pending['redirect']` returns exactly this one write, and the
completion block builds its own unrelated post-registration redirect from scratch
(`register-otp.php:519-526` — the line R19 already flagged for hard-coding PRO; since
fixed to emit `CT_FLOW_FREE_PRODUCT_ID` instead, per product decision that every signup
starts free). No other
file can be reading it either — the transient key is namespaced via the file-local
`ct_register_get_otp_key()`, and `templates/page-ct-register-otp.php` defines an
identically-named helper but is a separate page template never loaded on the same
request. Looks like the remnant of an unfinished implementation that was meant to route
the user back to wherever they were trying to go (or read their original plan choice)
after verifying the OTP, but the completion branch was written to ignore it. Believed safe
to remove — no generic iteration over `$pending`'s keys exists in the file that would
break if the key disappeared — but not verified against a live run, and left in place
pending a decision on whether it should instead be *wired up* rather than deleted (see
open question in R23).

**R23 — `_ct_registered_plan` is never written by the live my-account OTP signup path,
so the admin plan column is blank for every user who signs up that way.**
`includes/my-account/register-otp.php`'s registration-completion block (`:455-544`)
writes only `_ct_marketing_consent` and `_ct_marketing_consent_date` on the new user; it
never writes `_ct_registered_plan`. That meta key is written elsewhere —
`class-registration-hooks.php:283` (dead code, unhooked — see R14/R19) and
`page-ct-register.php:109` / `page-ct-register-otp.php:257` (both live, both write it
correctly). `templates/admin/moderation-panel.php:81` reads `_ct_registered_plan` to
display a user's registered plan in the admin UI, with no fallback for a missing value.
Net effect: any user who registers through the my-account OTP form (a live, reachable
path — see R19/§source #5 in the listing_package investigation) will show a blank plan
in that admin column indefinitely, regardless of which tier their redirect actually sent
them to. Independent of R19's redirect-package bug and not touched by that fix — this is
a separate missing write, not a wrong value.

**R24 — `mlog()->error()` and `mlog()->warning()` are calls to methods that don't exist;
catch blocks that claim to degrade gracefully crash on an unrelated fatal instead.**
`mlog()` resolves to `MyListing\Utils\Logger\Logger::instance()`
(`my-listing/includes/util.php:9-16` → `my-listing/includes/utils/logger/logger.php:9`, parent
theme). That class defines `info()`, `warn()`, `note()`, and a set of color-named aliases
(`blue`/`red`/`green`/…) — it has **no `error()` method, no `warning()` method, and no
`__call()`** to catch the gap. Calling either throws a fatal `Error: Call to undefined method`.

This is not a cosmetic typo. Every one of these call sites sits inside a `catch (\Throwable $e)`
block whose own surrounding comment explicitly claims graceful degradation — e.g.
`class-wizard-controller.php:327-329`: *"Guard the whole persistence body: any PHP Error/
Exception is turned into a readable JSON error instead of a bare 500 critical error page."*
In practice, the catch block's first statement is `mlog()->error(...)`, which itself throws a
**new, uncaught** fatal `\Error` — the original exception is caught, but the catch handler
crashes anyway, converting an intended graceful JSON error response into a raw PHP fatal / blank
page. This directly contradicts the comment's claimed behaviour — see CLAUDE.md's ownership-
context rule: comments here are evidence of intent, not of behaviour.

Six call sites were fixed as part of the R20 fix, since they sit directly in the code paths that
fix touches (renamed to the real `warn()` method — `error()` has no equivalent tier in this
Logger, so `warn()`, its highest available level, is the correct substitute; the four
`warning()` sites are an exact intent match, just the wrong method name):
`class-wizard-controller.php:381`, `:434`, `:912`, `:1046`, `:1072`, `:1087`.

**19 call sites remain unfixed** (confirmed by direct grep, not estimated) — every one of these
will fatal instead of logging, the next time the surrounding code hits an error condition:
- `class-admin-panel.php:95`
- `class-wizard-page.php:111`, `:175`
- `class-grow-webhook.php:86`, `:93`
- `class-grow-payment.php:249`, `:287`, `:297`, `:304`
- `class-wizard-controller.php:371`, `:535`, `:551`, `:794`, `:918`, `:924`, `:1007`, `:1281`, `:1301`
- `class-terms-step.php:354`

Fix is mechanical (rename each call to `warn()`) but touches 6 files and is unrelated to any
single feature — separate work item, not folded into R20's fix.

**R25 — `region` taxonomy is never assigned to cc listings [OPEN BUILD ITEM, not a defect].**
Checked three layers: ct-flow, the parent theme's native handling, and the child theme
outside ct-flow.

ct-flow never writes `region` — confirmed by tracing every call site of
`_save_taxonomies()` and `_save_location_native()` (`class-wizard-controller.php:1034`,
`:904`). `_save_taxonomies()` only maps `cart_type→type`, `ct_roadside→road`,
`amenities→case27_job_listing_tags`, `menu_categories→foodtype`; `_save_location_native()`
only injects `$_POST['job_location']` into `Location_Field::update()`, which itself writes
only the `mylisting_locations` table plus `_latitude`/`_longitude`/`_location_coffee`
postmeta. A repo-wide grep for `region` inside `includes/ct-flow/` returns zero matches.

This is not a gap in an otherwise-automatic pipeline — there is nothing to have missed.
`region` is a native MyListing taxonomy (`my-listing/includes/post-types.php:212`,
hierarchical) exposed as an ordinary **manually-picked** field: the default field preset
`'region'` (`my-listing/includes/src/listing-types/default-config.php:67-75`) is a
`Term_Select_Field` whose `update()` (`term-select-field.php:118`) calls
`wp_set_object_terms()` on whatever the user checked in `$_POST['region']`. None of the
parent theme's geocoder classes (`my-listing/includes/src/geocoder/*.php`) or
`Location_Field::update()` (`location-field.php:87`) derive a region term from an address
or lat/lng — there is no geocoding-to-region logic anywhere in the theme for ct-flow's
`$_POST`-injection trick to have bypassed. ct-flow's location step could in principle
inject `$_POST['region']` and call that field's `update()` the same way it does for
`job_location`, but nothing does.

The child theme's only other `region`-related code
(`functions.php:805-829`, `assets/js/ct-region-results-radius.js`) enqueues a
results-radius overlay on the Explore map that *reads* an already-active region filter —
it has no write path either.

**What breaks as a result:** for every `cc` listing, the region `tax_query` filter in
listing search (`my-listing/includes/src/queries/listing-feed.php:113-120`), the
"Regions" quick-search section (`quick-search.php:125-131`), and the `/explore/regions/...`
archive pages (`explore.php:192-198,328-348,386-391`) all return empty — silently, no
errors. `match_by_region` on similar-listings (off by default) would exclude everything if
ever enabled for this type.

**What doesn't break:** proximity/"nearby" search and sorting run entirely off
`lat`/`lng` in the `mylisting_locations` table (populated correctly by
`_save_location_native()`), independent of any taxonomy — unaffected by this gap.

**Unverified:** whether the `region` field preset is even enabled on the `cc` listing
type is per-type config stored as postmeta on the `case27_listing_type` post (DB state,
not code) — no wp-cli/DB access was available to check it in this pass.

---

## Appendix A — Status of `CT-FLOW-DETAILED-SPEC.md`

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

## Appendix B — Free vs PRO, every branch point

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

## Appendix C — Things I could not determine from this codebase

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
