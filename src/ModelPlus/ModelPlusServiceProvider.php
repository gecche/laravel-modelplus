<?php

namespace Gecche\ModelPlus;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class ModelPlusServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->alias('artisan',\Gecche\Multidomain\Console\Application::class);
//
//
//        foreach ($this->commands as $command)
//        {
//            $this->{"register{$command}Command"}();
//        }
//
//        $this->commands(
//            "command.domain",
//            "command.domain.update_env"
//        );

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
                Auth::id()
            );
        });


    }

}
