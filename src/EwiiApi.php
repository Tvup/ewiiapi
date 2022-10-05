<?php

namespace Tvup\EwiiApi;

class EwiiApi extends EwiiApiBase implements EwiiApiInterface
{

    public function login(string $email, string $password): void
    {
        if('' == $email || '' == $password) {
            throw new EwiiApiException(['Email and password cannot be blank'], [], '1');
        }
        $response = $this->makeErrorHandledRequest('POST', null, 'Login', null, [
            'Email' => $email,
            'Password' => $password,
            'scAction' => 'EmailLogin',
            'scController' => 'Auth',
        ], true);
        if(gettype($response)=='string') {
            if (strpos($response, 'Der var en fejl i dine kundeoplysninger.') !== false) {
                $messages = ['Email and password wasn\'t accepted by Ewii'];
                $this->clearCookieFile();
                throw new EwiiApiException($messages, [], '2');
            }
            if (strpos($response, 'din konto i 15 minutter, pga. for mange fejlede') !== false) {
                $messages = ['Account locked for 15 minutes'];
                $this->clearCookieFile();
                throw new EwiiApiException($messages, [], '2');
            }
        }
    }

    public function getAddressPickerViewModel(): array {
        $json = $this->makeErrorHandledRequest('POST', 'api/', 'product/GetAddressPickerViewModel', null, null, true);
        return json_decode($json, true)['Elements'][0];
    }

    public function setSelectedAddressPickerElement($payload): void {
        $this->makeErrorHandledRequest('POST', 'api/', 'product/SetSelectedAddressPickerElement', null, $payload);
    }

    public function getConsumptionMeters(): array {
        $json = $this->makeErrorHandledRequest('GET', 'api/', 'consumption/meters', ['utility'=>'Electricity'], null, true);
        $json = json_decode($json, true);
        $installationNumber = $json[0]['Installation']['InstallationNumber'];
        $consumerNumber = $json[0]['Installation']['ConsumerNumber'];
        $meterId = $json[0]['MeterId'];
        $counterId = $json[0]['CounterId'];
        $readingType = $json[0]['ReadingType'];
        $utility = $json[0]['Utility'];
        $unit = $json[0]['Unit'];
        $factoryNumber = $json[0]['FactoryNumber'];
        return [
            'installationNumber' => $installationNumber,
            'consumerNumber' => $consumerNumber,
            'meterId' => $meterId,
            'counterId' => $counterId,
            'type' => $readingType,
            'utility' => $utility,
            'unit' => $unit,
            'factoryNumber' => $factoryNumber,
        ];
    }

    public function getConsumptionMetersRaw(): array {
        $json = $this->makeErrorHandledRequest('GET', 'api/', 'consumption/meters', ['utility'=>'Electricity'], null, true);
        return json_decode($json, true);;
    }

    public function getConsumptionData(string $fileType, array $parameters): array
    {
        $data = $this->makeErrorHandledRequest('GET', 'api/', 'consumption/' . $fileType, $parameters,null, true);
        $data = str_getcsv($data,"\n"); //parse the rows
        array_shift($data); //First line is "sep=" for some reason
        array_shift($data); //Second line is table headers
        $returnArray = array();
        foreach($data as $row) {
            $row = str_getcsv($row, ";"); //parse the items in rows
            $returnArray[str_replace(' ','T',$row[0]).':00'] = str_replace(',','.',$row[1]);
        }

        return $returnArray;
    }
}