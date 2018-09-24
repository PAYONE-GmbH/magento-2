[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PAYONE-GmbH/magento-2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PAYONE-GmbH/magento-2/?branch=master) [![Build Status](https://travis-ci.org/PAYONE-GmbH/magento-2.svg?branch=master)](https://travis-ci.org/PAYONE-GmbH/magento-2)

# PAYMENT FOR YOUR MAGENTO 2-SHOP
The PAYONE payment extension for MAGENTO 2 is massively reworked since the first version. The Github-Community was important for this development. Take a look at our last released version and send us commits or other feedback to take care for the best possible solution. Your feedback is very important to us to ensure a flawless code and rapid development.

## Important functions for Magento 2
*	Seamless integration of Magento's OnePage Checkout
*	Very wide range of functions, simple and comprehensible supported by detailed online help
*	Central configuration and management options on the Magento Admin Panel
*	Export function of the configuration in XML format to facilitate the technical support
*	Optional multi-partial capture for partial deliveries
*	Supports simplified PCI DSS conformity in accordance with SAQ A
*	Payment methods can be automatically hidden depending on the amount of the order and the credit rating of the customer
*	Find all currently supported payment methods on www.payone.com/magento

## Installation
*	Go to your installation directory of Magento 2 and perform the following commands
*	composer require payone-gmbh/magento-2
*	php bin/magento setup:upgrade
*	php bin/magento setup:di:compile
*	php bin/magento cache:clean

## Compatibility issues
To make this extension Magento 2.2 compatible, all Unit Tests have been modified to work with PHPUnit 6, which breaks compatibility towards older versions of PHPUnit. Please run all Unit Tests in PHPUnit 6, regardless of your Magento version.

As of 05/07/2018 this seems, however, broken. For the time being, Unit Tests are carried out on Magento 2.2 alone during the Travis build.

## Magento Version Support
We follow Magento's version lifetime according to their [Enterprise Agreement](https://magento.com/legal/terms/enterprise-agreement#support). In theory, Magento 2.0 is still supported, but not actively tested.

## More information
*	More information about Magento on https://www.payone.de/magento

## Contact
PAYONE GmbH<br>
Office Kiel<br>
Fraunhoferstraße 2-4<br>
24118 Kiel, Germany<br>
Phone +49 431 25968-400 Fax +49 431 25968-1400<br>
magento@payone.com<br>
www.payone.com/magento

## License
See our License Agreement at: https://www.payone.de/fileadmin/downloads/sonstiges/PAYONE_Haftungs_und_Lizenzvereinbarung_Extensions.pdf

## About BS PAYONE
Since the end of August 2017, the two payment specialist companies PAYONE and B+S Card Service merged to become BS PAYONE GmbH. All current partnerships will be maintained the way they are. APIs, interfaces, and other technical parameters will stay the same. Your current contact persons will continue to gladly be at your service.<br>
BS PAYONE GmbH is headquartered in Frankfurt am Main and is one of the leading omnichannel payment providers in Europe. In addition to providing customer support to numerous Sparkasse banks, the full-service payment service provider also provides cashless payment transactions services to more than 255,000 customers from various branches – whether that be in stationary retail or when completing e-commerce and mobile payment transactions.
