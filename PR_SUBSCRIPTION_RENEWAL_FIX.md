# Fix: ConvesioPay subscription renewals stuck in Pending payment

## Title (for PR)

**Fix: ConvesioPay subscription renewals stuck in Pending payment (NO2U / token handling)**

---

## Issue

Subscriptions processed via the original ConvesioPay (CPay) plugin get stuck in **Pending payment** instead of completing or failing clearly. Renewal orders never receive a successful payment or webhook, so they stay in limbo and contribute to loss of subscribers and support escalations.

**Context:** NO2U store; multiple renewal orders (e.g. 409710, 409711, 409714, 409716) reported stuck in Pending payment.

---

## Root cause

1. **Token validation:** Recurring payment tokens (`_cpay_recurringDetailReference` / `_adn_recurringDetailReference`) were sometimes stored as empty strings. The code used `empty()`, which can treat whitespace-only values as non-empty, so the plugin used invalid tokens instead of fetching a new one from the API.

2. **Token not saved back:** When a valid token was fetched from the API during renewal, it was not saved back to the parent subscription, so the next renewal had no token again.

3. **Webhook gap:** Payment success webhooks updated the order but did not reliably update the **subscription’s** recurring token, and only one meta prefix was updated. CAPTURE success did not call token collection, so renewals confirmed only via CAPTURE never got the token stored on the subscription.

4. **Wrong fallback:** When the webhook had no recurring reference, the code fell back to `psp_reference` (payment ID), which is not a stored payment method ID and should not be saved as the recurring token.

---

## Fix

- **`includes/payment-methods/class-abstract-gateway.php`**
  - Treat token as invalid when it’s empty or whitespace-only (`trim(...) === ''`), and in that case fetch from the API via `get_renewal_payment_reference()`.
  - When a token is fetched from the API, save it on the subscription for all three meta keys: `_adn_recurringDetailReference`, `_cpay_recurringDetailReference`, `_adn_recurringDetailReference`.
  - When using a stored token, normalize with `trim()`.
  - In `get_renewal_payment_reference()`, safely read `storedPaymentMethods[0]->id` (check array and property exist) and return it; otherwise return `''`.

- **`includes/rest-api/class-rest-api.php`**
  - Add `has_valid_recurring_reference($args)` to require a non-empty, non–whitespace-only `recurr_reference`.
  - In `collect_recurring_reference()`: stop using `psp_reference` as a fallback for the recurring token; only persist when `recurr_reference` is valid.
  - Update both the **renewal order** and the **parent subscription(s)** with the recurring token and shopper reference.
  - Sync both `_cpay_` and `_adn_` meta keys for recurring reference and shopper reference on order and subscription.

- **`includes/rest-api/class-rest-api-hook.php`**
  - Use `REST_API::has_valid_recurring_reference($data)` before calling `collect_recurring_reference()` on AUTHORISATION.
  - On CAPTURE success, when `subscription_ids` and a valid recurring reference are present, call `collect_recurring_reference()` so subscription tokens are updated when the webhook only sends CAPTURE.

---

## Files changed

| File | Change |
|------|--------|
| `includes/payment-methods/class-abstract-gateway.php` | Token validation (empty/whitespace), save fetched token to subscription (3 meta keys), safe token read in `get_renewal_payment_reference()`. |
| `includes/rest-api/class-rest-api.php` | `has_valid_recurring_reference()`, no psp fallback, update order + subscription, sync `_cpay_` and `_adn_`. |
| `includes/rest-api/class-rest-api-hook.php` | Use helper for AUTHORISATION; call `collect_recurring_reference` on CAPTURE for renewals. |

---

## Test cases

- [ ] **New renewal with valid token on subscription**  
  Subscription has valid `_adn_recurringDetailReference`. Run a renewal; payment is sent and order completes when webhook is received.

- [ ] **New renewal with missing/empty token**  
  Subscription has no token or only whitespace. Renewal fetches token from API, saves it to the subscription (all 3 meta keys), sends payment; order completes when webhook is received.

- [ ] **Webhook AUTHORISATION with recurring reference**  
  Send AUTHORISATION webhook for a renewal order with `subscription_ids` and valid `recurringDetailReference`. Order and subscription(s) are updated with token and shopper ref (`_cpay_` and `_adn_`).

- [ ] **Webhook CAPTURE for renewal**  
  Send CAPTURE success webhook for a renewal order with `subscription_ids` and valid recurring reference. `collect_recurring_reference` runs; subscription and order have tokens updated.

- [ ] **Renewal with no token and API returns no token**  
  Subscription has no valid token and `get_renewal_payment_reference()` returns empty. Renewal order is set to **Failed** with an order note; it does not stay in Pending.

- [ ] **Webhook without recurring reference**  
  Webhook has no (or invalid) `recurr_reference`. Recurring token is not updated; `psp_reference` is not saved as the recurring token.

- [ ] **End-to-end: subscription creation then renewal**  
  Create a subscription with CPay, complete initial payment, then run a renewal (e.g. via Action Scheduler or test). Renewal order completes successfully and does not remain in Pending payment.
