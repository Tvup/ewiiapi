<?php

namespace TorbenIT\EwiiApi;

interface EwiiApiInterface
{
    public function login(string $email, string $password);

    public function getConsumptionData(string $fileType, string $installationNumber, int $consumerNumber, int $meterId, int $counterId, int $type, int $utility, string $unit, string $factoryNumber) : array;
}