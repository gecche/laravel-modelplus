<?php

namespace Gecche\ModelPlus\DBHelpers;

use Gecche\ModelPlus\ModelPlus;
use \Illuminate\Database\Connection;
use Illuminate\Support\Manager;

class DBHelpersManager extends Manager
{
    /*
         * Metodi per popolare i form automaticamente dal database, funzionano solo con Mysql al momento.
         */

    protected $currentConnectionName = null;
    protected $currentConnection = null;

    protected $connections = [];

    public function getDefaultDriver()
    {
        return $this->connection;
    }

    public function createMysqlDriver()
    {

        return new DBHelpersMysqlDriver($this->currentConnectionName,$this->currentConnection,$this->app->make('cache'));
    }


    /**
     * @param $model string
     */
    public function helperFromName($modelClass, $force = false)
    {

        $model = new $modelClass;

        return $this->helper($model, $force);

    }

    /**
     * @param ModelPlus $model
     * @return mixed
     */
    public function helper(ModelPlus $model, $force = false)
    {

        $connectionName = $model->getConnectionName() ?: config('database.default');
        $tableName = $model->getTable();


        if (!$force && $helper = array_get($this->connections, $connectionName)) {
            return $helper->setTable($tableName);
        }

        $this->currentConnection = $model->getConnection();

        $driverName = $this->currentConnection->getDriverName();

        $driver = $this->driver($driverName);

        $this->connections[$connectionName] = $driver;

        $this->currentConnectionName = $connectionName;

        return $driver->setTable($tableName);

    }




}
