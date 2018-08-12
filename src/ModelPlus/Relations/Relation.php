<?php

namespace Gecche\ModelPlus\Relations;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
abstract class Relation extends \Illuminate\Database\Eloquent\Relations\Relation
{


    /**
     * Touch all of the related models for the relationship.
     *
     * @return void
     */
    public function touch()
    {
        $column = $this->getRelated()->getUpdatedAtColumn();
        $ownershipsColumn = $this->getRelated()->getUpdatedByColumn();

        $this->rawUpdate([
            $column => $this->getRelated()->freshTimestampString(),
            $ownershipsColumn => Auth::id(),
        ]);
    }


    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function createdBy()
    {
        return $this->parent->getCreatedByColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function updatedBy()
    {
        return $this->parent->getUpdatedByColumn();
    }

    /**
     * Get the name of the related model's "updated at" column.
     *
     * @return string
     */
    public function relatedUpdatedBy()
    {
        return $this->related->getUpdatedByColumn();
    }


}
