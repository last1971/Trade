<?php

namespace App\Exceptions;

use Exception;
use SoapClient;

class PMEApiException extends Exception
{
    private $lastRequestHeaders;
    private $lastRequest;
    private $lastResponseHeaders;
    private $lastResponse;

    /**
     * @param Exception $ex исходная ошибка
     * @param SoapClient $soapClient [optional] объект SoapClient
     */
    public function __construct(Exception $ex, SoapClient $soapClient = null)
    {
        parent::__construct($ex->getMessage(), $ex->getCode(), $ex);
        if ($soapClient) {
            $this->lastRequestHeaders = $soapClient->__getLastRequestHeaders();
            $this->lastRequest = $soapClient->__getLastRequest();
            $this->lastResponseHeaders = $soapClient->__getLastResponseHeaders();
            $this->lastResponse = $soapClient->__getLastResponse();
        }
    }

    /**
     * @return string HTTP-заголовки отправленного запроса
     */
    public function getLastRequestHeaders()
    {
        return $this->lastRequestHeaders;
    }

    /**
     * @return string тело отправленного HTTP-запроса
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return string HTTP-заголовки полученного ответа
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @return string тело полученного HTTP-ответа
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
