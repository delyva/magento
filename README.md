# Magento 2 DelyvaX Dynamic Shipping rates extension/plugin

This module integrate Magento 2 with DelyvaX Shipment to enable fetching shipping rates from Delyvax Shipment dynamically. Tested on Magento version  v2.3.5 - 2.4.2.

## Install Magento 2 DelyvaX Dynamic Shipping rates extension
Download the code and place it inside `magento_root/app/code/Delyvax/Shipment/`
Run the following command in Magento 2 root directory:

```
php bin/magento module:enable Delyvax_Shipment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

## Configure DelyvaX Shipping in Magento 2
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > Sales > Shipping methods`. Then, click on `DelyvaX Dynamic Shipping rates` to configure Delyvax Credentials and settings.

- **DelyvaX Account Credentials** [(screenshot)](https://prnt.sc/10tz5no)
- **DelyvaX Settings** [(screenshot)](https://prnt.sc/10tz8zy)
- **DelyvaX Shipping Rate Adjustment** [(screenshot)](https://prnt.sc/10tz9l5)

### Setting Store Origin Address in Magento 2
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > Sales > Shipping Settings > Origin` [(screenshot)](https://prnt.sc/10tzaj3)

### Setting default Currency
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > Currency Setup > Currency options` [(screenshot)](https://prnt.sc/10tzb41)

### Setting default Weight Unit to Kilograms instead of Pounds
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > Locale Options > Weight Unit` [(screenshot)](https://prnt.sc/10tzbq7)

With this settings, user should be able to get Delyvax Shipping rates and carriers dynamically on checkout. [screenshot](https://prnt.sc/10tzewv)

### Setting Store information (name etc) that will be sent to DelyvaX on Order creation
From your Magento admin panel, follow this route: `Stores > Settings > Configuration > General > General > Store Information` [(screenshot)](https://prnt.sc/11mqbz3)

### To update plugin as per the latest code
Download/Clone latest code from repository and paste it in `magento_root/app/code/Delyvax/Shipment/` directory, then run the following commands in Magento 2 root directory:

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
