<?php

namespace Gecche\ModelPlus\Concerns;

use Gecche\ModelPlus\Facades\ModelPlusDBHelpers;

trait DBHelpers
{
    protected $dbAttributes = null;


    public function getDBAttributes() {
        return is_null($this->dbAttributes)
            ? array_keys($this->setAttributesFromDB())
            : array_keys($this->dbAttributes);
    }

    protected function setAttributesFromDB() {
        $this->dbAttributes = ModelPlusDBHelpers::helper($this)
            ->listColumnsDefault(null,true);

        return $this->dbAttributes;
    }

    public function getDBDefaults($key = null) {
        if (is_null($this->dbAttributes))
            $this->setAttributesFromDB();

        return $key
            ? array_get($this->dbAttributes,$key)
            : $this->dbAttributes;
    }




}
