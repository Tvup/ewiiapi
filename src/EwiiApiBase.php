<?php

namespace Tvup\EwiiApi;

use ErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;

class EwiiApiBase
{
    const BASE_URL = 'https://selvbetjening.ewii.com/';
    const COOKIE_FILENAME = 'ewii-cookies.json';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $cachedCookie = false;

    /**
     * @var string
     */
    private $storage_path;


    public function __construct()
    {
        $jar = null;

        if (function_exists('storage_path')) {
            $this->storage_path = storage_path();
        } else {
            $this->storage_path = dirname(__DIR__);
        }

        try {
            $jar = $this->getCookieJarFromFile();
        } catch (ErrorException $e) {
            //NOP
        }

        $jar = $jar ?: new FileCookieJar($this->storage_path . '/' . self::COOKIE_FILENAME, true);

        $this->client = new Client(array(
            'cookies' => $jar
        ));
    }

    public function makeErrorHandledRequest(string $verb, ?string $route, string $endpoint, ?array $parameters, ?array $payload, bool $returnResponse = false)
    {
        try {
            try {
                //echo ' **** ENDPOINT : ' . $endpoint . ' *******' . PHP_EOL;
                if ($endpoint == 'Login' && $this->cachedCookie) {
                    //echo 'Saved cookie' . PHP_EOL;
                    return [];
                }

                $response = $this->makeRequest($verb, $route, $endpoint, $parameters, $payload);
                $decodedResponse = $this->decode($response->getBody()->getContents());

                $errorCode = null;
                if (isset($decodedResponse['Message'])) {
                    $errorCode = $decodedResponse['Message'];
                }

                if (isset($errorCode) && $errorCode !== 0) {
                    $messages = [
                        'Verb' => $verb,
                        'Endpoint' => $endpoint,
                        'Payload' => $payload,
                    ];
                    $ewiiApiException = new EwiiApiException(
                        $decodedResponse['ErrorTxt'],
                        $decodedResponse['runInfo'],
                        $errorCode
                    );
                    $messages['Errors'] = $ewiiApiException->getErrors();
                    $messages['ErrorCode'] = $ewiiApiException->getCode();
                    throw $ewiiApiException;
                }
                if ($returnResponse) {
                    return $decodedResponse;
                } else {
                    return [];
                }
            } catch (ClientException $e) {
                $exceptionBody = $e->getResponse()->getBody()->getContents();
                $decodedExceptionBody = json_decode($exceptionBody, true);
                $messages = [
                    'Verb' => $verb,
                    'Endpoint' => $endpoint,
                    'Payload' => $payload,
                    'Body' => $decodedExceptionBody,
                    'Code' => $e->getCode(),
                ];

                $errorCode = null;
                if (isset($decodedExceptionBody['Message'])) {
                    $errorCode = $decodedExceptionBody['Message'];
                }

                if (isset($errorCode) && $errorCode !== 0) {
                    $ewiiApiException = new EwiiApiException(
                        $decodedExceptionBody['ErrorTxt'],
                        isset($decodedExceptionBody['runInfo']) ? $decodedExceptionBody['runInfo'] : null,
                        $errorCode
                    );
                    $messages['Errors'] = $ewiiApiException->getErrors();
                    $messages['ErrorCode'] = $ewiiApiException->getCode();
                    Log::critical(json_encode($messages));
                    throw $ewiiApiException;
                } else {
                    $ewiiApiException = new EwiiApiException(['Unknown error: ' . $e->getMessage()], [], $e->getCode());
                    Log::critical(json_encode($messages));
                    throw $ewiiApiException;
                }
            }
        } catch (TransferException $e) {
            $messages = [
                'Verb' => $verb,
                'Endpoint' => $endpoint,
                'Payload' => $payload,
                'Message' => $e->getMessage(),
                'Code' => $e->getCode(),
                'Class' => get_class($e)
            ];
            $ewiiApiException = new EwiiApiException($messages, [], $e->getCode());
            throw $ewiiApiException;
        }
    }

    private function makeRequest(string $verb, ?string $route, string $endpoint, ?array $parameters, ?array $payload): ResponseInterface
    {
        if (null !== $parameters) {
            $parameters = '?' . http_build_query($parameters);
        } else {
            $parameters = '';
        }
        if ('' == $route) {
            $url = self::BASE_URL . $endpoint . $parameters;
        } else {
            $url = self::BASE_URL . $route . $endpoint . $parameters;
        }
        if (null !== $payload) {
            return $this->client->request($verb, $url, [
                'form_params' => $payload
            ]);
        } else {
            return $this->client->request($verb, $url);
        }
    }

    private function decode(string $getContents)
    {
        return $getContents;
    }

    /**
     * @return FileCookieJar
     * @throws ErrorException
     */
    private function getCookieJarFromFile(): ?FileCookieJar
    {
        $jar = null;
        $file = file_get_contents($this->storage_path . '/' . self::COOKIE_FILENAME);
        $cookieData = json_decode($file);
        if ($cookieData) {
            $jar = new FileCookieJar($this->storage_path . '/' . self::COOKIE_FILENAME, true);
            foreach ($cookieData as $cookie) {
                //If there are multiple cookie data, you could filter according to your case
                $cookie = json_decode(json_encode($cookie), TRUE);
                if (array_key_exists('Name', $cookie) && $cookie['Name'] == 'ASP.NET_SessionId') {
                    $setCookie = new SetCookie($cookie);
                }
            }
            if ($setCookie) {
                $jar->setCookie($setCookie);
                $this->cachedCookie = true;
            }
        }
        return $jar;
    }


}