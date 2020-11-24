<?php


namespace App\Services;


use Exception;
use Illuminate\Support\Str;
use Throwable;

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


    public function __destruct()
    {
        if ($this->ucs) $this->ucs->close();
    }

    /**
     * @return array
     * @throws Throwable]
     */
    public function login(): void
    {
        $this->ucs->send($this->ucs::LOGIN);
        $this->ucs->receive();
    }

    /**
     * @param float $amount
     * @return array
     * @throws Throwable
     */
    public function sale(float $amount): array
    {
        $data = Str::padLeft(intval($amount * 100), 12, '0');
        $this->ucs->send($this->ucs::SALE, $data);
        return $this->getAnswer();
    }

    /**
     * @param string $urn
     * @param float $amount
     * @param float $newAmount
     * @throws Throwable
     */
    public function reversalOfSale(string $urn, float $amount, float $newAmount = 0): void
    {
        $data = $urn
            . Str::padLeft(intval($amount * 100), 12, '0')
            . Str::padLeft(intval($newAmount * 100), 12, '0');
        $this->ucs->send($this->ucs::SALE, $data);
        $this->getAnswer();
    }

    /**
     * @return array
     * @throws Throwable
     */
    private function getAnswer()
    {
        $res = ['code' => '00'];
        while ($res['code'] !== '60' && $res['code'] !== '5X') {
            $res = $this->ucs->receive();
            var_dump($res);
        }
        throw_if(
            $res['code'] === '5X',
            new Exception(
                'Ошибка ' . Str::substr($res['data'] ,0 ,2) . ' - '
                . iconv("cp1251", "utf-8", Str::substr($res['data'] ,2))
            )
        );
        throw_if(
            Str::substr($res['data'] ,57, 2) !== '00',
            new Exception(
                'Ошибка. Ответ - ' . iconv("cp1251", "utf-8", Str::substr($res['data'] ,59))
            )
        );
        return [
            'urn' => Str::substr($res['data'] ,45,12),
            'l4d' => Str::substr($res['data'] ,75,4)
        ];
    }
}
