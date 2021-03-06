<?php


namespace App\Services;


use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

//use Illuminate\Database\Eloquent\Collection;

class ModelService
{

    /**
     * Attributes like SUM, COUNT e.t.c from related models
     * @var array
     */
    protected $aggregateAttributes = [];

    /**
     * It need for join related tables when use in in where and orderBy clauses
     * @var array
     */
    protected $aliases = [];

    /**
     * Additional select atribbutes like some RAW attributes
     * @var array
     */
    protected $addSelect = [];

    /**
     * Firebird2.0 has ploblem with date attributes
     * @var array
     */
    protected $dateAttributes = [];

    /**
     * Firebird2.0 has ploblem with float attributes
     * @var array
     */
    protected $floatAttributes = [];

    /**
     * Additional RAW where
     * @var array
     */
    protected $whereAttributes = [];

    /**
     * @var Model
     */
    private $modelClass;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * ModelService constructor.
     * @param Model|string $modelClass
     */
    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
        $this->query = $this->modelClass::query();
    }

    public function setRawFrom(string $from, array $bindings)
    {
        $this->query->fromRaw($from, $bindings);
    }

    /**
     * @param string $attribute
     * @return string
     */
    private function rawAttribute(string $attribute)
    {
        $parts = explode('.', $attribute);
        return Str::replaceFirst(
            '.',
            '',
            array_reduce($parts, function ($s, $v) {
                $s .= '.' . '"' . $v . '"';
                return $s;
            }, '')
        );
    }

    private function getCahngedDates(array $item, Model $model)
    {
        return array_filter($item, function ($val, $key) use ($model) {
            return array_search($key, $this->dateAttributes) !== FALSE
                && Carbon::create($val) != Carbon::create($model->getAttribute($key));
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function setCahngedDates(array $item, Model $model)
    {
        $dateDiff = $this->getCahngedDates($item, $model);
        if (count($dateDiff) > 0) {
            $table = $model->getTable();
            $keyName = $model->getKeyName();
            $sql = 'UPDATE ' . $table . ' SET ';
            foreach ($dateDiff as $key => $val) {
                $sql .= $key . ' = \'' . $val . '\' ,';
            }
            $sql = Str::replaceLast(',', '', $sql) . 'WHERE ' . $keyName . ' = ' . $model->getKey();
            DB::connection('firebird')->update($sql);
        }
    }

    private function getFloatDiff(array $item, Model $model)
    {
        $ret = [];
        foreach ($item as $key => $val) {
            if (array_search($key, $this->floatAttributes) !== FALSE && $val !== $model->getAttribute($key))
                $ret[$key] = $val;
        }
        return $ret;
    }

    private function setChangedFloats(array $item, Model $model)
    {
        $floatDiff = $this->getFloatDiff($item, $model);
        if (count($floatDiff) > 0) {
            $table = $model->getTable();
            $keyName = $model->getKeyName();
            $sql = 'UPDATE ' . $table . ' SET ';
            foreach ($floatDiff as $key => $val) {
                $sql .= $key . ' = ' . str_replace(',', '.', $val) . ' ,';
            }
            $sql = Str::replaceLast(',', '', $sql) . 'WHERE ' . $keyName . ' = ' . $model->getKey();
            DB::connection('firebird')->update($sql);
        }
    }

    /**
     * @param FormRequest|Request|Collection $request
     * @return Builder|mixed
     */
    public function index($request)
    {
        // add joins
        foreach (
            array_unique(
                array_merge($request->get('filterAttributes') ?? [], $request->get('sortBy') ?? [])
            ) as $attribute
        ) {
            if (isset($this->aliases[$attribute])) {
                $this->aliases[$attribute]($this->query);
            }
        }

        return $this->query
            // relations
            ->when($request->get('with'), function (Builder $query, array $with) {
                $query->with($with);
            })

            // select only attributes
            ->select($request->get('selectAttributes') ?? (new $this->modelClass)->getTable() . '.*')
            ->addSelect($this->addSelect)

            // additional agregate attributes
            ->aggregateAttributes($request->get('aggregateAttributes'), $this->aggregateAttributes)

            // filter
            ->when(
                $request->get('filterAttributes'),
                function (Builder $query, array $filterAttributes) use ($request) {
                    foreach ($filterAttributes as $index => $filterAttribute) {
                        $data = array_key_exists($filterAttribute, $this->aggregateAttributes)
                            ? $this->aggregateAttributes[$filterAttribute] : false;
                        // aggregate where
                        if ($data) {
                            $query->whereHas(
                                key($data),
                                current($data),
                                $request->get('filterOperators')[$index],
                                $request->get('filterValues')[$index]
                            );
                            // date between
                        } else if ($request->get('filterOperators')[$index] === 'BETWEENDATE') {
                            $between = $request->get('filterValues')[$index];
                            if (!is_array($between)) {
                                $between = json_decode($between);
                            }
                            $query->whereRaw(
                                $this->rawAttribute($filterAttribute) . ' BETWEEN '
                                . '\'' . Carbon::parse($between[0]) . '\' AND '
                                . '\'' . Carbon::parse($between[1]) . '\''
                            );
                            // date where
                        } else if (array_search($filterAttribute, $this->dateAttributes) !== false) {
                            $query->whereRaw(
                                $this->rawAttribute($filterAttribute) . ' '
                                . $request->get('filterOperators')[$index]
                                . '\'' . Carbon::parse($request->get('filterValues')[$index]) . '\''
                            );
                            // in where
                        } else if ($request->get('filterOperators')[$index] === 'IN') {
                            $query->whereIn($filterAttribute, $request->get('filterValues')[$index]);
                            // containing where
                        } else if ($request->get('filterOperators')[$index] === 'CONTAIN') {
                            $query->where(
                                $filterAttribute,
                                'CONTAINING',
                                $request->get('filterValues')[$index]
                            );
                            // whereRaw
                        } else if (array_key_exists($filterAttribute, $this->whereAttributes)) {
                            $query->whereRaw($this->whereAttributes[$filterAttribute]);
                            // where
                        } else {
                            $query->where(
                                $filterAttribute,
                                $request->get('filterOperators')[$index],
                                $request->get('filterValues')[$index]
                            );
                        }
                    }
                }
            )

            // ordering
            ->when($request->get('sortBy'), function (Builder $query, array $sortBy) use ($request) {
                foreach ($sortBy as $index => $orderBy) {
                    $query->orderBy($orderBy, $request->get('sortDesc')[$index] ? 'desc' : 'asc');
                }
            });
    }

    /**
     * @param $request
     * @param $id
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function update($request, $id)
    {
        $model = $this->modelClass::query()->find(intval($id));
        $model->fill($request->item);
        $model->save();
        $this->setCahngedDates($request->item, $model);
        $this->setChangedFloats($request->item, $model);
        return $this->index(collect($request->options))->find(intval($id));
    }

    /**
     * @param $request
     * @return mixed
     */
    public function create($request)
    {
        $model = new $this->modelClass;
        $data = is_array($request) ? $request : $request->item;
        $model->fill($data);
        $model->save();
        $this->setCahngedDates($data, $model);
        $this->setChangedFloats($data, $model);
        return $this->index(collect(is_array($request) ? [] : $request->options))->find($model->getKey());
    }

    /**
     * @param $id
     * @return bool|mixed|null
     * @throws Exception
     */
    public function remove($id)
    {
        return $this->modelClass::query()->find(intval($id))->delete();
    }

    protected function addUserBuyers($request, $table = null)
    {
        $table = $table ?? $this->query->getModel()->getTable();
        if (auth()->user() && !auth()->user()->userBuyers->isEmpty()) {
            $restrictions = auth()->user()->userBuyers->map(function ($v) {
                return $v->buyer_id;
            })->all();
            $this->addUserRestriction($request, $table . '.POKUPATCODE', $restrictions);
        }
    }

    protected function addUserFirms($request, $table = null)
    {
        $table = $table ?? $this->query->getModel()->getTable();
        if (auth()->user() && !auth()->user()->userFirms->isEmpty()) {
            $restrictions = auth()->user()->userFirms->map(function ($v) {
                return $v->firm_id;
            })->all();
            $this->addUserRestriction($request, $table . '.FIRM_ID', $restrictions);
        }
    }

    private function addUserRestriction($request, $attribute, $restrictions)
    {
        $filterAttributes = $request->get('filterAttributes') ?? [];
        $filterValues = $request->get('filterValues') ?? [];
        $filterOperators = $request->get('filterOperators') ?? [];
        if (($index = array_search($attribute, $filterAttributes)) !== FALSE) {
            $filterValues[$index] = array_filter($filterValues[$index], function ($v) use ($restrictions) {
                return in_array($v, $restrictions);
            });
        } else {
            $filterValues[] = $restrictions;// implode(',', $restrictions);
            $filterOperators[] = 'IN';
            $filterAttributes[] = $attribute;
        }
        $request->merge(compact('filterValues', 'filterOperators', 'filterAttributes'));
    }
}
