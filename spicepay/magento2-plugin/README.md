# Magento 2 SpicePay Plugin

Sign up for SpicePay account at <https://spicepay.com> for production and <https://sandbox.spicepay.com> for testing (sandbox) environment.

Please note, that for "Test" mode you **must** generate separate API credentials on <https://sandbox.spicepay.com>. API credentials generated on <https://spicepay.com> will **not** work for "Test" mode.

## Installation via Composer

You can install Magento 2 SpicePay plugin via [Composer](http://getcomposer.org/). Run the following command in your terminal:

1. Go to your Magento 2 root folder.

2. Enter following commands to install plugin:

    ```bash
    composer require spicepay/magento2-plugin
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable plugin:

    ```bash
    php bin/magento module:enable SpicePay_Merchant --clear-static-content
    php bin/magento setup:upgrade
    ```

## Installation via Magento Marketplace

1. Go to https://marketplace.magento.com/spicepay-magento2-plugin.html (you will have login and register on Magento Marketplace).

2. “Purchase” the plugin (it is free, but it is called a “Purchase” by Magento Marketplace).

3. Login to your Magento admin panel and go to `System > Web Setup Wizard > Component Manager`.

4. Click "**Sign in** to sync your Magento Marketplace purchases" (to generate the public and private keys needed for signin - login to your Magento Marketplace account, go to `My Account > My Access Keys > Magento 2` and click **Create A New Access Key**).

5. Click **Sync**, wait for Magento to sych your Marketplace purchases and click **Install**.

6. Click **Install** next to `spicepay/magento2-plugin`.

7. Click **Start Readiness Check** > **Next** > **Create Backup** (optional) > **Next** > **Install**.

## Plugin Configuration

Enable and configure SpicePay plugin in Magento Admin under `Stores / Configuration / Sales / Payment Methods / Bitcoin and Altcoins via SpicePay`.
