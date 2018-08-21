<?php

namespace Gecche\ModelPlus\Concerns;

use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

/**
 * Ardent - Self-validating Eloquent model base class
 *
 */
trait IsPdfExportable
{

    /*
     * Nell'esportazione csv del modello:
     *
     * - se la blacklist e la whitelist sono entrambe null esporto TUTTI i campi
     * - altrimenti se la whitelist è un array esporto SOLO i campi al suo interno
     * - altrimenti se la blacklist è un array esporto TUTTI i campi eccetto quelli in blacklist
     */
    // Campi da scartare nell'esportazione csv del modello

    protected $pdfExportSettings = array(
        'record' => array(
            'configLayout' => 'record',
            'options' => array(
                'lowquality' => false
            ),
            'documentTitle' => 'Record',
        ),
        'list' => array(
            'configLayout' => 'list',
            'blacklist' => [
                'id',
                'password',
                'created_at',
                'updated_at',
            ],
            'fieldWidths' => [
//                'indirizzo' => '20',
            ],
            'defaultFieldWidth' => '10',
            'documentTitle' => 'Lista',
        ),
    );




    /*
     * Metodi per esportazione PDF
     */


    public function getPdfView($type = 'record')
    {

        $pdfTypeParams = array_get($this->pdfExportSettings, $type, []);

        $viewPath = array_get($pdfTypeParams, 'viewPath', 'pdf');

        switch ($type) {
            case 'list':
                $viewName = array_get($pdfTypeParams, 'viewName', 'model_' . $type);
                break;
            default:
                $viewName = array_get($pdfTypeParams, 'viewName', snake_case($this->getRelativeClassName()) . '_' . $type);
                break;
        }

        return $viewPath . '.' . $viewName;
    }

    public function getPdfOptions($type = 'record')
    {

        $pdfTypeParams = array_get($this->pdfExportSettings, $type, []);

        $pdfOptions = array_get($pdfTypeParams, 'options', []);
        $configLayout = array_get($pdfTypeParams, 'configLayout', $type);
        $configPdfOptions = config('snappy.layouts.' . $configLayout . '.options', []);


        $pdfOptions = array_merge($configPdfOptions, $pdfOptions);

        return $pdfOptions;
    }

    public static function getPdfExport($models = null, $modelParams = [], $type = 'record', $params = [])
    {
        if (is_null($models)) {
            $models = static::all();
        }

        $newModel = new static;

        $viewName = $newModel->getPdfView($type);

        $pdfOptions = $newModel->getPdfOptions($type);

        $fields = $newModel->getPdfExportFields($type,$modelParams);
        $fields = array_combine(array_keys($fields), array_keys($fields));

        $methodName = 'buildPdfExport' . studly_case($type);
        if (method_exists($newModel, $methodName)) {
            return $newModel->$methodName($viewName, $models, $modelParams, $pdfOptions, $fields);
        }

        return PDF::loadView($viewName,
            ['data' => $models, 'modelParams' => $modelParams, 'fields' => $fields])->setOptions($pdfOptions)->output();

    }

    public function getPdfExportFields($type, $modelParams) {
        if (is_array($this->pdfExportSettings[$type]['whitelist'])) {
            return $this->pdfExportSettings[$type]['whitelist'];
        }

        $attributes = array_keys($modelParams);

//        Log::info(print_r($attributes,true));
        if (is_array($this->pdfExportSettings[$type]['blacklist'])) {
            return array_diff($attributes, $this->pdfExportSettings[$type]['blacklist']);
        }

        return $attributes;

    }

    public function buildPdfExportList($viewName, $models, $modelParams = [], $pdfOptions = [], $fields = [])
    {

//        $fieldWidths = $this->pdfExportSettings['list']['fieldWidths']; //['indirizzo'=> 20]
//        $defaultFieldWidth = $this->pdfExportSettings['list']['defaultFieldWidth']; //10
//        $fieldStyles = $this->pdfExportSettings['list']['fieldStyles'];
//        $defaultFieldStyle = $this->pdfExportSettings['list']['defaultFieldStyle'];

        $fieldWidths = array_get($this->pdfExportSettings['list'], 'fieldWidths', [] ); //['indirizzo'=> 20]
        $defaultFieldWidth = $this->pdfExportSettings['list']['defaultFieldWidth']; //10
        $fieldStyles = array_get($this->pdfExportSettings['list'], 'fieldStyles', [] );
        $defaultFieldStyle = $this->pdfExportSettings['list']['defaultFieldStyle'];

//        [
//           'denominazione' => [
//                'width' => 10,
//          ],
//      ...
//      ]
        foreach ($fields as $fieldKey => $fieldValue) {
            $fields[$fieldKey] = [
                'width' => array_get($fieldWidths, $fieldKey, $defaultFieldWidth),
                'style' => array_get($fieldStyles,$fieldKey,$defaultFieldStyle)
            ];

        }

        $modelName = camel_case($this->getRelativeClassName());

        return PDF::loadView($viewName, ['fields' => $fields, 'data' => $models, 'model' => ucfirst(trans_choice('model.'.$modelName,2))])
            ->setOptions($pdfOptions)->output();
    }

//
//    public function buildPdfExportEtichette($viewName,$models,$pdfOptions) {
//        return PDF::loadView($viewName,['data' => $models])->setOptions($pdfOptions)->output();
//    }


    public function getPdfExportSettings($type)
    {
        return array_get($this->pdfExportSettings, $type, []);
    }

    /*
     * Fine metodi per esportazione PDF
    */

}
