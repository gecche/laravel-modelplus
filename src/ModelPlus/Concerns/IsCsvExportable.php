<?php

namespace Gecche\ModelPlus\Concerns;

use Illuminate\Support\Facades\Lang;

/*
 * This file is part of the Ardent package.
 *
 * (c) Max Ehsan <contact@laravelbook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Ardent - Self-validating Eloquent model base class
 *
 */
trait IsCsvExportable
{

    /*
     * Nell'esportazione csv del modello:
     *
     * - se la blacklist e la whitelist sono entrambe null esporto TUTTI i campi
     * - altrimenti se la whitelist è un array esporto SOLO i campi al suo interno
     * - altrimenti se la blacklist è un array esporto TUTTI i campi eccetto quelli in blacklist
     */
    // Campi da scartare nell'esportazione csv del modello

    protected $csvExportSettings = array(
        'default' => array(
            'blacklist' => array(
                'id',
                'password',
            ),
            'whitelist' => null,
            'separator' => ';',
            'endline' => "\n",
            'headers' => 'translate',
            'decimalFrom' => '.',
            'decimalTo' => false,
        )
    );



    /*
     * Metodi per esportazione CSV
     */

    public function getCsvFieldStandard($type,$key) {
        if ($this->csvExportSettings[$type]['decimalTo'] && is_numeric($this->$key)) {
            return str_replace($this->csvExportSettings[$type]['decimalFrom'],$this->csvExportSettings[$type]['decimalTo'],$this->$key);
        }
        return $this->$key;
    }

    public function getCsvRowExport($type = 'default', $modelParams = [])
    {

        $separator = $this->csvExportSettings[$type]['separator'];
        $fieldsToExport = $this->getCsvExportFields($type);

        $row = '';
        foreach ($fieldsToExport as $key) {
            $exportMethod = 'getCsvExport' . studly_case($key);
            if (method_exists($this, $exportMethod)) {
                $field = $this->$exportMethod($type, $modelParams);
            } else {
                $field = $this->getCsvFieldStandard($type,$key);
            }

            $row .= $field . $separator;
        }
        $row = rtrim($row, $separator);
        $row .= $this->csvExportSettings[$type]['endline'];

        return $row;
    }

    public function getCsvRowHeadersExport($type = 'default', $modelParams = [])
    {
        $separator = $this->csvExportSettings[$type]['separator'];
        $fieldsToExport = $this->getCsvExportFields($type, $modelParams);

        $headersType = array_get($this->csvExportSettings[$type], 'headers', 'translate');
        $row = '';
        $modelName = camel_case($this->getRelativeClassName());
//        Log::info($headersType . ' --- ' . $modelName);

        switch ($headersType) {
            case 'translate':
                foreach ($fieldsToExport as $key) {
                    $methodName = 'getCsvHeader'.studly_case($key);
                    if (method_exists($this,$methodName)) {
                        $row .= $this->$methodName($type) . $separator;
                    } else {
                        $row .= Lang::getMFormField($key, $modelName) . $separator;
                    }
//                    Log::info($key . ' --- ' . $modelName);
//                    Log::info(Lang::getMFormField($key,$modelName));
                }
                break;
            default:
                foreach ($fieldsToExport as $key) {
                    $row .= $key . $separator;
                }
                break;
        }

        $row = rtrim($row, $separator);
        $row .= $this->csvExportSettings[$type]['endline'];

        return $row;
    }

    public function getCsvExportFields($type = 'default', $modelParams = [])
    {

        if (is_array($this->csvExportSettings[$type]['whitelist'])) {
            return $this->csvExportSettings[$type]['whitelist'];
        }

        $attributes = array_keys($this->getColumnsFromDB());

//        Log::info(print_r($attributes,true));
        if (is_array($this->csvExportSettings[$type]['blacklist'])) {
            return array_diff($attributes, $this->csvExportSettings[$type]['blacklist']);
        }

        return $attributes;
    }

    public function setCsvSeparator($separator, $type = 'default')
    {
        $this->csvExportSettings[$type]['separator'] = $separator;
    }

    public function setCsvEndOfLine($endOfLine, $type = 'default')
    {
        $this->csvExportSettings[$type]['endline'] = $endOfLine;
    }


    public static function getCsvExport($models = null, $modelParams = [], $type = 'default', $params = array())
    {
        if (is_null($models)) {
            $models = static::all();
        }

        $csvStream = '';

        $newModel = new static;

        $writeHeader = true;
        if (
            !array_get($newModel->csvExportSettings[$type], 'headers', true) ||
            !array_get($params, 'headers', true)
        ) {
            $writeHeader = false;
        }
        if ($writeHeader) {
            $csvStream .= $newModel->getCsvRowHeadersExport($type, $modelParams);
        }

        foreach ($models as $model) {
            $csvStream .= $model->getCsvRowExport($type, $modelParams);
        }

        return $csvStream;
    }

    /*
     * Fine metodi per esportazione CSV
    */

}
