<?php

namespace Gecche\ModelPlus\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class HasOne extends \Illuminate\Database\Eloquent\Relations\HasOne
{
    /**
     * Perform an update on all the related models.
     *
     * @param  array  $attributes
     * @return int
     */
    public function update(array $attributes)
    {
        if ($this->related->usesOwnerships()) {
            $attributes[$this->relatedUpdatedBy()] = Auth::id();
        }

        return parent::update($attributes);
    }
}
