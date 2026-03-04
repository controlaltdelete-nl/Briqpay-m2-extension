<?php

namespace Briqpay\PaymentsHyvaCheckout\Magewire\Payment\Method;

use Briqpay\Payments\Logger\Logger;
use Briqpay\Payments\Model\PaymentModule\CreateSession;
use Briqpay\Payments\Model\PaymentModule\ReadSession;
use Briqpay\Payments\Model\Utility\ReinitialiseSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\SessionManagerInterface;
use Magewirephp\Magewire\Component;

class Briqpay extends Component
{
    public array $session = [];
    protected $listeners = [
        'shipping_method_selected' => 'updateSession',
        'payment_method_selected' => 'updateSession',
        'coupon_code_applied' => 'updateSession',
        'coupon_code_revoked' => 'updateSession',
        'shipping_address_saved' => 'updateSession',
        'shipping_address_activated' => 'updateSession',
        'billing_address_saved' => 'updateSession',
        'billing_address_activated' => 'updateSession'
    ];

    public function __construct(
        private readonly SessionManagerInterface $sessionManager,
        private readonly CheckoutSession $checkoutSession,
        private readonly Logger $logger,
        private readonly ReadSession $readSession,
        private readonly ReinitialiseSession $reinitialiseSession,
        private readonly CreateSession $createSession,
    ) {}

    public function getIframe(): string
    {
        $sessionId = $this->sessionManager->getData('briqpay_session_id');
        $quote = $this->checkoutSession->getQuote();

        $briqpayEmail = $quote->getPayment()->getAdditionalInformation('briqpay_email');
        $guestEmail = $quote->getCustomerEmail() ?? $briqpayEmail;

        if ($sessionId) {
            $readSessionData = $this->readSession->getSession($sessionId);
        }
        $triggerNewSession = false;

        if ($sessionId && ($quote->getId() != $readSessionData['references']['quoteId'])) {
            $this->logger->info('Quotes did not match, starting new session');
            $triggerNewSession = true;
        }

        if ($sessionId && !$triggerNewSession) {
            $this->session = $this->reinitialiseSession->reinitialiseSession($sessionId, $guestEmail);
        } else {
            $briqpaySessionId = $quote->getBriqpaySessionId();
            if (!is_null($briqpaySessionId) && !$triggerNewSession) {
                $this->session = $this->reinitialiseSession->reinitialiseSession($briqpaySessionId, $guestEmail);
                $this->sessionManager->setBriqpaySessionId($briqpaySessionId);
            } else {
                $this->session = $this->createSession->getPaymentModule($guestEmail);
                $this->sessionManager->setBriqpaySessionId($this->session['sessionId']);
            }
        }

        $output = $this->session['htmlSnippet'];
        $output = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $output);

        $this->dispatchBrowserEvent('briqpay-update-iframe-html', $output);

        return $output;
    }

    public function isQuoteValid(): bool
    {
        $quote = $this->checkoutSession->getQuote();

        $address = $quote->getShippingAddress();
        if (!$address ||
            !$address->getShippingMethod() ||
            !$address->getShippingDescription()
        ) {
            return false;
        }

        return true;
    }

    public function updateSession(): void
    {
        if (!$this->isQuoteValid()) {
            return;
        }

        $this->getIframe();
    }
}
