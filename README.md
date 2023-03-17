# Loqate Magento 2 API Integration

## What is the Loqate API Integration?
Performs address capture and data validation (email, phone number and address) using Loqate API.

##Download
###Download via composer

Request composer to fetch the module:
```
composer require loqate-integration/adobe
```

###Manual Download

Download & copy the git content to /app/code/Loqate/ApiIntegration

## Install

Please run the following commands after you download the module
```
php bin/magento module:enable Loqate_ApiIntegration
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

## Configuration Instructions
The configuration for the module is located under Stores -> Configuration -> Loqate.
