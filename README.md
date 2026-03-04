# Briqpay Payments for Hyvä Checkout

This extension integrates the [Briqpay](https://briqpay.com) payment gateway
with [Hyvä Checkout](https://hyva.io/checkout).

**Author:** [Control Alt Delete BV](https://controlaltdelete.nl)

---

## Requirements

- PHP >= 8.3
- Magento 2.4.8
- Hyvä Checkout >= 1.3.8
- `hyva-themes/magento2-compat-module-fallback`
- `briqpay/module-payments` (base Briqpay module)

## Installation

Install via Composer:

```bash
composer require briqpay/module-payments-hyva-checkout
bin/magento module:enable Briqpay_PaymentsHyvaCheckout
```

## Configuration

This module does not introduce any new configuration options. It does have support for these options from the Briqpay
module:

- **Payment Overlay** — Show a loading overlay while the payment is being processed.
- **Custom Decision Logic** — Enable custom decision handling via DOM events (
  see [Custom Decision Logic](#custom-decision-logic) below).

### Payment Overlay

When **Payment Overlay** is enabled, a loading indicator is shown while the payment is being processed. It subscribes
to the Briqpay `paymentProcessStarted` and `paymentProcessCancelled` events to show and hide the overlay respectively.

### Custom Decision Logic

When **Custom Decision Logic** is enabled, the module emits a `briqpayDecision` DOM event before finalising the
payment. Your custom code can listen to this event, perform additional checks, and respond with a
`briqpayDecisionResponse` event:

```js
document.addEventListener('briqpayDecision', function (e) {
    // e.detail.data contains the Briqpay session data
    const approved = true; // your custom logic here

    document.dispatchEvent(new CustomEvent('briqpayDecisionResponse', {
        detail: {decision: approved}
    }));
});
```

If no response is received within 10 seconds, the decision defaults to `true`.

## Running Tests

End-to-end tests use [Playwright](https://playwright.dev):

```bash
# From the extension root
npx playwright test --ui
```

### Required store views

The tests expect two store views to be present:

| Store view code | Name               | Theme            | Checkout type |
|-----------------|--------------------|------------------|---------------|
| `hyva_default`  | Default Store View | Hyvä Default CSP | Default       |
| `hyva_onepage`  | Onepage Store View | Hyvä Default CSP | Onepage       |

The `onepage` store view must have `hyva_themes_checkout/general/checkout` set to `onepage`, and `web/url/use_store` to
`1`.

### Available test suites

- `tests/End-2-End/checkouts/default.spec.ts` — places a successful order in the default Hyvä Checkout
- `tests/End-2-End/checkouts/onepage.spec.ts` — places a successful order in the Hyvä Checkout with the onepage
  configuration and verifies that the Briqpay iframe price updates when the shipping method changes

Override the base URL with the `BASE_URL` environment variable:

```bash
BASE_URL=https://your-store.example.com npx playwright test
```

## Support

- **Briqpay support:** support@briqpay.com
- **Developer guide:** https://cdn.briqpay.com/static/plugins/Magento/Briqpay-M2-Extension-developer-guide.pdf

OSL-3.0 or AFL-3.0
