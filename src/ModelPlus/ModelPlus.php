<?php namespace Gecche\ModelPlus;

use Gecche\ModelPlus\Concerns\HasFormHelpers;
use Gecche\ModelPlus\Concerns\HasOwnerships;
use Gecche\ModelPlus\Concerns\HasRelationships as ModelPlusHasRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Gecche\ModelPlus\Concerns\DBHelpers;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;;


/**
 * ModelPlus - Eloquent model base class with some pluses!
 *
 */
abstract class ModelPlus extends Model {


    use Concerns\HasValidation;
    use HasTimestamps;
    use HasRelationships;
    use HasOwnerships {
        HasOwnerships::touch insteadof HasTimestamps;
    }
    use ModelPlusHasRelationships {
        ModelPlusHasRelationships::newHasOne insteadof HasRelationships;
        ModelPlusHasRelationships::newHasMany insteadof HasRelationships;
        ModelPlusHasRelationships::newBelongsToMany insteadof HasRelationships;
        ModelPlusHasRelationships::getMorphClass insteadof HasRelationships;
    }
    use HasFormHelpers;


    /** This class "has one model" if its ID is an FK in that model */
    const HAS_ONE = 'hasOne';

    /** This class "has many models" if its ID is an FK in those models */
    const HAS_MANY = 'hasMany';

    const HAS_MANY_THROUGH = 'hasManyThrough';

    /** This class "belongs to a model" if it has a FK from that model */
    const BELONGS_TO = 'belongsTo';

    const BELONGS_TO_MANY = 'belongsToMany';

    const MORPH_TO = 'morphTo';

    const MORPH_ONE = 'morphOne';

    const MORPH_MANY = 'morphMany';

    const MORPH_TO_MANY = 'morphToMany';

    const MORPHED_BY_MANY = 'morphedByMany';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_BY = 'created_by';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_BY = 'updated_by';

    /**
     * Perform a model update operation (with ownerships).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {

            $this->setKeysForSaveQuery($query)->updateOwnerships($dirty);

            $this->fireModelEvent('updated', false);

            $this->syncChanges();
        }

        return true;
    }




    /**
     * Perform a model insert operation (with ownerships).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->attributes;

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * @return array
     */
    public function getAppends()
    {
        return $this->appends;
    }




}
