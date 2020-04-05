<?php


namespace App\Services;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelService
{
    /**
     * @var array
     */
    protected $aggregateAttributes = [];

    /**
     * @var array
     */
    protected $dateAttributes = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var Model
     */
    private $modelClass;

    /**
     * ModelService constructor.
     * @param Model|string $modelClass
     */
    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @param null $request
     * @return Builder|mixed
     */
    public function index($request)
    {
        $query = $this->modelClass::query();
        // add joins
        foreach (
            array_unique(
                array_merge($request->get('filterAttributes') ?? [], $request->get('sortBy') ?? [])
            ) as $attribute
        ) {
            if (isset($this->aliases[$attribute])) {
                $this->aliases[$attribute]($query);
            }
        }
        return $query
            // relations
            ->when($request->get('with'), function (Builder $query, array $with) {
                $query->with($with);
            })
            // select only attributes
            ->select($request->get('selectAttributes') ?? (new $this->modelClass)->getTable() . '.*')
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
                        // date where
                        } else if (array_search($filterAttribute, $this->dateAttributes) !== false) {
                            $query->whereRaw(
                                '"' . $filterAttribute . '" ' . $request->get('filterOperators')[$index]
                                . '\'' . Carbon::parse($request->get('filterValues')[$index]) . '\''
                            );
                            // in where
                        } else if ($request->get('filterOperators')[$index] === 'IN') {
                            $query->whereRaw(
                                '"' . $filterAttribute . '" IN (' . $request->get('filterValues')[$index] . ')'
                            );
                            // contain where
                        } else if ($request->get('filterOperators')[$index] === 'CONTAIN') {
                            $query->where(
                                $filterAttribute,
                                'LIKE',
                                '%' . $request->get('filterValues')[$index] . '%'
                            );
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

}
