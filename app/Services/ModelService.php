<?php


namespace App\Services;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class ModelService
{

    /**
     * Attributes like SUM, COUNT e.t.c from related models
     * @var array
     */
    protected $aggregateAttributes = [];

    /**
     * It need for join related tables when use in in wher eand orderBy clauses
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
        $model = $this->query->find(intval($id));
        $model->fill($request->item);
        $model->save();
        return $this->index(collect($request->options))->find(intval($id));
    }

}
