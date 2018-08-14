<?php

namespace Gecche\ModelPlus\Console;

use Gecche\ModelPlus\ModelPlus;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class CompileRelationsCommand extends Command
{

    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelplus:relations
                    {model? : Only compile relations for the specified model}
                    {--dir= : Directory of the models (relative to app}
                    {--force : Overwrite existing relation traits by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a relation trait for each model by using the relational array.';


    protected $dir;
    protected $fullDir;
    protected $namespace;
    protected $models = [];

    protected $relationErrors = [];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $this->getFilesData();

        $this->createDirectories();

        foreach ($this->models as $modelFilename) {
            if (($modelData = $this->checkAndGuessModelFile($modelFilename)) === false) {
                $this->info('File ' . $modelFilename . ' not guessed as a model');
                continue;
            }



            if (!($modelRelations = $this->getModelRelations($modelData))) {
                $this->info('Empty or not suitable relational array in file ' . $modelFilename);
                continue;
            };

            if (!($traitContents = $this->compileTrait($modelRelations))) {
                $this->info('Empty or not suitable relational array in file ' . $modelFilename);
                continue;
            };
        }

//        $this->exportViews();
//
//        if (!$this->option('views')) {
//            file_put_contents(
//                app_path('Http/Controllers/HomeController.php'),
//                $this->compileControllerStub()
//            );
//
//            foreach (['VerificationController', 'LostVerificationController', 'RegisterController'] as $controllerName) {
//                file_put_contents(
//                    app_path('Http/Controllers/Auth/' . $controllerName . '.php'),
//                    $this->compileControllerStub($controllerName)
//                );
//            }
//
//            if (!$this->option('verification-routes')) {
//                file_put_contents(
//                    base_path('routes/web.php'),
//                    file_get_contents(__DIR__ . '/stubs/make/routes.stub'),
//                    FILE_APPEND
//                );
//            }
//
//            file_put_contents(
//                base_path('routes/web.php'),
//                file_get_contents(__DIR__ . '/stubs/make/verification-routes.stub'),
//                FILE_APPEND
//            );
//        }

//        $this->info('Authentication scaffolding generated successfully.');
    }


    protected function getFilesData()
    {

        $this->dir = $this->option('dir') ?:
            (config('modelplus.default-models-dir') ?:
                '');

        $this->fullDir = app_path($this->dir);

        $this->namespace = $this->getAppNamespace() . '\\' . config('modelplus.namespace');

        $modelName = $this->argument('model');

        $this->models = $modelName ? [$this->getModelFilename($modelName)]
            : glob($this->fullDir . '/*.php');


    }


    protected function getModelFilename($modelName)
    {
        return $this->fullDir . '/' . $modelName . '.php';
    }

    protected function checkAndGuessModelFile($modelFilename)
    {


        if (!file_exists($modelFilename)) {
            return false;
        }

        $modelRelativeName = $this->guessModelNameFromFilename($modelFilename);
        $modelContents = file_get_contents($modelFilename);

        if (!str_contains($modelContents, 'use ' . $this->namespace)
            || !str_contains($modelContents, 'class ' . $modelRelativeName . ' extends ModelPlus')
            || ($classContentsStart = strpos($modelContents, '{')) === false
        ) {
            return false;
        }

        return [
            'modelContents' => $modelContents,
            'modelClassName' => $this->namespace . '\\' . $modelRelativeName,
            'modelContentsStartingPoint' => $classContentsStart,
        ];


    }

    protected function guessModelNameFromFilename($filename)
    {

        $rightPart = substr($filename, strrpos($filename, '/') + 1);
        return substr($rightPart, 0, -4);
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {

        if (!is_dir($directory = ($this->fullDir . '/Relations'))) {
            mkdir($directory, 0755, true);
        }
    }


    protected function getModelRelations($modelData) {
        $modelClassName = array_get($modelData,'modelClassName');

        $relationsData = $modelClassName::getRelationsData();

        return is_array($relationsData) ?: false;

    }

    protected function compileTrait($modelRelations) {
        $traitContents = [
            'use' => [],
            'relations' => [],
        ];

        foreach ($modelRelations as $name => $relationData) {
            $relationType = array_get($relationData,0);
            if (!in_array($relationType,ModelPlus::getRelationTypes())) {
                $this->relationErrors[$name] = [
                  'Relation type not allowed',
                ];
                continue;
            }

            $methodName = 'compile'.ucfirst($relationType);

            $relationContent = $this->$methodName($name,$relationData);
            if ($relationContent) {
                $traitContents['use'][$relationType] = $relationType;
                $traitContents['relations'][$name] = $relationContent;
            }



        }

    }


    protected function compileHasOne($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileHasMany($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileHasManyThrough($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileBelongsTo($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileBelongsToMany($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileMorphTo($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileMorphOne($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileMorphMany($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileMorphToMany($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    protected function compileMorphedByMany($name,$relationData) {
        $relationContents = '';
        return $relationContents;
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            if (file_exists($view = resource_path('views/' . $value)) && !$this->option('force')) {
                if (!$this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(
                __DIR__ . '/stubs/make/views/' . $key,
                $view
            );
        }
    }

    /**
     * Compiles the HomeController stub.
     *
     * @return string
     */
    protected function compileControllerStub($controllerFile = 'HomeController')
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__ . '/stubs/make/controllers/' . $controllerFile . '.stub')
        );
    }
}
