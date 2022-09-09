<?php

namespace TorbenIT\EwiiApi;

use Exception;
use Throwable;

class EwiiApiException extends Exception
{
    public $errors;

    public $runInfo;

    public function __construct($errors, $runInfo, $code = 0, Throwable $previous = null) {
        $this->errors = $errors;
        $this->runInfo = $runInfo;
        $message = $this->errors[0] ?? '';
        if (is_array($message)) {
            $message = $message[0] ?? '';
        }
        parent::__construct($message, $code, $previous);
    }

    public function getErrors()
    {
        return $this->errors;
    }

}