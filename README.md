# ewiiapi
This repository contains PHP library code to facilitate communication with Ewii Api's. It is intended to be loaded into another PHP application as a composer package through packagist.
The API exposes functions for login and for getting consumption data and the requests neccesary in between.

**Use at own risk**


## Prerequisites
Requires an account at https://selvbetjening.ewii.com/Login with the possibility to login with E-mail and password.

### If you are an existing customer but doesn't have the setup with login using e-mail and password, follow these steps:
* Login as normal (nem-id)
* In upper right corner click "Min profil" (My profile)
* Choose "Mine oplysninger" (My information)
* Find the card "Tildel andre adgang" (Grant access to others) and click "Tildel adgang" (Grant access)
* Follow the steps to setup an account which can access your consumption data. I don't know if it will break anything if you choose an email-adress which Ewii already has - probably choose another email than your primary or use the "google dot"-trick if you're familiary with that.

This should set you up with a secondary login using email and password.


## Installation with composer
```
composer require tvup/ewiiapi
```


## Work-flow of the api
Obviously you will need to make a call through the api to login. This will set a cookie which is used to authenticate you during all other requests.

The flow has these steps:
1. Login
2. Get "AddressPickerViewModel" - a list of your addresses - probably only has one if you don't have products at multiple addresses
  * For each address is a list of installations. Again probably only one, but there shouldn't be much imagination to imagine that you could electricity and internet broadband or multiple electricity installations each with their own meter.
    * Pick "the one"
3. Set "the one" with call to "setSelectedAddressPickerElement" - somehow this seems mandatory else the next request will fail `¯\_(ツ)_/¯`
4. Get metadata for the meter
5. Use some of the metadat to get the consumption data

### Example code
```
require_once 'vendor/autoload.php';

$ewiiApi = new Tvup\EwiiApi\EwiiApi();
//$ewiiApi->setDebug(true);

//1
$ewiiApi->login('AN_EMAIL_ADDRESS','A_PASSWORD');

//2
$addressElement = $ewiiApi->getAddressPickerViewModel();

//3
$ewiiApi->setSelectedAddressPickerElement($addressElement);

//4
$consumptionMeterMetaData = $ewiiApi->getConsumptionMeters();

//5
$response = $ewiiApi->getConsumptionData('csv', $consumptionMeterMetaData);

print_r($response);
```

## Cookies and login
The api is handling the cookies from Ewii and saves them to disk. If a cookie is present at disk, it will be used and login will not be called even though you hit the method `public function login(string $email, string $password): void`
Please remember, that this api wasn't created for you to make tons of requests to Ewii's services and drawing attention.

## Feedback
Always welcome. This project was created because I was curious if it was possible, and I tend to lose interest when the goal has been accomplished.
You wan't something differently - you can make a pull-request we can share a bit of each other's understanding.

## Appreciate it?
Great. Glad I could help.
### I want to make a donation as a sign of appreciation
Don't
#### I insist
It will probably be spent on beer: https://www.buymeacoffee.com/tvup