<?php


namespace App\Services;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelService
{
    protected $agregateAttributes = [];

    protected $dateAttributes = [];

    /**
     * @var Model
     */
    private $modelClass;

    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function index($request = null)
    {
        return $this->modelClass::query()
            // relations
            ->when($request->get('with'), function (Builder $query, array $with) {
                $query->with($with);
            })
            // select only attributes
            ->when($request->get('selectAttributes'), function (Builder $query, array $selectAttributes) {
                $query->select($selectAttributes);
            })
            // additional agregate attributes
            ->when($request->get('agregateAttributes'), function (Builder $query, array $agregateAttributes) {
                $withCount = [];
                foreach ($agregateAttributes as $agregateAttribute) {
                    $data = $this->agregateAttributes[$agregateAttribute];
                    $withCount[key($data) . ' as ' . $agregateAttribute] = current($data);
                }
                $query->withCount($withCount);
            })
            // filter
            ->when(
                $request->get('filterAttributes'),
                function (Builder $query, array $filterAttributes) use ($request) {
                    foreach ($filterAttributes as $index => $filterAttribute) {
                        $data = array_key_exists($filterAttribute, $this->agregateAttributes)
                            ? $this->agregateAttributes[$filterAttribute] : false;
                        if ($data) {
                            $query->whereHas(
                                key($data),
                                current($data),
                                $request->get('filterOperators')[$index],
                                $request->get('filterValues')[$index]
                            );
                        } else if (array_search($filterAttribute, $this->dateAttributes) !== false) {
                            $query->whereRaw(
                                '"' . $filterAttribute . '" ' . $request->get('filterOperators')[$index]
                                . '\'' . $request->get('filterValues')[$index] . '\''
                            );
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
                    $query->orderBy($orderBy, $request->get('sortDesc')[$index]);
                }
            });
    }
}
