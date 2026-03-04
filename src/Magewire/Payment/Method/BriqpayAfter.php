<?php

namespace Briqpay\PaymentsHyvaCheckout\Magewire\Payment\Method;

use Briqpay\Payments\Model\PaymentModule\UpdateSession;
use Briqpay\Payments\Model\Utility\MakeDecisionLogic;
use Briqpay\Payments\Model\Utility\ScopeHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;

class BriqpayAfter extends Component
{
    public bool $decisionSuccess = false;

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
        private readonly Session $checkoutSession,
        private readonly UpdateSession $updateSession,
        private readonly MakeDecisionLogic $makeDecisionLogic,
        private readonly ScopeHelper $scopeHelper,
        private readonly SessionManagerInterface $sessionManager,
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    public function makeDecision(string $sessionId, ?string $email = null): void
    {
        $quote = $this->checkoutSession->getQuote();
        $briqpayEmail = $quote->getPayment()->getAdditionalInformation('briqpay_email');
        $email = $quote->getCustomerEmail() ?? $email ?? $briqpayEmail;

        $quote->getShippingAddress()->setEmail($email);
        $quote->getBillingAddress()->setEmail($email);
//
        $this->decisionSuccess = $this->makeDecisionLogic->makeDecision($sessionId, $email);
    }

    public function showPaymentOverlay(): bool
    {
        $value = $this->scopeHelper->getScopedConfigValue('payment/briqpay/advanced/payment_overlay');
        return $value === '1'; // Convert "1" to true, otherwise false
    }

    public function updateEmail(string $email): void
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setAdditionalInformation('briqpay_email', $email);
        $this->cartRepository->save($this->checkoutSession->getQuote());
    }

    public function updateSession(): void
    {
        $sessionId = $this->sessionManager->getData('briqpay_session_id');
        if (!$sessionId) {
            return;
        }

        $this->updateSession->updateSession($sessionId);
    }

    public function useCustomDecisionLogic(): bool
    {
        $value = $this->scopeHelper->getScopedConfigValue('payment/briqpay/advanced/custom_decision');
        return $value === '1'; // Convert "1" to true, otherwise false
    }
}
