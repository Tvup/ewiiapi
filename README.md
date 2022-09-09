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

$ewiiApi->login('AN_EMAIL_ADDRESS','A_PASSWORD');

$response = $ewiiApi->getConsumptionData('csv', '5153695', 1, 1, 1, 2, 0, 'KWH', '21517435');

print_r($response);
```


