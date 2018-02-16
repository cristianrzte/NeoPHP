<?php

namespace NeoPHP\Database;

use NeoPHP\Database\Query\ConditionGroup;

class Query {

    private $table;
    private $selectFields = [];
    private $whereConditions = null;
    private $havingConditions = null;
    private $orderByFields = [];
    private $groupByFields = [];
    private $joins = [];
    private $limit = null;
    private $offset = null;

    public function __construct() {
    }

    public function addSelectFields(...$fields) {
        foreach ($fields as $field) {
            $this->addSelectField($field);
        }
        return $this;
    }

    public function addSelectField(...$fieldArguments) {
        $field = null;
        switch (sizeof($fieldArguments)) {
            case 1:
                $field = $fieldArguments[0];
                break;
            case 2:
                $field = new \stdClass();
                $field->field = $fieldArguments[0];
                $field->alias = $fieldArguments[1];
                break;
            case 3:
                $field = new \stdClass();
                $field->field = $fieldArguments[0];
                $field->alias = $fieldArguments[1];
                $field->table = $fieldArguments[2];
                break;
        }
        $this->selectFields[] = $field;
        return $this;
    }

    public function getSelectFields(): array {
        return $this->selectFields;
    }

    public function setSelectFields(array $selectFields) {
        $this->selectFields = $selectFields;
        return $this;
    }

    public function setTable($table) {
        $this->table = $table;
        return $this;
    }

    public function getTable() {
        return $this->table;
    }

    public function setWhereConditions(ConditionGroup $whereConditions) {
        $this->whereConditions = $whereConditions;
        return $this;
    }

    public function getWhereConditions(): ConditionGroup {
        if (!isset($this->whereConditions)) {
            $this->whereConditions = new ConditionGroup();
        }
        return $this->whereConditions;
    }

    public function setWhereConnector($connector) {
        $this->getWhereConditions()->setConnector($connector);
        return $this;
    }

    public function getWhereConnector() {
        return $this->getWhereConditions()->getConnector();
    }

    public function addWhere(...$arguments) {
        $this->getWhereConditions()->addCondition($arguments);
        return $this;
    }

    public function setHavingConditions(ConditionGroup $havingConditions) {
        $this->havingConditions = $havingConditions;
        return $this;
    }

    public function getHavingConditions(): ConditionGroup {
        if (!isset($this->havingConditions)) {
            $this->havingConditions = new ConditionGroup();
        }
        return $this->havingConditions;
    }

    public function setHavingConnector($connector) {
        $this->getHavingConditions()->setConnector($connector);
        return $this;
    }

    public function getHavingConnector() {
        return $this->getHavingConditions()->getConnector();
    }

    public function addHaving(...$arguments) {
        $this->getHavingConditions()->addCondition($arguments);
        return $this;
    }

    public function addOrderByFields(...$fields) {
        foreach ($fields as $field) {
            $this->addOrderByField($field);
        }
        return $this;
    }

    public function addOrderByField(...$fieldArguments) {
        $field = null;
        switch (sizeof($fieldArguments)) {
            case 1:
                $field = $fieldArguments[0];
                break;
            case 2:
                $field = new \stdClass();
                $field->field = $fieldArguments[0];
                $field->direction = $fieldArguments[1];
                break;
        }
        $this->orderByFields[] = $field;
        return $this;
    }

    public function getOrderByFields(): array {
        return $this->orderByFields;
    }

    public function setOrderByFields(array $orderByFields) {
        $this->orderByFields = $orderByFields;
        return $this;
    }

    public function addGroupByFields(...$fields) {
        foreach ($fields as $field) {
            $this->addGroupByField($field);
        }
        return $this;
    }

    public function addGroupByField($field) {
        $this->groupByFields[] = $field;
        return $this;
    }

    public function getGroupByFields(): array {
        return $this->groupByFields;
    }

    public function setGroupByFields(array $groupByFields) {
        $this->groupByFields = $groupByFields;
        return $this;
    }
}