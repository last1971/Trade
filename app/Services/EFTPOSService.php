<?php


namespace App\Services;


use Exception;

class EFTPOSService
{
    /**
     * @var UCSService
     */
    private UCSService $ucs;

    /**
     * EFTPOSService constructor.
     */
    public function __construct()
    {
        $this->ucs = new UCSService();
    }

    /**
     * @param int $seconds
     * @return array|string
     * @throws \Throwable
     */
    private function receive(int $seconds)
    {
        $count = 0;
        $answer = '';
        while ($count < $seconds || $answer === '') {
            sleep(1);
            $count++;
            $answer = $this->ucs->receive();
        }
        throw_if($answer === '', new Exception('Не получен ответ за ' . $seconds . ' секнуд'));
        return $answer;
    }
}
