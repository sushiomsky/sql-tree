<?php
/**
 * class: Xml2Sql
 * purpose: nescessary sql querys to manage a tree structure in a SQL DB
 * Focus: Data/tree consistency
 * 
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 0.1
 * @todo
 *
 */
class Xml2Sql {
	
	/**
	 *
	 * @var Object $pdoDb PDO database connection object
	 */
	private $pdoDb;
	
	/**
	 *
	 * @var Object $selectWhereIdStatement
	 */
	private $selectWhereIdStatement;
	
	/**
	 *
	 * @var Object $insertStatment;
	 */
	private $insertStatment;
	
	/**
	 *
	 * @var array $column names for table,lft,rgt,id,name
	 */
	protected $columns;
	
	/**
	 * Connects to the database and checks if table and table columns exists
	 * 
	 * @param mixed[] $dbParams
	 *        	Array structure with database host information and credentials.
	 * @param mixed[] $columns
	 *        	Array structure with table column names.
	 * @return Object Returns a Xml2Sql Object
	 */
	function __construct($dbParams, $columns) {
		try {
			$this->pdoDb = new PDO ( 'mysql:host=' . $dbParams ['host'] . ';dbname=' . $dbParams ['db'], $dbParams ['user'], $dbParams ['password'] );
			$this->columns = $columns;
			$this->prepareSelectWhereId ();
			$this->prepareInsert ();
		} catch ( PDOException $e ) {
			print "Error!: " . $e->getMessage ();
			$this->closeConnection ();
		}
	}
	
	/**
	 * Closes database connection
	 */
	protected function closeConnection() {
		$this->pdoDb = null;
	}
	
	/**
	 *
	 * @return integer $columns['rgt'] highest rgt field in table
	 */
	private function selectRgtOrderDesc() {
		try {
			$selectRgtOrderDescStatement = $this->pdoDb->query ( "SELECT " . $this->columns ['rgt'] . " FROM " . $this->columns ['table'] . " ORDER BY " . $this->columns ['rgt'] . " DESC LIMIT 0,1" );
			$result = $selectRgtOrderDescStatement->fetch ( PDO::FETCH_ASSOC );
			return $result [$this->columns ['rgt']];
		} catch ( PDOException $e ) {
			print "Error!: " . $e->getMessage ();
			$this->closeConnection ();
			exit ( 1 );
		}
	}
	
	/**
	 * Prepares select node by id statement
	 */
	private function prepareSelectWhereId() {
		$this->selectWhereIdStatement = $this->pdoDb->prepare ( "SELECT :id, :rgt, :lft, :parent, :name FROM :table WHERE :id=:idvalue LIMIT 0,1" );
		$this->selectWhereIdStatement->bindParam ( ':table', $this->columns ['table'], PDO::PARAM_STR );
		$this->selectWhereIdStatement->bindParam ( ':rgt', $this->columns ['rgt'], PDO::PARAM_STR );
		$this->selectWhereIdStatement->bindParam ( ':lft', $this->columns ['lft'], PDO::PARAM_STR );
		$this->selectWhereIdStatement->bindParam ( ':id', $this->columns ['id'], PDO::PARAM_STR );
		$this->selectWhereIdStatement->bindParam ( ':parent', $this->columns ['parent'], PDO::PARAM_STR );
	}
	
	/**
	 * Selects node by id
	 * 
	 * @return mixed[] $columns Array structure.
	 */
	private function selectWhereId($id) {
		$this->selectWhereIdStatement->bindParam ( ':idvalue', $id, PDO::PARAM_INT );
		$this->selectWhereIdStatement->execute ();
		$result = $this->selectWhereIdStatement->fetch ( PDO::FETCH_ASSOC );
		return $result;
	}
	
	/**
	 * Prepares an insert statement
	 */
	private function prepareInsert() {
		$this->insertStatement = $this->pdoDb->prepare ( "INSERT INTO " . $this->columns ['table'] . " ( " . $this->columns ['rgt'] . ", " . $this->columns ['lft'] . ", " . $this->columns ['parent'] . ", " . $this->columns ['name'] . ") VALUES( :rgt, :lft, :parent, :name)" );
	}
	
	/**
	 * Inserts a root node at the right place.
	 * 
	 * @param integer $id
	 *        	of the added root node.
	 */
	protected function insertRootNode($name) {
		try {
			$rgt = $this->selectRgtOrderDesc ();
			$left = $rgt + 1;
			$right = $rgt + 2;
			$zero = 0;
			$this->insertStatement->bindParam ( ":lft", $left, PDO::PARAM_INT );
			$this->insertStatement->bindParam ( ":rgt", $right, PDO::PARAM_INT );
			$this->insertStatement->bindParam ( ":name", $name, PDO::PARAM_STR );
			$this->insertStatement->bindParam ( ":parent", $zero, PDO::PARAM_INT );
			$this->insertStatement->execute ();
			return $this->pdoDb->lastInsertId ();
		} catch ( PDOException $e ) {
			print "Error!: " . $e->getMessage ();
			$this->closeConnection ();
			exit ( 1 );
		}
	}
	
	/**
	 * Inserts a node subnode under an element identified by $parentId.
	 * 
	 * @param integer $parentId
	 *        	id of the parent node.
	 * @param string $name
	 *        	name of the node
	 */
	protected function insertNode($parentId, $name) {
		try {
			$this->pdoDb->beginTransaction ();
			$statement = $this->pdoDb->prepare ( "SELECT * FROM " . $this->columns ['table'] . " WHERE " . $this->columns ['id'] . "=" . $parentId );
			$statement->execute ();
			$parent = $statement->fetch ( PDO::FETCH_ASSOC );
			
			// update all $parent's set $rgt +2 WHERE rgt >= $RGT;
			$this->pdoDb->query ( "UPDATE " . $this->columns ['table'] . " SET " . $this->columns ['rgt'] . "=" . $this->columns ['rgt'] . "+2 WHERE " . $this->columns ['rgt'] . ">=" . $parent [$this->columns ['rgt']] . "" );
			$this->pdoDb->query ( "UPDATE " . $this->columns ['table'] . " SET " . $this->columns ['lft'] . "=" . $this->columns ['lft'] . "+2 WHERE " . $this->columns ['lft'] . ">" . $parent [$this->columns ['rgt']] . "" );
			
			// $lft is $parent's $rgt
			// $rgt is $parents's $rgt + 1
			$right = $parent [$this->columns ['rgt']] + 1;
			$this->insertStatement->bindParam ( ":lft", $parent [$this->columns ['rgt']], PDO::PARAM_INT );
			$this->insertStatement->bindParam ( ":rgt", $right, PDO::PARAM_INT );
			$this->insertStatement->bindParam ( ":name", $name );
			$this->insertStatement->bindParam ( ":parent", $parentId );
			$this->insertStatement->execute ();
			$lastId = $this->pdoDb->lastInsertId ();
			$this->pdoDb->commit ();
			return $lastId;
		} catch ( PDOException $e ) {
			$this->pdoDb->rollBack ();
			print "Error!: " . $e->getMessage ();
			$this->closeConnection ();
			exit ( 1 );
		}
	}
	protected function countNodes() {
		$statement = $this->pdoDb->prepare ( "SELECT " . $this->columns ['rgt'] . " FROM " . $this->columns ['table'] . " WHERE 1 ORDER BY " . $this->columns ['rgt'] . " DESC LIMIT 0,1" );
		$statement->execute ();
		$right = $statement->fetch ( PDO::FETCH_ASSOC );
		
		$statement = $this->pdoDb->prepare ( "SELECT " . $this->columns ['lft'] . " FROM " . $this->columns ['table'] . " WHERE 1 ORDER BY " . $this->columns ['lft'] . " ASC LIMIT 0,1" );
		$statement->execute ();
		$left = $statement->fetch ( PDO::FETCH_ASSOC );
		return round ( ($right [0] - $left [0]) / 2 );
	}
	protected function searchNodes($nodeName, $nodeValue) {
		$this->pdoDb->query ( "SELECT ns." . $this->columns ['lft'] . ", ns." . $this->columns ['name'] . " AS element_name, ns2." . $this->columns ['name'] . " AS element_value FROM `" . $this->columns ['table'] . "` AS ns JOIN `" . $this->columns ['table'] . "` AS ns2 WHERE ns." . $this->columns ['name'] . " LIKE '" . $nodeName . "' AND ns2." . $this->columns ['lft'] . ">ns." . $this->columns ['lft'] . " AND ns2." . $this->columns ['rgt'] . "<ns." . $this->columns ['rgt'] . "" );
	}
	
	/**
	 * selects all elements with calculated column for nesting 'level' and 'offspring'
	 */
	public function selectAll() {
		try {
			$statement = $this->pdoDb->prepare ( "SELECT n." . $this->columns ['name'] . ", COUNT(*)-1 AS level, ROUND ((n." . $this->columns ['rgt'] . " - n." . $this->columns ['lft'] . " - 1) / 2) AS offspring FROM " . $this->columns ['table'] . " AS n, " . $this->columns ['table'] . " AS p WHERE n." . $this->columns ['lft'] . " BETWEEN p." . $this->columns ['lft'] . " AND p." . $this->columns ['rgt'] . " GROUP BY n." . $this->columns ['lft'] . " ORDER BY n." . $this->columns ['lft'] . "" );
			$statement->execute ();
			return $statement->fetchAll ( PDO::FETCH_ASSOC );
		} catch ( PDOException $e ) {
			print "Error!: " . $e->getMessage ();
			exit ( 1 );
		}
	}
}

?>