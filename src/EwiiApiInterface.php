<?php

namespace Tvup\EwiiApi;

interface EwiiApiInterface
{
    public function login(string $email, string $password): void;

    public function getAddressPickerViewModel(): array;

    public function setSelectedAddressPickerElement(array $payload): void;

    public function getConsumptionMeters(): array;

    public function getConsumptionData(string $fileType, array $parameters): array;
}