<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Str;

class UCSService
{
    const SALE='10';
    const LOGIN='30';

    private $resource;
    private string $terminal;

    /**
     * UCSService constructor.
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->terminal = env('UCS_TERMINAL');
        throw_if(empty($this->terminal), new Exception('Не указан номер терминала'));
        $this->resource = stream_socket_client(
            env('UCS_IP'), $errno, $errstr, 30
        );
        fclose($this->resource);
        throw_if(!$this->resource, new Exception("$errstr ($errno)"));
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
        $length = $this->toHex(strlen($data));
        fwrite($this->resource, $command . $this->terminal . $length . $data);
    }

    private function getBytes(int $bytes): string
    {
        throw_if(feof($this->resource), new Exception('Соединение закрыто!'));
        $res = '';
        for ($i = 0;$i < $bytes; $i++) {
            $res .= fgets($this->resource, 2);
        }
        return $res;
    }

    public function get()
    {
        throw_if(feof($this->resource), new Exception('Соединение закрыто!'));
        $command = $this->getBytes(2);
        $termianl = $this->getBytes(10);
        $length = hexdec($this->getBytes(2));
        throw_if($termianl !== $this->terminal, new Exception('Ошибка в номере терминала'));
        $data = $length ? fgets($this->resource, $length) : '';
        return [
            $command, $data
        ];
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return feof($this->resource);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        while(!feof($this->resource)){
            fgets($this->resource, 2);
        }
        return fclose($this->resource);
    }

}
