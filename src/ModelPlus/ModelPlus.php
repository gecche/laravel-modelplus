<?php namespace Gecche\ModelPlus;

use Gecche\ModelPlus\Concerns\HasOwnerships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * ModelPlus - Eloquent model base class with some pluses!
 *
 */
abstract class ModelPlus extends Model {


    use HasTimestamps;
    use HasOwnerships {
        HasOwnerships::touch insteadof HasTimestamps;
    }


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
     * Array of relations used to verify arguments used in the {@link $relationsData}
     *
     * @var array
     */
    protected static $relationTypes = array(
        self::HAS_ONE, self::HAS_MANY, self::HAS_MANY_THROUGH,
        self::BELONGS_TO, self::BELONGS_TO_MANY,
        self::MORPH_TO, self::MORPH_ONE, self::MORPH_MANY,
        self::MORPH_TO_MANY, self::MORPHED_BY_MANY
    );


    /**
     * Can be used to ease declaration of relationships in Ardent models.
     * Follows closely the behavior of the relation methods used by Eloquent, but packing them into an indexed array
     * with relation constants make the code less cluttered.
     *
     * It should be declared with camel-cased keys as the relation name, and value being a mixed array with the
     * relation constant being the first (0) value, the second (1) being the classname and the next ones (optionals)
     * having named keys indicating the other arguments of the original methods: 'foreignKey' (belongsTo, hasOne,
     * belongsToMany and hasMany); 'table' and 'otherKey' (belongsToMany only); 'name', 'type' and 'id' (specific for
     * morphTo, morphOne and morphMany).
     * Exceptionally, the relation type MORPH_TO does not include a classname, following the method declaration of
     * {@link \Illuminate\Database\Eloquent\Model::morphTo}.
     *
     * Example:
     * <code>
     * class Order extends Ardent {
     *     protected static $relations = array(
     *         'items'    => array(self::HAS_MANY, 'Item'),
     *         'owner'    => array(self::HAS_ONE, 'User', 'foreignKey' => 'user_id'),
     *         'pictures' => array(self::MORPH_MANY, 'Picture', 'name' => 'imageable')
     *     );
     * }
     * </code>
     *
     * @see \Illuminate\Database\Eloquent\Model::hasOne
     * @see \Illuminate\Database\Eloquent\Model::hasMany
     * @see \Illuminate\Database\Eloquent\Model::hasManyThrough
     * @see \Illuminate\Database\Eloquent\Model::belongsTo
     * @see \Illuminate\Database\Eloquent\Model::belongsToMany
     * @see \Illuminate\Database\Eloquent\Model::morphTo
     * @see \Illuminate\Database\Eloquent\Model::morphOne
     * @see \Illuminate\Database\Eloquent\Model::morphMany
     * @see \Illuminate\Database\Eloquent\Model::morphToMany
     * @see \Illuminate\Database\Eloquent\Model::morphedByMany
     *
     * @var array
     */
    protected static $relationsData = array();

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
    public static function getRelationTypes()
    {
        return self::$relationTypes;
    }

    /**
     * @return array
     */
    public static function getRelationsData()
    {
        return self::$relationsData;
    }


}
