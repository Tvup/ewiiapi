<?php

namespace Tvup\EwiiApi;

use ErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ClientException;
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

    private bool $debug = false;
    private string $email;
    private string $password;
    private FileCookieJar $jar;
    private string $md5EwiiCredentials;

    public function __construct($email=null, $password=null)
    {
        $jar = null;

        if (function_exists('storage_path')) {
            $this->storage_path = storage_path() . '/ewii_cookies';

            if(!is_dir($this->storage_path)) {
                mkdir($this->storage_path);
            }
        } else {
            $this->storage_path = getcwd();
        }

        if($email && $password) {
            $this->md5EwiiCredentials = md5($email.$password);
        }

        try {
            $jar = $this->getCookieJarFromFile();
        } catch (ErrorException $e) {
            //NOP
        }

        if(isset($this->md5EwiiCredentials)) {
            $path = $this->storage_path . '/' . ($this->md5EwiiCredentials ? $this->md5EwiiCredentials . '-' : '') . self::COOKIE_FILENAME;
        } else {
            $path = $this->storage_path . '/' . self::COOKIE_FILENAME;
        }

        $this->jar = $jar ?: new FileCookieJar($path, true);

        $this->client = new Client(array(
            'cookies' => $this->jar
        ));
    }

    public function makeErrorHandledRequest(string $verb, ?string $route, string $endpoint, ?array $parameters, ?array $payload, bool $returnResponse = false)
    {
        try {
            try {
                //echo ' **** ENDPOINT : ' . $endpoint . ' *******' . PHP_EOL;
                if ($endpoint == 'Login' && $this->cachedCookie) {
                    //echo 'Saved cookie' . PHP_EOL;
                    $this->email = $payload['Email'];
                    $this->password = $payload['Password'];
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
                    throw $ewiiApiException;
                } else {
                    $ewiiApiException = new EwiiApiException(['Unknown error: ' . $e->getMessage()], [], $e->getCode());
                    throw $ewiiApiException;
                }
            }
        } catch (TransferException $e) {
            $exceptionBody = $e->getResponse()->getBody()->getContents();
            $code = $e->getCode();
            $messages = [
                'Verb' => $verb,
                'Endpoint' => $endpoint,
                'Payload' => $payload,
                'Message' => $e->getMessage(),
                'Response' => $exceptionBody,
                'Code' => $code,
                'Class' => get_class($e)
            ];

            //Retry with without data-access token
            if ($code == 500 && $this->cachedCookie) {
                //Clear data-access token
                $this->cachedCookie = false;
                //Login
                $this->makeErrorHandledRequest('POST', null, 'Login', null, [
                    'Email' => $this->email,
                    'Password' => $this->password,
                    'scAction' => 'EmailLogin',
                    'scController' => 'Auth',
                ]);
                //Retry
                return $this->makeErrorHandledRequest($verb, $route, $endpoint, $parameters, $payload, $returnResponse);
            }

            event(new EwiiRequestFailed($verb, $endpoint, $code));
            $ewiiApiException = new EwiiApiException($messages, [], $code);
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

            $options = [
                'form_params' => $payload
            ];
            if($this->debug) {
                array_merge($options, ['debug' => true,]);
            }
            event(new EwiiRequestMade($verb, $endpoint));
            return $this->client->request($verb, $url, $options);
        } else {
            event(new EwiiRequestMade($verb, $endpoint));
            return $this->client->request($verb, $url, $this->debug ? ['debug' => true] : []);
        }
    }

    /**
     * Decode
     *
     * The intention was that decoding should happen here..
     * NotYetImplemented
     *
     * @param string $getContents
     * @return string
     */
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
        if(isset($this->md5EwiiCredentials)) {
            $file = file_get_contents($this->storage_path . '/' . ($this->md5EwiiCredentials ? $this->md5EwiiCredentials . '-' : '') . self::COOKIE_FILENAME);
        } else {
            $file = file_get_contents($this->storage_path . '/' . self::COOKIE_FILENAME);
        }

        $cookieData = json_decode($file);
        if ($cookieData) {
            if(isset($this->md5EwiiCredentials)) {
                $jar = new FileCookieJar($this->storage_path . '/' . ($this->md5EwiiCredentials ? $this->md5EwiiCredentials . '-' : '') . self::COOKIE_FILENAME, true);
            } else {
                $jar = new FileCookieJar($this->storage_path . '/' . self::COOKIE_FILENAME, true);
            }

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

    protected function clearCookieFile()
    {
        $this->jar->clear();
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }




}