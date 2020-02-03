<?php
/**
 * Receive currencies Source Model
 *
 * @category    SpicePay
 * @package     SpicePay_Merchant
 * @author      SpicePay
 * @copyright   SpicePay (https://spicepay.com)
 * @license     https://github.com/spicepay/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace SpicePay\Merchant\Model\Source;

class Receivecurrencies
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
          ['value' => 'btc', 'label' => 'Bitcoin (฿)'],
          ['value' => 'usdt', 'label' => 'USDT'],
          ['value' => 'eur', 'label' => 'Euros (€)'],
          ['value' => 'usd', 'label' => 'US Dollars ($)'],
          ['value' => 'DO_NOT_CONVERT', 'label' => 'Do not convert']
        ];
    }
}
