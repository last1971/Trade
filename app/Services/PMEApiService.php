<?php


namespace App\Services;


use App\Exceptions\PMEApiException;
use Exception;
use InvalidArgumentException;
use SoapClient;

class PMEApiService
{
    protected $partnerId;
    protected $url;
    protected $certPath;
    protected $soapClient;

    /**
     * Инициализация объекта
     *
     * @param string $partnerId идентификатор клиента API
     * @param string $url [optional] адрес SOAP-службы
     * @param string $certPath [optional] путь к файлу клиентского сертификата для доступа к службе
     */
    public function __construct(
        string $partnerId,
        string $certPath = 'pmelpublic.pem',
        string $url = 'http://web.pm-electronics.ru/api/ApiService.wsdl'
    )
    {
        if (empty($partnerId) || !is_string($partnerId)) {
            throw new InvalidArgumentException('Parameter \'$partnerId\' must be a non-empty string');
        }
        $this->partnerId = $partnerId;

        $this->certPath = $certPath;

        if (empty($url) || !is_string($url)) {
            throw new InvalidArgumentException('Parameter \'$url\' must be a non-empty string');
        }
        $this->url = $url;
    }

    /**
     * Подключение к службе
     */
    protected function connect()
    {
        if (!$this->soapClient) {
            $params = array(
                'encoding'    => 'UTF-8',
                'compression' => (SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP),
                'keep_alive'  => true,
                'exceptions'  => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            );
            if ($this->certPath) {
                $params['local_cert'] = $this->certPath;
                $params['stream_context'] = stream_context_create(array(
                    'ssl' => array(
                        'ciphers'              => 'SHA1',
                        'verify_peer'          => false,
                        'verify_peer_name'     => false,
                        'allow_self_signed'    => true
                    )
                ));
                $params['https'] = array(
                    'curl_verify_ssl_peer' => false,
                    'curl_verify_ssl_host' => false
                );
            }
            try {
                $this->soapClient = new SoapClient($this->url, $params);
            } catch (Exception $ex) {
                throw new PMEApiException($ex, $this->soapClient);
            }
        }
        return $this->soapClient;
    }

    /**
     * Проверка, что служба поиска работает
     *
     * @return bool <code>true</code>, если служба поиска запущена и работает
     */
    public function isAvailable()
    {
        try {
            $response = $this->connect()->__soapCall('isAvailable', array('isAvailableRequest' => array(
                'partnerId' => $this->partnerId
            )));
            return $response && isset($response->available) && $response->available;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Поиск в базе данных по артикулу производителя или артикулу Mouser-а
     *
     * @param string|array $partNumbers один или несколько артикулов для поиска
     * @param bool $strictMatch [optional] искать артикулы, точно соответствующие запрашиваемым
     * @return array ассоциативный массив, ключами которого являются значения, переданные в параметре <i>$partNumbers</i>,
     *               а значениями списки найденных артикулов
     * @throws PMEApiException
     */
    public function findByPartNumber($partNumbers, $strictMatch = true)
    {
        if (is_string($partNumbers)) {
            $partNumbers = array($partNumbers);
        } elseif (!is_array($partNumbers)) {
            throw new InvalidArgumentException('Parameter \'$partNumbers\' must be a string or an array');
        }
        $body = array(
            'partnerId' => $this->partnerId,
            'strictMatch' => $strictMatch ? true : false,
            'partNumbers' => array()
        );
        foreach ($partNumbers as $partNumber) {
            $body['partNumbers'][] = $partNumber;
        }
        try {
            $response = $this->connect()->__soapCall('findByPartNumber', array(
                'findByPartNumberRequest' => $body
            ));
            return $response;
        } catch (PMEApiException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw new PMEApiException($ex, $this->soapClient);
        }
    }
/*
    protected function convertFindByPartNumberResponse($response) {
        $result = array();
        if ($response && isset($response->items)) {
            if(!is_array($response->items)) $response->items = array($response->items);
            foreach ($response->items as $item) {
                if (isset($item->partNumbers))
                    $result[$item->partNumber] = is_array($item->partNumbers) ?
                        $item->partNumbers :
                        (isset($item->partNumbers) ?
                            array($item->partNumbers) :
                            array());
            }
        }
        return $result;
    }
*/
}
