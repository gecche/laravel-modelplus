<?php

namespace Gecche\ModelPlus;

use Gecche\ModelPlus\Console\CompileRelationsCommand;
use Gecche\ModelPlus\DBHelpers\DBHelpersManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class ModelPlusServiceProvider extends ServiceProvider
{

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CompileRelations',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('modelplus.dbhelpers', function ($app) {
            return new DBHelpersManager($app);
        });

        foreach ($this->commands as $command)
        {
            $this->{"register{$command}Command"}();
        }

        $this->commands(
            "command.modelplus.relations"
        );

    }


    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCompileRelationsCommand()
    {
        $this->app->singleton('command.modelplus.relations', function()
        {
            return new CompileRelationsCommand;
        });
    }


    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

//        $this->publishes([
//            __DIR__.'/../config/auth-verification.php' => config_path('auth-verification.php'),
//        ]);

        Builder::macro('addUpdatedByColumn', function (array $values) {

            if (!$this->model->usesOwnerships()) {
                return $values;
            }

            return Arr::add(
                $values, $this->model->getUpdatedByColumn(),
                $this->model->currentUserId()
            );
        });

        Builder::macro('updateOwnerships', function (array $values) {
            return $this->toBase()->update($this->addUpdatedByColumn($this->addUpdatedAtColumn($values)));
        });


    }

}
