# Dcw_PurchaseOrderReview

Adds a **Purchase Order** payment method (`dcw_po_gateway`) for B2B-style checkout: orders are placed in a **pending review** state, customers are notified by email, and a **Zendesk** ticket can be created for internal follow-up. If Zendesk fails, an optional **fallback** email is sent.

## Requirements

- Magento 2.4+ (Sales, Checkout, Payment, Email, Config)
- **Magento_Company** (company name on Zendesk ticket body when applicable)
- **Dcw_PaymentMethods** (declared in `module.xml` sequence)

## Admin configuration

**Stores → Configuration → Sales → Purchase Order Review (PO + Zendesk)**

| Group | Purpose |
|--------|--------|
| **General** | Enable module, minimum **base subtotal** for PO, **allow guest PO** |
| **Checkout** | **PO shipping modal — CMS block identifier** (must match a **Content → Blocks** block; default `po_shipping_modal`) |
| **Zendesk API** | Enable ticket creation, **subdomain**, agent **email**, **API token** (obscure/encrypted field) |
| **Fallback email** | Recipient when Zendesk ticket creation fails |

**Stores → Configuration → Sales → Payment Methods → Purchase Order (Custom Gateway)**

- Enable/disable, title, sort order.

## Behavior

1. **Availability** (`PaymentMethodPlugin`): PO is shown when general module is enabled, the method is active, **base subtotal** ≥ configured threshold, and for guests only if **Allow Guest Checkout with PO** is Yes (logged-in customers are not required to belong to a Magento Company).
2. **Checkout**: Customer enters **contact name, 10-digit phone, and email** (required before order submit). The payment method may be saved once via API with only the method code; full contact data must be present when the quote is submitted (`sales_model_service_quote_submit_before` validates).
3. **After place order** (`sales_order_place_after` → `ProcessPurchaseOrderAfterPlace`):
   - Sets **status** `po_pending_review`; **state** via **`PendingReviewPlacementState::resolveAfterPlace()`** — preserves **processing** if already set (uncommon for this method), otherwise **new** for invoice flow (**pending_payment** → **new**).
   - Sends customer **expectation** email (`po_expectation`).
   - Creates a **Zendesk** ticket with order summary; stores `zendesk_ticket_id` on payment additional info when successful.
   - On Zendesk failure, sends **fallback** email (`po_pending_review_fallback`) if a fallback address is configured.
4. **Encrypted API token**: `Helper\Config::getZendeskApiToken()` decrypts Magento ciphertext when `ScopeConfig` returns an encrypted blob (avoids Zendesk **401** when metadata decryption is missing for this path).

## Data patch

`Setup/Patch/Data/AddPoPendingReviewStatus` registers `po_pending_review` linked to **`new` and `processing`**. **`EnsurePoPendingReviewUsesNewOrderState`** removes the obsolete `pending_payment` link, ensures both state rows exist, and moves stuck `pending_payment` orders to `new`. **`MigratePoPendingReviewStatusToProcessingState`** is a no-op for patch history.

After deploy: `bin/magento setup:upgrade`

## PO checkout shipping modal (CMS block)

Create the block in **Admin → Content → Blocks → Add New Block**:

- **Identifier:** `po_shipping_modal` (or another value; set **Stores → Configuration → … → Checkout → PO shipping modal — CMS block identifier** to match).
- **Block Title:** Used as the modal popup **heading** (`dcwPoShippingModalTitle` in checkout config).
- **Enable Block:** Yes  
- **Store View:** All Store Views (or as needed)  
- **Content:** Your modal HTML (phone, messaging, etc.).

The checkout modal loads **title + filtered content** from this block via `ShippingModalConfigProvider`. If the block is missing or the title is empty, the JS title falls back to a translated default.

## Frontend

- Checkout UI: `view/frontend/layout/checkout_index_index.xml`, Knockout renderer and template `po_gateway.html`.
- **Admin / emails / PDFs**: `Block/Info/PoGateway` uses `Magento_Payment::info/default.phtml` and `_prepareSpecificInformation()` so the payment **title** and **Purchase Order #** show everywhere (including **Sales → Orders → view → Payment Information**).

## Email templates

Declared in `etc/email_templates.xml`:

- `dcw_po_review_expectation`
- `dcw_po_review_fallback`

## Developer notes

- **Dcw_PaymentMethods** listens to `payment_method_is_active` in `PaymentMethodDisable`; it explicitly skips `dcw_po_gateway` so legacy company/COD rules for `purchaseorder` and other methods do not affect this gateway.
- **Static content**: `bin/magento setup:static-content:deploy` as needed after JS/template changes.
- **DI compile**: `bin/magento setup:di:compile` after PHP constructor changes.
- **Manual Zendesk check**: use Postman or curl against `https://{subdomain}.zendesk.com/api/v2/tickets.json` with Basic auth username `{email}/token` and password = API token (plain token length is typically ~40 characters; ~90+ from config usually means undecrypted ciphertext—see `Helper\Config`).

## ACL

`etc/acl.xml` — configuration resource `Dcw_PurchaseOrderReview::config`.
