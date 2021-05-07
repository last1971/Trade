<?php


namespace App\Services;


use App\Http\Resources\SellerPriceResource;
use App\Services\Pricing\DataBase;
use Error;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
    public function get(string $search, int $sellerId, bool $update = false): array
    {
        $processedSearch = trim($search);
        throw_if(!$this->isSeller($sellerId), new Error('Bad Seller!'));
        throw_if(mb_strlen($processedSearch) < 3, new Error('Search string is short!'));
        $key = 'sellerId=' . $sellerId . ';search=' . $processedSearch;
        $result = array();
        $result['isApiError'] = false;
        if (!$update && Cache::has($key)) {
            $result['data'] = SellerPriceResource::collection(Cache::get($key));
            $result['cache'] = true;
        } else {
            $seller = $this->seller($sellerId);
            $service = new $seller['class'];
            if ($seller['ereg']) {
                $processedSearch = mb_ereg_replace(config('app.search_replace'), '', $processedSearch);
            }
            try {
                $collection = $service($processedSearch);
                $result['data'] = SellerPriceResource::collection($collection);
                $goodIds = $collection
                    ->map(function($sellerPrice) {
                        return $sellerPrice->sellerWarehouse->sellerGood->id;
                    })
                    ->unique()
                    ->toArray();
                Cache::tags($goodIds)->put($key, $collection, $seller['cacheTimes']);
            } catch (Exception $e) {
                $service = new DataBase();
                $processedSearch = mb_ereg_replace(config('app.search_replace'), '', $processedSearch);
                $result['data'] = SellerPriceResource::collection($service($processedSearch));
                $result['isApiError'] = true;
                Log::error($e->getMessage());
            }
            $result['cache'] = false;
        }
        return $result;
    }
}
