<?php

namespace NeoPHP\Query;

/**
 * Class QueryUtils - Utilidades para los queries
 * @package NeoPHP\Query
 */
abstract class QueryUtils {

    /**
     * @param $table
     * @return SelectQuery
     */
    public static function selectFrom ($table): SelectQuery {
        return new SelectQuery($table);
    }

    /**
     * @param $table
     * @return InsertQuery
     */
    public static function insertInto ($table): InsertQuery {
        return new InsertQuery($table);
    }

    /**
     * @param $table
     * @return UpdateQuery
     */
    public static function update ($table): UpdateQuery {
        return new UpdateQuery($table);
    }

    /**
     * @param $table
     * @return DeleteQuery
     */
    public static function deleteFrom ($table): DeleteQuery {
        return new DeleteQuery($table);
    }

    /**
     * @param string $connector
     * @return ConditionGroup
     */
    public static function conditionGroup ($connector=ConditionGroup::CONNECTOR_AND) {
        return new ConditionGroup($connector);
    }

    /**
     * @param $table
     * @param string $type
     * @return Join
     */
    public static function joinTable ($table, $type=Join::TYPE_INNER_JOIN) {
        return new Join($table, $type);
    }
}