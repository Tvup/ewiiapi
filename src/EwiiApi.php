<?php

namespace Tvup\EwiiApi;

class EwiiApi extends EwiiApiBase implements EwiiApiInterface
{

    public function login(string $email, string $password)
    {
        if('' == $email || '' == $password) {
            throw new EwiiApiException(['Email and password cannot be blank'], [], '1');
        }
        return $this->makeErrorHandledRequest('POST', null, 'Login', null, [
            'Email' => $email,
            'Password' => $password,
            'scAction' => 'EmailLogin',
            'scController' => 'Auth',
        ]);
    }

    public function getAddressPickerViewModel() {
        $json = $this->makeErrorHandledRequest('POST', 'api/', 'product/GetAddressPickerViewModel', null, null, true);
        return json_decode($json, true)['Elements'][0];
    }

    public function setSelectedAddressPickerElement($payload) {
        $json = $this->makeErrorHandledRequest('POST', 'api/', 'product/SetSelectedAddressPickerElement', null, $payload, true);
        $json = json_decode($json, true);
        return ['ok'];
    }

    public function getConsumptionMeters() {
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

    public function getConsumptionData(string $fileType, array $parameters):array
    {
        $data = $this->makeErrorHandledRequest('GET', 'api/', 'consumption/' . $fileType, $parameters,null, true);

        $dataArray = explode(PHP_EOL, $data);

        array_shift($dataArray); //First line is "sep=" for some reason
        array_shift($dataArray); //Second line is table headers

        $returnArray = array();

        foreach ($dataArray as $line) {
            $lineArray = explode(';', $line);
            if(count($lineArray)>1) {
                array_push($returnArray, [$lineArray[0] => $lineArray[1]]);
            }
        }

        return $returnArray;
    }
}