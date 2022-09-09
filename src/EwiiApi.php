<?php

namespace TorbenIT\EwiiApi;

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

    public function getConsumptionData(string $fileType, string $installationNumber, int $consumerNumber, int $meterId, int $counterId, int $type, int $utility, string $unit, string $factoryNumber):array
    {
        $data = $this->makeErrorHandledRequest('GET', 'api/', 'consumption/csv', [
            'installationNumber' => $installationNumber,
            'consumerNumber' => $consumerNumber,
            'meterId' => $meterId,
            'counterId' => $counterId,
            'type' => $type,
            'utility' => $utility,
            'unit' => $unit,
            'factoryNumber' => $factoryNumber,
        ],null, true);

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