<?php

namespace Tvup\EwiiApi;

interface EwiiApiInterface
{
    public function login(string $email, string $password);

    public function getAddressData();

    public function getConsumptionData(string $fileType, array $parameters) : array;
}