<?php
/**
 * SpicePay payment method model
 *
 * @category    SpicePay
 * @package     SpicePay_Merchant
 * @author      SpicePay
 * @copyright   SpicePay (https://spicepay.com)
 * @license     https://github.com/spicepay/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace SpicePay\Merchant\Model;

use SpicePay\SpicePay;
use SpicePay\Merchant as SpicePayMerchant;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;

class Payment extends AbstractMethod
{
    const spicepay_MAGENTO_VERSION = '1.2.6';
    const CODE = 'spicepay_merchant';

    protected $_code = 'spicepay_merchant';

    protected $_isInitializeNeeded = true;

    protected $urlBuilder;
    protected $spicepay;
    protected $storeManager;
    protected $orderManagement;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param SpicePayMerchant $spicepay
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param OrderManagementInterface $orderManagement
     * @param array $data
     * @internal param ModuleListInterface $moduleList
     * @internal param TimezoneInterface $localeDate
     * @internal param CountryFactory $countryFactory
     * @internal param Http $response
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        OrderManagementInterface $orderManagement,
        SpicePayMerchant $spicepay,
        array $data = [],
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null

    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->orderManagement = $orderManagement;
        $this->spicepay = $spicepay;

        \SpicePay\SpicePay::config([
            'environment' => $this->getConfigData('sandbox_mode') ? 'sandbox' : 'live',
            'auth_token'  => $this->getConfigData('api_auth_token'),
            'user_agent'  => 'SpicePay - Magento 2 Extension v' . self::spicepay_MAGENTO_VERSION
        ]);
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getSpicePayRequest(Order $order)
    {
        $token = substr(md5(rand()), 0, 32);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation('spicepay_order_token', $token);
        $payment->save();

        $description = [];
        foreach ($order->getAllItems() as $item) {
            $description[] = number_format($item->getQtyOrdered(), 0) . ' × ' . $item->getName();
        }

        $params = [
            'order_id' => $order->getIncrementId(),
            'price_amount' => number_format($order->getGrandTotal(), 2, '.', ''),
            'price_currency' => $order->getOrderCurrencyCode(),
            'receive_currency' => $this->getConfigData('receive_currency'),
            'callback_url' => ($this->urlBuilder->getUrl('spicepay/payment/callback') .
               '?token=' . $payment->getAdditionalInformation('spicepay_order_token')),
            'cancel_url' => $this->urlBuilder->getUrl('spicepay/payment/cancelOrder'),
            'success_url' => $this->urlBuilder->getUrl('spicepay/payment/returnAction'),
            'title' => $this->storeManager->getWebsite()->getName(),
            'description' => join($description, ', '),
            'token' => $payment->getAdditionalInformation('spicepay_order_token')
        ];

        $cgOrder = \SpicePay\Merchant\Order::create($params);

        if ($cgOrder) {
            return [
                'status' => true,
                'payment_url' => $cgOrder->payment_url
            ];
        } else {
            return [
                'status' => false
            ];
        }
    }

    /**
     * @param Order $order
     */
    public function validateSpicePayCallback(Order $order)
    {

        try {
            if (!$order || !$order->getIncrementId()) {
                $request_order_id = (filter_input(INPUT_POST, 'order_id')
                    ? filter_input(INPUT_POST, 'order_id') : filter_input(INPUT_GET, 'order_id')
                );

                throw new \Exception('Order #' . $request_order_id . ' does not exists');
            }

            $payment = $order->getPayment();
            $get_token = filter_input(INPUT_GET, 'token');
            $token1 = $get_token ? $get_token : '';
            $token2 = $payment->getAdditionalInformation('spicepay_order_token');

            if ($token2 == '' || $token1 != $token2) {
                throw new \Exception('Tokens do match.');
            }

            $request_id = (filter_input(INPUT_POST, 'id')
                ? filter_input(INPUT_POST, 'id') :  filter_input(INPUT_GET, 'id'));
            $cgOrder = \SpicePay\Merchant\Order::find($request_id);

            if (!$cgOrder) {
                throw new \Exception('SpicePay Order #' . $request_id . ' does not exist');
            }

            if ($cgOrder->status == 'paid') {
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                $order->save();
            } elseif (in_array($cgOrder->status, ['invalid', 'expired', 'canceled', 'refunded'])) {
                $this->orderManagement->cancel($cgOrder->order_id);

            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
    }
}
