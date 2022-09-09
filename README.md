# ewiiapi
This repository contains PHP library code to facilitate communication with Ewii () Api's. It is intended to be loaded into another PHP application as a composer package through packagist.
The API exposes function for login and for getting consumption data as an Ewii customer


## Prerequisites
In order to complete the instructions below, you need to have PHP > 7.1 installed locally. You also need a working composer installation locally.

Requires access to login at https://selvbetjening.ewii.com/Login with E-mail and password.

## Installation with composer
```
composer require tvup/ewiiapi
```

### Example code
```
require_once 'vendor/autoload.php';

$ewiiApi = new Tvup\EwiiApi\EwiiApi();
//$ewiiApi->setDebug(true);

$ewiiApi->login('AN_EMAIL_ADDRESS','A_PASSWORD');

$addressElement = $ewiiApi->getAddressPickerViewModel();

$ewiiApi->setSelectedAddressPickerElement($addressElement);

$consumptionMeterMetaData = $ewiiApi->getConsumptionMeters();

$response = $ewiiApi->getConsumptionData('csv', $consumptionMeterMetaData);

print_r($response);
```


