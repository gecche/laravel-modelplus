<?php

namespace Gecche\ModelPlus\Relations;


class HasMany extends \Illuminate\Database\Eloquent\Relations\HasMany
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
