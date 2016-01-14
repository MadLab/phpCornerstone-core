<?php

namespace MadLab;

use Carbon\Carbon;
use League\Route\Http\Exception;
use PDO;

class BedRock
{

	private $connection;
	private $sql;
	private $returnFields = '*';
	private $fieldValues = [];
	private $params;

	function __construct(PDO $connection)
	{
		$this->connection = $connection;
		if (!isset($this->fields)) {
			throw new \Exception('Class ' . get_class($this) . ' failed to define Database fields');
		}
	}

	function __get($name)
	{
		if (isset($this->fieldValues[$name])) {
			return $this->fieldValues[$name];
		} else {
			throw new \Exception("Trying to access non-existent field `$name` of class " . get_class($this));
		}
	}

	function __set($name, $value)
	{
		if (isset($this->fields[$name])) {
			$type = $this->fields[$name];
			switch ($type) {
				case 'date':
					if ($value instanceof Carbon) {
						$this->fieldValues[$name] = $value->setTimezone('UTC');
					} else {
						$this->fieldValues[$name] = Carbon::parse($value, 'UTC');
					}
					break;
				default:
					$this->fieldValues[$name] = $value;
			}
		}
		else{
			throw new \Exception("Trying to set non-existent field `$name` of class " . get_class($this));
		}
	}

	function __isset($name)
	{
		return isset($this->fieldValues[$name]);
	}

	function get(String $id) : \stdClass
	{
		$this->sql = sprintf("select %s from %s where %s = ? limit 1", $this->returnFields, $this->table, $this->primary);
		$this->params = [$id];
		return $this->getOne();
	}


	function update() : int
	{
		$fieldList = array();
		$params = array();


		$query = "update $this->table set ";
		foreach (array_keys($this->fields) as $field) {
			$fieldList[] = "`$field` = ?";
			$params[] = $this->fieldValues[$field];
		}
		$query .= implode(", ", $fieldList);
		$query .= " where `$this->primary` = ?";
		$params[] = $this->fieldValues[$this->primary];

		$statement = $this->connection->prepare($query);
		if ($statement->execute($params)) {
			return $statement->rowCount();
		} else {
			$e = $statement->errorInfo();
			throw new \Exception(json_encode($e));
		}
	}

	function query($sql, $params = []) : BedRock
	{
		$this->sql = $sql;
		$this->params = $params;
		return $this;
	}

	function getOne() : \stdClass
	{
		$this->sql .= ' limit 1';
		$statement = $this->connection->prepare($this->sql);
		if (!is_array($this->params)) {
			$this->params = array($this->params);
		}
		if ($statement->execute($this->params)) {
			$rows = $statement->fetchObject(get_class($this), [$this->connection]);
			if (count($rows) > 0) {
				return $rows[0];
			}
			return null;
		} else {
			$e = $statement->errorInfo();
			throw new \Exception(json_encode($e));
		}
	}

	function getMany() : Array
	{
		$this->sql .= ' limit 10';
		$statement = $this->connection->prepare($this->sql);
		if (!is_array($this->params)) {
			$this->params = array($this->params);
		}

		if ($statement->execute($this->params)) {
			$rows = [];
			while ($row = $statement->fetchObject(get_class($this), [$this->connection])) {
				$rows[$row->{$row->primary}] = $row;
			}
			return $rows;
		} else {
			$e = $statement->errorInfo();
			throw new \Exception(json_encode($e));
		}
	}
}

/*
 * $userValue = User::fields([fname,lname])->filter(1)->getOne();
$userValues = User::fields([fname,lname])->filter([fname=>'john'])->getMany());
$userValue = User::query('select *')->getOne();


$user = User::fromValues($userValue);
$use->save();


$useValue = User::filter([fname=>'john'])->filter(['lname'=>'smith'])->getMany();


$userValue = User::add([fname=>'john', lname=>'blah']);
User::set(['field'=>'val'])->where()->update();


$userProfileValues = UserProfiles::bulkGet([1,2,3,4,5])

{foreach $userValues as $user}
	{$userProfileValues[$user->id]->bio}
{/foreach}


Class User extends MadORM{
	protected $table = 'user';
}
 */