<?php


namespace App\Services;


use App\Http\Requests\SellerPriceRequest;
use App\Http\Resources\SellerPriceResource;
use App\SellerPriceRule;
use App\Services\Pricing\DataBase;
use Error;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SellerPriceService
{
    /**
     * @var string
     */
    private string $search;

    /**
     * @var int
     */
    private int $sellerId;

    /**
     * @var bool
     */
    private bool $update = false;

    /**
     * @var string
     */
    private string $sellerKey;

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @var bool
     */
    private bool $cache = false;

    /**
     * @var bool
     */
    private bool $isApiError = false;

    /**
     * @var string
     */
    private string $searchKey;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private array $goodIds = [];

    /**
     * @var array
     */
    private array $seller;

    /**
     * @param string $search
     * @param int $sellerId
     * @param bool $update
     * @return SellerPriceService
     */
    private function initialize(string $search, int $sellerId, bool $update = false): SellerPriceService
    {
        $this->search = trim($search);
        $this->sellerId = $sellerId;
        $this->update = $update;
        $this->seller = $this->seller($this->sellerId);
        return $this;
    }

    /**
     * @return SellerPriceService
     * @throws Throwable
     */
    private function checkParameters(): SellerPriceService
    {
        throw_if(!$this->isSeller($this->sellerId), new Error('Bad Seller!'));
        throw_if(mb_strlen($this->search) < 3, new Error('Search string is short!'));
        return $this;
    }

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

    private function checkSearchCache(): SellerPriceService
    {
        $rule = SellerPriceRule::userSellerPriceRule();
        $this->sellerKey = 'sellerId=' . $this->sellerId . ';search=' . $this->search;
        $this->searchKey = 'rule=' . $rule->alias . $this->sellerKey . ';';
        if (!$this->update && Cache::has($this->searchKey)){
            $this->data = Cache::get($this->searchKey);
            $this->cache = true;
        }
        return $this;
    }

    private function checkSellerCache(): SellerPriceService
    {
        if (!$this->cache && !$this->update && Cache::has($this->sellerKey)) {
            $this->collection = Cache::get($this->sellerKey);
            $this->cache = true;
        }
        return $this;
    }

    private function sellerQuery(): SellerPriceService
    {
        if ($this->cache) return $this;
        $service = new $this->seller['class'];
        $processedSearch = $this->seller['ereg']
            ? mb_ereg_replace(config('app.search_replace'), '', $this->search)
            : $this->search;
        try {
            $this->collection = $service($processedSearch);
            $this->goodIds = $this->collection
                ->map(fn($sellerPrice) => $sellerPrice->sellerWarehouse->sellerGood->id)
                ->unique()
                ->toArray();
            Cache::tags($this->goodIds)->put($this->sellerKey, $this->collection, $this->seller['cacheTimes']);
        } catch (Exception $e) {
            $service = new DataBase();
            $processedSearch = mb_ereg_replace(config('app.search_replace'), '', $processedSearch);
            $this->collection = $service($processedSearch);
            $this->isApiError = true;
            Log::error($e->getMessage());
        }
        return $this;
    }

    /**
     * @return array
     */
    private function response(): array
    {
        if (!$this->data) {
            $this->data = SellerPriceResource::collection($this->collection);
            Cache::put($this->searchKey, $this->data, $this->seller['cacheTimes']);
        }
        return [
            'data' => $this->data,
            'cache' => $this->cache,
            'isApiError' => $this->isApiError,
        ];
    }

    /**
     * @param SellerPriceRequest $request
     * @return SellerPriceService
     * @throws Throwable
     */
    public function searchFromRequest(SellerPriceRequest $request): array
    {
        return $this
            ->initialize($request->search, $request->sellerId, $request->isUpdate)
            ->checkParameters()
            ->checkSearchCache()
            ->checkSellerCache()
            ->sellerQuery()
            ->response();
    }

}
