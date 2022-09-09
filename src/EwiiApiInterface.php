<?php

namespace Tvup\EwiiApi;

interface EwiiApiInterface
{
    public function login(string $email, string $password);

    public function getAddressPickerViewModel();

    public function setSelectedAddressPickerElement($payload);

    public function getConsumptionData(string $fileType, array $parameters) : array;
}