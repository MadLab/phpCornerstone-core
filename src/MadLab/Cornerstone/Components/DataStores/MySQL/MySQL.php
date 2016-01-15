<?php

namespace MadLab\Cornerstone\Components\DataStores\MySQL;
use \PDO;
use \Exception;

class MySQL
{

    /**
     * @var PDO
     */
    private static $connection;

    /**
     * MySQL constructor.
     * @param PDO $pdoConnection
     */
    private function __construct(PDO $pdoConnection)
    {
        static::$connection = $pdoConnection;
    }

    public static function getConnection(PDO $pdoConnection)
    {
        /*
        * Returns the last database connection if available,
         * otherwise initializes a new database connection
        */

        if (static::$connection) {
            return static::$connection;
        } else {
            new MySQL($pdoConnection);
            return static::$connection;
        }
    }

    public static function set($query, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        $params = array_values($params);

        $statement = static::$connection->prepare($query);
        if ($statement->execute($params)) {
            return $statement->rowCount();
        } else {

            $e = $statement->errorInfo();
            throw new Exception($e[2]);
        }

    }

    public static function get($query, $params = array())
    {
        $statement = static::$connection->prepare($query);
        if (!is_array($params)) {
            $params = array($params);
        }
        if ($statement->execute($params)) {
            return $statement->fetchAll(PDO::FETCH_BOTH);
        } else {
            $e = $statement->errorInfo();
            throw new Exception($e[2]);
        }

    }

    public static function getRow($query, $params = array())
    {
        $result = static::get($query, $params);
        if (count($result) > 0) {
            return $result[0];
        }
        return false;
    }

    public static function getValue($query, $params = array())
    {
        $result = static::get($query, $params);
        if (count($result) > 0) {
            return $result[0][0];
        }
        return false;
    }

    public static function update($table, $fields, $conditions)
    {
        $fieldList = array();
        $conditionList = array();
        $params = array();

        $query = "update $table set ";
        foreach ((array)$fields as $field => $value) {
            $fieldList[] = "`$field` = ?";
            $params[] = $value;
        }
        $query .= implode(", ", $fieldList);
        $query .= " where ";
        foreach ((array)$conditions as $condition => $value) {
            $conditionList[] = "`$condition` = ?";
            $params[] = $value;
        }
        $query .= implode(" and ", $conditionList);

        return static::set($query, $params);
    }

    public static function insert($table, $fields)
    {
        $params = array();
        $fieldList = array();
        $valueList = array();

        foreach ((array)$fields as $field => $value) {
            $fieldList[] = "`" . $field . "`";
            $valueList[] = '?';
            $params[] = $value;
        }
        $fieldList = implode(",", $fieldList);
        $valueList = implode(",", $valueList);
        $query = "insert into $table($fieldList) values ($valueList)";
        static::set($query, $params);

        return static::$connection->lastInsertId();
    }

    public static function massInsert($table, $rows)
    {
        $params = array();
        $fieldList = array();
        $valueList = array();

        $fields = $rows[0];

        foreach ((array)$fields as $field => $value) {
            $fieldList[] = "`" . $field . "`";
            $valueList[] = '?';
            $params[] = $value;
        }
        $fieldList = implode(",", $fieldList);
        $valueList = implode(",", $valueList);
        $query = "insert into $table($fieldList) values ($valueList)";


        try {
            $statement = static::$connection->prepare($query);
            foreach($rows as $row){
                $params = array_values($row);
                if (!$statement->execute($params)) {
                    $e = $statement->errorInfo();
                    throw new \Exception($e[2]);
                    return false;
                }
            }
        } catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }

        return count($rows);
    }

    public static function upsert($table, $fields)
    {
        $params = array();
        $fieldList = array();
        $valueList = array();

        foreach ((array)$fields as $field => $value) {
            $fieldList[] = "`" . $field . "`";
            $valueList[] = '?';
            $params[] = $value;
        }
        $fieldList = implode(",", $fieldList);
        $valueList = implode(",", $valueList);
        $query = "replace into $table($fieldList) values ($valueList)";
        static::set($query, $params);

        return static::$connection->lastInsertId();
    }
    /**
     * Function to escape a string using PDO
     *
     * @param string $var The variable to escape
     *
     * @return string The escaped string
     */
    public static function quote($var)
    {

        $result = static::$connection->quote($var);
        return $result;
    }

    public static function lastInsertId()
    {
        return static::$connection->lastInsertId();
    }
}
