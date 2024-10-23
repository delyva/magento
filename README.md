# Magento 2 DelyvaX Dynamic Shipping rates extension/plugin

This module integrate Magento 2 with DelyvaX Shipment to enable fetching shipping rates from Delyvax Shipment dynamically. Tested on Magento version  v2.3.5 - 2.4.6.

## Install Magento 2 DelyvaX Dynamic Shipping rates extension
Download the code and place it inside `magento_root/app/code/Delyvax/Shipment/`
Run the following command in Magento 2 root directory:

```
php bin/magento module:enable Delyvax_Shipment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Configure DelyvaX Shipping in Magento 2
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > Sales > Delivery Methods`. Then, click on `DelyvaX Shipping Method` to configure Delyvax Credentials and settings.

- **DelyvaX Account Credentials** [(screenshot)](https://prnt.sc/6A99bPtlWY6h)
- **DelyvaX Settings** [(screenshot)](https://prnt.sc/w5p83YWzPl-I)
- **DelyvaX Shipping Rate Adjustment** [(screenshot)](https://prnt.sc/WUeuR-Ea_tSo)

### Setting Store Origin Address in Magento 2
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > Sales > Shipping Settings > Origin` [(screenshot)](https://prnt.sc/10tzaj3)

### Setting default Currency
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > Currency Setup > Currency options` [(screenshot)](https://prnt.sc/10tzb41)

### Setting default Weight Unit to Kilograms instead of Pounds
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > Locale Options > Weight Unit` [(screenshot)](https://prnt.sc/10tzbq7)

With this settings, user should be able to get Delyvax Shipping rates and carriers dynamically on checkout. [screenshot](https://prnt.sc/10tzewv)

### Setting Store information (name etc) that will be sent to DelyvaX on Order creation
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > General > Store Information` [(screenshot)](https://prnt.sc/11mqbz3)

### DelyvaX Shipping Rate Adjustment - Configuration Details
This configuration is to update the DelyvaX shipping rates on checkout page. Magento Admin can +Markup or give -Discount to their customer on DelyvaX shipping rates based on this configuration settings.

- **Enable Rate Adjustment:** Yes/No (to enable/disable rate adjustment)
- **Adjustment Type:** Markup/Discount (to increase DelyvaX rates - select Markup, and to decrease DelyvaX rates - select Discount)
- **Adjustment Form:** Percentage/Flat (select this form to apply rate adjustment in flat rate or percentage)
- **Adjustment Amount:** Open text field that will accept a number - added number will be applied as rates adjustment on DelyvaX Shipping rates
- **Rate Adjustment Rule:** Admin can add a [Cart price rule](https://prnt.sc/yI15okzFbdmC) in `Marketing > Promotions > Cart Price Rules` following this [example](https://prnt.sc/MwyQMx0anCo0) and Rate Adjustment will be applied only if conditions fulfill. If none is selected on this setting, Rate Adjustment will be applied straightaway on cart
- **Service Providers:** Add `'*'` in this field to apply Rate Adjustment on all DelyvaX Shipping rates, or add comma-separated specific DelyvaX service provider's code `DHLEC-MY,GDEXMY-WM`

### To update plugin as per the latest code
Download/Clone latest code from repository and paste it in `magento_root/app/code/Delyvax/Shipment/` directory, then run the following commands in Magento 2 root directory:

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
