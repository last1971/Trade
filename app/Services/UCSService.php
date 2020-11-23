<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Str;

class UCSService
{
    const SALE='10';
    const LOGIN='30';

    /**
     * @var array
     */
    private $types = [
        '31' => 'Login Response',
        '50' => 'Initial response',
        '51' => 'Initial response – Requires login first',
        '52' => 'PIN Entry required',
        '53' => 'On-line authorisation required',
        '54' => 'Initial response — No previous transaction with such ref. number',
        '55' => 'Hold',
        '5M' => 'Console message',
        '5X' => 'Initial response – Error parsing previous request',
        '60' => 'Authorisation Response'
    ];

    /**
     * @var false|resource
     */
    private $resource;

    /**
     * @var mixed|string
     */
    private string $terminal;

    /**
     * UCSService constructor.
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->terminal = env('UCS_TERMINAL');
        throw_if(empty($this->terminal), new Exception('Не указан номер терминала'));
        $this->resource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        throw_if(
            $this->resource === false,
            new Exception(
                "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error())
            )
        );
        $result = socket_connect($this->resource, env('UCS_IP'), env('UCS_PORT'));
        throw_if(
            $result === false,
            new Exception(
                "Не удалось выполнить socket_connect(). Причина: " . socket_strerror(socket_last_error())
            )
        );
    }

    /**
     * @param int $v
     * @return string
     */
    private function toHex(int $v): string
    {
        return Str::padLeft(Str::upper(dechex($v)), 2, '0');
    }

    /**
     * @param string $command
     * @param string $data
     */
    public function send(string $command, string $data = ''): void
    {
        $buffer = $command . $this->terminal . $this->toHex(strlen($data)) . $data;
        $length = strlen($buffer);
        socket_write($this->resource, $buffer, $length);
    }

    /**
     * @param int $length
     * @return string
     */
    private function getBytes(int $length): string
    {
        return socket_read($this->resource, $length);
    }

    /**
     * @return array
     * @throws \Throwable
     */
    public function receive()
    {
        $code = $this->getBytes(2);
        $terminal = $this->getBytes(10);
        $length = hexdec($this->getBytes(2));
        throw_if($terminal !== $this->terminal, new Exception('Ошибка в номере терминала'));
        $data = $length ? $this->getBytes($length) : '';
        return [
            'code' => $code, 'type' => $this->types[$code], 'data' => $data
        ];
    }

    /**
     *
     */
    public function close(): void
    {
        socket_close($this->resource);
    }

}
