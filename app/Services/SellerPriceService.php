<?php


namespace App\Services;


use App\Services\Pricing\DataBase;
use Error;
use Illuminate\Support\Facades\Cache;

class SellerPriceService
{
    /**
     * @param int $id
     * @return bool
     */
    private function isSeller(int $id): bool
    {
        return !!$this->seller($id);
    }

    /**
     * @param int $id
     * @return mixed
     */
    private function seller(int $id)
    {
        return collect(config('pricing'))->firstWhere('sellerId', $id);
    }

    /**
     * @param string $search
     * @param int $sellerId
     * @param bool $file
     * @param bool $update
     * @return array
     * @throws \Throwable
     */
    public function get(string $search, int $sellerId, bool $file = true, bool $update = false): array
    {
        $processedSearch = trim($search);
        throw_if(!$this->isSeller($sellerId), new Error('Bad Seller!'));
        throw_if(mb_strlen($processedSearch) < 3, new Error('Search string is short!'));
        $key = 'sellerId=' . $sellerId . ';search=' . $processedSearch . ';file=' . $file;
        $result = array();
        if (!$update && Cache::has($key)) {
            $result['data'] = Cache::get($key);
            $result['cache'] = true;
        } else {
            $seller = $this->seller($sellerId);
            $service = $file ? new DataBase() : new $seller['class'];
            if ($file || $seller['ereg']) {
                $processedSearch = mb_ereg_replace(config('app.search_replace'), '', $processedSearch);
            }
            $result['data'] = $service($processedSearch, $sellerId);
            Cache::put($key, $result['data'], $seller['cacheTimes']);
            $result['cache'] = false;
        }
        return $result;
    }
}
