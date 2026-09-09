# Dcw_OrderPendingReview

Centralizes **why** an order should stay in **Pending Review** before downstream systems (e.g. NetSuite) process it:

- Persists **`sales_order.dcw_pending_review_reasons`** as JSON (structured **Order Restriction** payloads from **pending_review** rules).
- Sets order **state** from **`PendingReviewPlacementState::resolveAfterPlace()`** (captures state *before* hold logic): keeps **processing** when the gateway (e.g. Authorize.Net) already set it; maps **pending_payment** → **new** for invoice lifecycle; **new** stays **new**. **Status** is `po_pending_review` (shared with **Dcw_PurchaseOrderReview**).
- Maintains **`quote.dcw_failed_payment_attempts`** and **`quote` / `sales_order`.`dcw_fraud_flag`** for rule conditions and auditing.
- Integrates **Dotcomweavers Order Restrictions** with **`apply_type`**: **restriction** (block checkout) vs **pending_review** (hold after place).

**Module sequence:** `module.xml` lists **Dcw_PurchaseOrderReview** before this module so the PO observer on `sales_order_place_after` typically runs first (Magento `events.xml` has no observer `sortOrder`). If ordering diverges, use a plugin or dedicated event.

## Requirements

- Magento 2.4+ (Sales, Checkout, Quote, Sales Rule)
- **Dcw_PurchaseOrderReview** — `po_pending_review` status and PO payment flow.
- **Dotcomweavers_OrderRestrictions** — rules, conditions, admin UI; this module adds **`apply_type`**, **Fraud flag** condition, and validation glue.
- **Dcw_IncstoreShipping** — unit weight helper used by **`QuoteRulesValidator`** and **`OrderRestrictionPendingReviewMatcher`** (aligned with legacy checkout restriction checks).
- **Dcw_SplitPayment** — optional; used by checkout plugins for failed-attempt tracking, not for hold reason detection (holds use rules only).

## Configuration

There is **no** Stores → Configuration toggle for this module. **Pending review** behavior is driven only by **Order Restrictions** rules with **Apply type = pending_review** (and optional **restriction** rules for blocking checkout).

Order **status** when holds apply is **`Dcw\OrderPendingReview\Model\Config::PENDING_REVIEW_ORDER_STATUS`** (default **`po_pending_review`**), defined in code only.

## Order Restriction apply types (admin rule form)

Extended on **Order Restrictions** rules (`orderrestriction_rules.apply_type` in `db_schema.xml`):

| Value | Behavior |
|--------|----------|
| **restriction** | **Blocking:** evaluated by **`QuoteRulesValidator`** on **place order** (`Dotcomweavers\OrderRestrictions\Plugin\OrderManagement` delegates to this module) and optionally by **`CheckRestriction`** during checkout (shipping-step AJAX in **`Dotcomweavers_OrderRestrictions::onepage_js.phtml`**). Only **`restriction`** rules are loaded for these checks. Throws **`LocalizedException`** with combined rule messages (` \| `) on place order. |
| **pending_review** | **Non-blocking at checkout:** not included in **`QuoteRulesValidator`**. After a successful order, **`OrderRestrictionPendingReviewMatcher`** reloads the quote, copies **payment method** from the **order** onto the quote (so **Payment method** conditions match), recomputes totals + **`QuoteFraudFlagCalculator`**, validates **pending_review** rules, and adds **structured** entries to the hold list (see **Reason payloads**). |

**Custom conditions** (Conditions tab → **Order Pending Review**): **`Fraud flag (1 or 2)`**, **`Payment method`** (e.g. **`dcw_po_gateway`**, **`splitpayment`**) — use **pending_review** rules so PO/split (and any gateway) are driven by admin rules, not hardcoded PHP.

**Fraud flag** (`quote.dcw_fraud_flag` / order copy): set by **`QuoteFraudFlagCalculator`** using thresholds in **`Model/FraudFlag.php`**. **`ComputeQuoteFraudFlagBeforeOrderPlace`** runs before the Dotcomweavers **beforePlace** plugin so **`FraudFlagEquals`** conditions see a current flag.

**Login as customer:** **`QuoteRulesValidator`** / **`CheckRestriction`** still respect **`orderrestrictions/general/enable_admin_login_as_customer`** and skip blocking validation when the admin is shopping as the customer. **`OrderRestrictionPendingReviewMatcher`** does **not** skip in that mode so **pending_review** reasons can still be recorded.

## Behavior (runtime)

1. **`HoldReasonCollector`** (`Api/HoldReasonCollectorInterface`): delegates entirely to **`OrderRestrictionPendingReviewMatcher`** (all hold reasons must come from **pending_review** rules — e.g. conditions **Payment method** = `dcw_po_gateway` or `splitpayment`, **Fraud flag**, cart/product rules, etc.).

2. **`ApplyPendingReviewAfterPlaceOrder`** (`sales_order_place_after`):
   - Loads quote when possible; **`collectTotals()`** + **`QuoteFraudFlagCalculator::apply()`** + save; copies **`dcw_fraud_flag`** to the order.
   - **`holdReasonCollector->collect()`**; if reasons non-empty, sets **`dcw_pending_review_reasons`** JSON, **state** / **status** as above, saves order; resets **`dcw_failed_payment_attempts`** on the quote when appropriate.

3. **Failed place-order attempts:** (a) **`aroundSavePaymentInformationAndPlaceOrder`** on **`PaymentInformationManagementInterface`** / **`GuestPaymentInformationManagementInterface`** (`sortOrder` **100**, inside ParadoxLabs TokenBase **10**) — catches any **Throwable** from REST checkout when those services are invoked. (b) **`Magento_AsyncOrder`** registers the **same** `POST …/payment-information` routes on **`AsyncPaymentInformationGuestPublisherInterface`** / **`AsyncPaymentInformationCustomerPublisherInterface`**; when async order is enabled and the payment method uses the async path, placement does **not** delegate to (a) — **`Plugin\\AsyncOrder\\AsyncPaymentInformation*PublisherPlugin`** covers that. (c) **`aroundPlaceOrder`** on **`GuestCartManagementInterface`** — guest **two-step** checkout (**`POST …/set-payment-information`** then **`PUT …/guest-carts/:id/order`**) never hits (a). (d) **`sales_model_service_quote_submit_failure`** when **`orderManagement::place`** throws inside **`QuoteManagement::submitQuote`**. **`QuoteFailedPaymentTracker`** uses **request registry** deduplication plus **deferred** (shutdown) **atomic** `UPDATE … SET dcw_failed_payment_attempts = IFNULL(…)+1` so later quote saves during payment failure handling cannot overwrite the counter in the same request. **`Plugin\\SplitPayment\\PlaceOrderPlugin`**: split payment JSON **success: 0**. **Guest carts:** REST passes a **masked** cart id — **`QuoteFailedPaymentTracker`** resolves it with **`MaskedQuoteIdToQuoteIdInterface`** before loading the quote. Checkout / placeOrder / async publisher plugins **do not** increment on **`AuthorizationException`** (e.g. TokenBase session lockout); the quote-submit observer also skips **`AuthorizationException`**. Registry deduplication prevents double increments when overlapping hooks run for the same error.

4. **Repository plugins** (`OrderRepositoryInterface`, `CartRepositoryInterface`): extension attributes **`dcw_pending_review_reasons`**, **`dcw_failed_payment_attempts`**, **`dcw_fraud_flag`** where declared.

5. **Admin:** **`AddOrderRestrictionApplyTypeField`**, **`AddSalesRuleCombineConditions`** — registers **Fraud flag** and **Payment method** conditions for Order Restrictions.

## Reason payloads (`dcw_pending_review_reasons` JSON)

Each entry is a structured object from a matched **pending_review** rule: **`type`** = **`order_restriction`**, plus **`restriction_id`**, **`name`**, **`message`**, **`title`**, **`internal_notes`**, etc.

**`PendingReviewReason::labels()`** still maps legacy **string** codes (**`purchase_order`**, **`split_payment`**) on older orders for the admin grid; new placements should use rules only.

## Database (`etc/db_schema.xml`)

| Table | Column | Notes |
|-------|--------|--------|
| `quote` | `dcw_failed_payment_attempts` | Failed place-order attempts. |
| `quote` | `dcw_fraud_flag` | `0` / `1` / `2` — see **`FraudFlag`**. |
| `sales_order` | `dcw_pending_review_reasons` | JSON array. |
| `sales_order` | `dcw_fraud_flag` | Copied from quote after place when possible. |
| `orderrestriction_rules` | `apply_type` | **`restriction`** \| **`pending_review`**. |

After schema changes: `bin/magento setup:upgrade`

## Extension attributes (`etc/extension_attributes.xml`)

- **Order:** `dcw_pending_review_reasons`, `dcw_fraud_flag`
- **Quote:** `dcw_failed_payment_attempts`, `dcw_fraud_flag`

## Deploy

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Admin UI

- **Order view:** **Pending Review Reason(s)** when JSON is set; fraud flag when present.
- **Orders grid:** column for pending review reasons (`Plugin\Ui\SalesOrderGridDataProviderPlugin`).

## Developer notes

- **Early checkout:** **`Dotcomweavers\OrderRestrictions\Controller\Index\CheckRestriction`** uses **`QuoteRulesValidator::collectBlockingRuleViolations()`** (restriction rules only) plus fraud flag + totals; template **`Dotcomweavers_OrderRestrictions/view/frontend/templates/onepage_js.phtml`** wires the shipping-step AJAX.
- **NetSuite / exports:** treat **`po_pending_review`** and/or non-empty **`dcw_pending_review_reasons`** per your integration rules.
- **DI:** run **`setup:di:compile`** after constructor changes.
