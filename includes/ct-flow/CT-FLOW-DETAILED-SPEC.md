# CoffeeTrail Flow — Complete Technical Specification

> Detailed documentation of the entire CT Flow system: registration, listing submission, payment, admin moderation, and post-approval editing.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Entry Points](#2-entry-points)
3. [Registration Flow](#3-registration-flow)
4. [Listing Submission Flow](#4-listing-submission-flow)
5. [Payment Flow (Grow)](#5-payment-flow-grow)
6. [Admin Moderation](#6-admin-moderation)
7. [Post-Approval Editing](#7-post-approval-editing)
8. [Email Notifications](#8-email-notifications)
9. [Dashboard Features](#9-dashboard-features)
10. [Meta Keys Reference](#10-meta-keys-reference)
11. [File Reference](#11-file-reference)
12. [Addendum: Annual Payment Option](#12-addendum-annual-payment-option)

---

## 1. Overview

CoffeeTrail Flow (CT Flow) is a custom registration and listing-creation system built on top of the MyListing WordPress theme. It provides:

- **Two-tier pricing**: Free and PRO plans
- **Custom registration flow** with phone verification and marketing consent
- **Terms agreement step** (scroll-to-unlock)
- **Payment via Grow** (pre-authorization model for PRO)
- **Admin moderation** for new listings
- **Selective field approval** for edits to live listings
- **Hebrew UI** throughout

### Architecture

```
my-listing-child/functions.php (line ~773)
    └── require_once ct-flow/ct-flow.php
            ├── class-fixes.php               → Asset loading, WC patch
            ├── class-registration-hooks.php  → Registration form customization
            ├── class-terms-step.php          → Terms + payment steps injection
            ├── class-locked-fields.php       → PRO field teasers for Free users
            ├── class-dashboard-hooks.php     → My Listings dashboard buttons
            ├── class-selective-approval.php  → Edit-mode field queueing
            ├── class-admin-panel.php         → Admin moderation UI
            ├── class-auto-save.php           → AJAX draft saving
            ├── class-email-notifications.php → Transactional emails
            ├── class-grow-payment.php        → Grow API integration
            └── class-grow-webhook.php        → Grow server callback
```

### Key Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `CT_FLOW_FREE_PRODUCT_ID` | `24` | WooCommerce product ID for Free plan |
| `CT_FLOW_PRO_PRODUCT_ID` | `25` | WooCommerce product ID for PRO plan |

---

## 2. Entry Points

### Landing Page Buttons

```
Free: /add-listing/?listing_type=cc&listing_package=24&skip_selection=1
PRO:  /add-listing/?listing_type=cc&listing_package=25&skip_selection=1
```

### Dashboard Buttons

From `/my-account/my-listings/`:

- "הוסף עגלה חדשה — חינמי" → Free flow
- "הוסף עגלה חדשה — PRO" → PRO flow

---

## 3. Registration Flow

### Trigger

When a guest visits the add-listing URL with all three params (`listing_type`, `listing_package`, `skip_selection`), they're redirected to:

```
/my-account/?redirect=[encoded add-listing URL]
```

### Registration Form Customizations

1. **Owner Notice**: "ההרשמה מיועדת לבעלי עגלות קפה / פוד טראקים בלבד"
2. **Plan Badge**: Shows "PRO" or "חינמי" with switch link
3. **Phone Field**: Required, saves to `billing_phone`
4. **Marketing Consent**: Optional checkbox

### Saved User Meta

| Meta Key | Value |
|----------|-------|
| `billing_phone` | Phone number |
| `_ct_marketing_consent` | `1` or `0` |
| `_ct_registered_plan` | `free` or `pro` |

---

## 4. Listing Submission Flow

### Step Sequence

| Priority | Step | Name | Applies To |
|----------|------|------|------------|
| **7** | `ct-terms` | הסכמה לתנאים | All |
| 10 | `submit-listing` | Form Fields | All |
| **22** | `ct-payment-placeholder` | תשלום | PRO only |
| 30 | `done` | Done | All |

### Terms Step (Priority 7)

- Scrollable terms box (scroll-to-unlock via `ct-terms-scroll.js`)
- PRO requires 2 checkboxes (terms + cancellation policy)
- Free requires 1 checkbox (terms only)
- Saves `_ct_terms_agreed_at` and `_ct_terms_plan` to user meta

### Form Step (Priority 10)

- **Locked Fields**: Free users see PRO fields as disabled teasers with "זמין במסלול PRO בלבד" overlay
- **Auto-Save**: Debounced AJAX saves drafts every 3 seconds

### Payment Step (Priority 22, PRO Only)

- Shows Grow SDK wallet (or dev placeholder if unconfigured)
- Creates pre-authorization (hold on card) via `chargeType=2`
- Validates payment before advancing to done

### Done Step

- Assigns WC package via `mylisting/payments/submission/use-free-package`
- Shows "העגלה בדרך!" for pending listings
- Shows success + link for published listings

---

## 5. Payment Flow (Grow)

### Pre-Authorization Model

```
User submits PRO listing
    ↓
CT Flow calls Grow createPaymentProcess (chargeType=2)
    ↓
Grow returns authCode → SDK wallet renders
    ↓
User enters card details
    ↓
Grow webhook fires → CT Flow stores transactionId
    ↓
Listing goes to pending status

Admin approves:
    → CT Flow calls settlesuspendedtransaction (captures payment)
    → Listing published

Admin rejects:
    → CT Flow calls refundtransaction (releases hold)
    → Listing goes to draft
```

### Configuration (`wp-config.php`)

```php
define('CT_GROW_API_KEY',   'your-api-key');
define('CT_GROW_USER_ID',   'your-user-id');
define('CT_GROW_PAGE_CODE', 'your-page-code');
define('CT_GROW_SDK_URL',   'https://cdn.grow-il.com/grow-payment-sdk.js');
define('CT_GROW_ENV',       'test'); // or 'live'
```

### Webhook Endpoint

```
POST /wp-json/ct-flow/v1/grow-callback
```

---

## 6. Admin Moderation

### Panel Location

`WP Admin → Listings → CoffeeTrail ✦`

### Tab 1: New Listings

- Shows listings with `post_status = pending`
- **Approve**: Captures Grow payment (PRO), publishes listing, sends email
- **Reject**: Releases Grow hold (PRO), sets to draft, sends email with reason

### Tab 2: Pending Changes

- Shows published listings with `_ct_has_pending_changes = 1`
- Displays diff table: old value vs new value
- Per-field or bulk approve/reject

---

## 7. Post-Approval Editing

### Selective Approval

When configured fields are edited on a published listing:

1. Old values are snapshotted before save
2. New values are queued in `_ct_pending_changes`
3. Old values are restored (live listing unchanged)
4. Admin reviews in moderation panel

### Configuration

```php
add_filter( 'ct_approval_required_fields', function( $keys ) {
    $keys[] = 'gallery';
    $keys[] = 'story';
    return $keys;
} );
```

---

## 8. Email Notifications

| Trigger | Recipient | Subject |
|---------|-----------|---------|
| Listing approved | Owner | הרישום שלך אושר! |
| Listing rejected | Owner | הרישום שלך נדחה |
| Field approved | Owner | שינוי אושר |
| Field rejected | Owner | שינוי נדחה |
| Pending changes queued | Admin | שינוי חדש ממתין |
| New listing submitted | Admin | רישום חדש ממתין |

---

## 9. Meta Keys Reference

### Listing Meta

| Key | Purpose |
|-----|---------|
| `_user_package_id` | Links to WC package |
| `_ct_pending_changes` | Queued field edits |
| `_ct_has_pending_changes` | Flag for queries |
| `_ct_rejection_reason` | Why rejected |
| `_ct_grow_transaction_id` | Grow transaction for capture |
| `_ct_grow_auth_amount` | Pre-auth amount in ILS |
| `_ct_grow_captured_at` | When payment captured |
| `_ct_grow_card_token` | Saved card for future billing |

### User Meta

| Key | Purpose |
|-----|---------|
| `billing_phone` | Phone number |
| `_ct_marketing_consent` | Marketing opt-in |
| `_ct_registered_plan` | `free` or `pro` |
| `_ct_terms_agreed_at` | When agreed to terms |

---

## 10. File Reference

### Classes

| File | Purpose |
|------|---------|
| `ct-flow.php` | Bootstrap, constants |
| `class-fixes.php` | Asset loading |
| `class-registration-hooks.php` | Registration customization |
| `class-terms-step.php` | Terms + payment steps |
| `class-locked-fields.php` | PRO field teasers |
| `class-dashboard-hooks.php` | Dashboard buttons |
| `class-selective-approval.php` | Edit field queueing |
| `class-admin-panel.php` | Admin moderation |
| `class-auto-save.php` | AJAX draft saving |
| `class-email-notifications.php` | Emails |
| `class-grow-payment.php` | Grow API |
| `class-grow-webhook.php` | Grow callback |

### Templates

| File | Purpose |
|------|---------|
| `templates/terms-step.php` | Terms UI |
| `templates/payment-placeholder.php` | Grow wallet |
| `templates/admin/moderation-panel.php` | Admin UI |

---

## 11. Addendum: Annual Payment Option

### Current State

- One-time payment model
- PRO product #25 has single price
- Pre-auth → capture on approval

### For Annual Subscriptions

**Recommended: Option A — New WC Product**

1. Create new product (e.g., ID `26`) of type `job_package_subscription`
2. Add constant: `CT_FLOW_PRO_ANNUAL_PRODUCT_ID = 26`
3. Update entry points to support three plans

**Code Changes Needed**

| File | Change |
|------|--------|
| `ct-flow.php` | Add `CT_FLOW_PRO_ANNUAL_PRODUCT_ID` constant |
| `class-terms-step.php` | Update `is_pro` checks to include annual ID |
| `class-grow-payment.php` | Add recurring payment methods using Grow's `isRecurringDebitPayment` |
| `class-registration-hooks.php` | Show three plan options |
| `class-dashboard-hooks.php` | Add annual button or plan selector |

**New Meta Keys**

| Key | Purpose |
|-----|---------|
| `_ct_billing_frequency` | `monthly` / `annual` |
| `_ct_grow_recurring_debit_id` | For Grow renewals |
| `_ct_subscription_renewal` | Next renewal date |

**Grow API for Recurring**

```php
// First payment
$params = [
    'isRecurringDebitPayment' => 1,
    'paymentType' => 1,
    'paymentNum' => 12,
];
// Stores recurringDebitId for future charges

// Annual renewal (via cron)
$params = ['recurringDebitId' => $saved_id];
```

---

*Document generated for CoffeeTrail ct-flow reference. Sync with the codebase if paths or behavior drift.*
