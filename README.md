# Magento 2 DelyvaX Dynamic Shipping rates extension/plugin

This module integrate Magento 2 with DelyvaX Shipment to enable fetching shipping rates from Delyvax Shipment dynamically.

## Install Magento 2 DelyvaX Dynamic Shipping rates extension
Download the code and place it inside `magento_root/app/code/DelyvaX/Shipment/`
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
