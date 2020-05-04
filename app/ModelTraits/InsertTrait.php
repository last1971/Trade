<?php

namespace App\ModelTraits;

use Illuminate\Database\Eloquent\Builder;

trait InsertTrait
{
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $nextSequenceId = $this->nextSequenceId();
        $query->insert(array_merge($attributes, [$this->getKeyName() => $nextSequenceId]));
        $this->setAttribute($this->getKeyName(), $nextSequenceId);
    }

    protected function nextSequenceId()
    {
        $result = $this->getConnection()
            ->table('RDB$DATABASE')
            ->select($this->getConnection()->raw("COALESCE( GEN_ID( $this->sequenceName, 1 ), 1 ) AS ID"))
            ->get();
        return $result[0]->ID;
    }
}
