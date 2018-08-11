<?php

namespace Gecche\ModelPlus\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasOwnerships
{
    /**
     * Indicates if the model should be ownershipped.
     *
     * @var bool
     */
    public $ownerships = false;

    /**
     * Update the model's update timestamp.
     *
     * @return bool
     */
    public function touch()
    {
        if (! $this->usesTimestamps() && ! $this->usesOwnerships()) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateOwnerships()
    {

        $userId = Auth::id();

        if (! is_null(static::UPDATED_BY) && ! $this->isDirty(static::UPDATED_BY)) {
            $this->setUpdatedBy($userId);
        }

        if (! $this->exists && ! $this->isDirty(static::CREATED_BY)) {
            $this->setCreatedBy($userId);
        }
    }

    /**
     * Set the value of the "created by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setCreatedBy($value)
    {
        $this->{static::CREATED_BY} = $value;
    }

    /**
     * Set the value of the "updated by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setUpdatedBy($value)
    {
        $this->{static::UPDATED_BY} = $value;
    }


    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesOwnerships()
    {
        return $this->ownerships;
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function getCreatedByColumn()
    {
        return static::CREATED_BY;
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function getUpdatedByColumn()
    {
        return static::UPDATED_BY;
    }
}
