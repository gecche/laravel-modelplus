<?php namespace Gecche\ModelPlus;

use Gecche\ModelPlus\Concerns\HasOwnerships;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * ModelPlus - Eloquent model base class with some pluses!
 *
 */
abstract class ModelPlus extends Model {


    use HasOwnerships {
        HasOwnerships::touch insteadof HasTimestamps;
    }


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

}
