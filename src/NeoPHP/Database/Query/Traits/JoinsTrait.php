<?php

namespace NeoPHP\Database\Query\Traits;

use NeoPHP\Database\Query\Join;
use NeoPHP\Database\Query\RawValue;

trait JoinsTrait {

    private $joins = [];

    public function getJoins () {
        return $this->joins;
    }

    public function setJoins(array $joins) {
        $this->joins = $joins;
    }

    public function clearJoins () {
        $this->joins = [];
        return $this;
    }

    public function addJoin (...$joinArgument) {
        $joinObj = null;
        switch (sizeof($joinArgument)) {
            case 1:
                if (is_a($joinArgument[0], Join::class)) {
                    $joinObj = $joinArgument[0];
                }
                break;
            case 3:
            case 4:
                $tableName = $joinArgument[0];
                $originField = $joinArgument[1];
                $destinationField = $joinArgument[2];
                $joinObj = new Join($tableName);
                if (isset($joinArgument[3])) {
                    $joinObj->setType($joinArgument[3]);
                }
                $joinObj->addCondition($originField, new RawValue($destinationField));
                break;
        }
        if ($joinObj != null) {
            $this->joins[] = $joinObj;
        }
        return $this;
    }
}