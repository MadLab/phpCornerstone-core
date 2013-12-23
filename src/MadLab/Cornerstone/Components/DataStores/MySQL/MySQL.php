<?php

namespace MadLab\Cornerstone\Components\DataStores\MySql;

use MadLab\Cornerstone\App;
use \PDO;

class MySQL
{

    private $_host;
    private $_user;
    private $_password;
    private $_dbname;
    private static $connection;

    /**
     * Constructor initializes database PDO connection
     *
     * @param array $connectionParams array containing host, username, password, and database to connect to
     */
    private function __construct($connectionParams)
    {
        $host = $connectionParams['host'] ? $connectionParams['host'] : 'localhost';
        $user = $connectionParams['user'];
        $password = $connectionParams['password'];
        $dbname = $connectionParams['dbname'];

        try {
            static::$connection = new PDO("mysql:host={$host};dbname={$dbname}", $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            return true;
        } catch (PDOException $e) {
            throw new \Exception("Database Connection Error");
            return false;
        }
    }

    /**
     * Retrieve a database connection with the given connection parameters. Will create connection if none exists
     * @static getConnection
     *
     * @param  array $connectionParams
     *
     * @return Database Database Instance
     */
    public static function getConnection($connectionParams)
    {
        /*
        * Returns the last database connection if available,
         * otherwise initializes a new database connection
        */

        if (static::$connection) {
            return static::$connection;
        } else {
            new MySQL($connectionParams);
            return static::$connection;
        }
    }

    /**
     * Queries database and returns number of affected rows. Used for any queries that insert/update info
     *
     * @param string $query The parameterized query to execute
     * @param array $params Array of parameters to fill query with
     *
     * @return bool|int The number of affected rows, or false on error
     */
    public static function set($query, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        $params = array_values($params);
        try {
            $statement = static::$connection->prepare($query);
            if ($statement->execute($params)) {
                return $statement->rowCount();
            } else {

                $e = $statement->errorInfo();
                throw new \Exception($e[2]);
                return false;
            }

        } catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Queries database and returns the resulting data. Used for any queries that fetch data
     *
     * @param string $query The parameterized query to execute
     * @param array $params Array of parameters to fill query with
     *
     * @return boolean|array The resulting data, or false on error
     */
    public static function get($query, $params = array())
    {
        try {
            $statement = static::$connection->prepare($query);
            if (!is_array($params)) {
                $params = array($params);
            }
            if ($statement->execute($params)) {
                return $statement->fetchAll(PDO::FETCH_BOTH);
            } else {
                $e = $statement->errorInfo();
                throw new \Exception($e[2]);
                return false;
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    /**
     * Convenience function to return a single row when executing a 'get' style query. Used when fetching a single row.
     *
     * @param  string $query The parameterized query to execute
     * @param array|string $params Array of parameters to fill query with
     *
     * @return boolean|array Single row result, or false on error
     */
    public static function getRow($query, $params = array())
    {
        $result = static::get($query, $params);
        if (count($result) > 0) {
            return $result[0];
        }
        return false;
    }

    /**
     * Convenience function to return a single value when executing a 'get' style query. Used when fetching a single values
     *
     * @param string $query The parameterized query to execute
     * @param array $params Array of parameters to fill query with
     *
     * @return string|boolean The value, or false on error
     */
    public static function getValue($query, $params = array())
    {
        $result = static::get($query, $params);
        if (count($result) > 0) {
            return $result[0][0];
        }
        return false;
    }

    /**
     * Convenience function to format an update query
     *
     * @param string $table Table to update
     * @param array $fields Array of key=>value containing columns to update
     * @param array $conditions Array of key=>value containing conditions to match
     *
     * @return boolean|int Number of rows affected, or false on error
     */
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

    /**
     * Convenience function to format an insert query
     *
     * @param array $table Table to insert into
     * @param array $fields Array of key=>value containing columns to insert into
     *
     * @return int The last insert ID
     */
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


    /**
     * Convenience function to insert multiple rows at once
     *
     * @param array $table Table to insert into
     * @param array $fields Nested Array, each Item is Array of key=>value containing columns to insert into
     *
     * @return int The last insert ID
     */
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

    public static function getPDOInstance()
    {
        return static::$connection;
    }
}
