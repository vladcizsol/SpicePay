<?php

/**
 * SpicePay test API authorization controller
 *
 * @category    SpicePay
 * @package     SpicePay_Merchant
 * @author      SpicePay
 * @copyright   SpicePay (https://spicepay.com)
 * @license     https://github.com/spicepay/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */

namespace SpicePay\Merchant\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SpicePay\SpicePay;
use \Magento\Store\Model\ScopeInterface;
use SpicePay\Merchant\Model\Payment\Interceptor;

class TestConnection extends Action
{

    protected $checkoutSession;
    protected $scopeConfig;


    public function __construct(
        Context $context,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig
    )

    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }


    public function execute()
    {
        if (!$this->scopeConfig->getValue('payment/spicepay_merchant/api_auth_token', ScopeInterface::SCOPE_STORE)) {
            $this->getResponse()->setBody(json_encode([
                'status' => false,
                'reason' => "No API Token entered",
            ]));
                return;
        }

        $test =  SpicePay::testConnection(SpicePay::config([
            'environment' => $this->scopeConfig->getValue('payment/spicepay_merchant/sandbox_mode',ScopeInterface::SCOPE_STORE) ? 'sandbox' : 'live',
            'auth_token'  => $this->scopeConfig->getValue('payment/spicepay_merchant/api_auth_token', ScopeInterface::SCOPE_STORE),
            'user_agent'  => 'SpicePay - Magento 2 Extension v' . Interceptor::spicepay_MAGENTO_VERSION
        ]));

        if($test !== true) {

            $this->getResponse()->setBody(json_encode([
                'status' => false,
                'reason' => $test,
            ]));
                return;
        } else {

            $this->getResponse()->setBody(json_encode([
                'status' => true,
            ]));
                return;
        }
    }


}
