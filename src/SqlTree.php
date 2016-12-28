<?php

namespace Suchomsky\SqlTree;

/**
 * class: SqlTree
 * purpose: Manages a nested sets tree structure in a SQL DB
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
class SqlTree {
	
	/**
	 *
	 * @var Object $pdo PDO database connection object
	 */
	private $pdo = null;
	
	/**
	 *
	 * @var arrray $columns table and column names
	 */
	private $columns = null;

	/**
	 *
	 * @var array $statements pdo prepared statements
	 */
	private $statements = null;

	/**
	 *
	 * @var array $nodePointer stack of node id's last element is the id of the last node inserted
	 */
	private $nodePointer = array(0 => 0);
	
	/**
	 *
	 * @var array QUERYS Sql querys nescessary to manage the tree structure
	 */
	const QUERYS = array (
			'SELECT_WHERE_ID' => 'SELECT :id, :rgt, :lft, :parent, :name FROM :table WHERE :id=:value_id LIMIT 0,1',
			'SELECT_TOP_RGT' => 'SELECT :rgt FROM :table WHERE 1 ORDER BY :rgt DESC LIMIT 0,1',
			'UPDATE_PARENTS_RGT' => 'UPDATE :table SET :rgt = :rgt +2 WHERE :rgt >= :value_rgt',
			'UPDATE_PARENTS_LFT' => 'UPDATE :table SET :lft = :lft +2 WHERE :lft >= :value_lft',
			'INSERT_NODE' => 'INSERT INTO :table (:lft, :rgt, :parent, :name) VALUES(:value_lft, :value_rgt, :value_parent, :value_name)' 
	);
	
	/**
	 * Connects to the database and checks for valid column names
	 * 
	 * @param array $dbCreds        	
	 * @param array $columns        	
	 */
	function __construct($dbCreds, $columns) {
		try {
			$this->pdo = new PDO ( 'mysql:host=' . $dbCreds ['host'] . ';dbname=' . $dbCreds ['db'], $dbCreds ['user'], $dbCreds ['password'] );
			$this->columns = $columns;
			$this->prepareStatements ();
		} catch ( PDOException $e ) {
			print "Error!: " . $e->getMessage ();
			$this->closeConnection ();
		}
	}
	
	/**
	 * Add a node at current position
	 * @param String $name Nodename
	 */
	public function addNode($name){
		
	}
	
	/**
	 * Add a Subnode at current position
	 * @param String $name Nodename
	 */
	public function addSubNode($name){
	
	}
	
	/**
	 * Add a root node
	 * @param String $name Nodename
	 */
	public function addRootNode($name){
	
	}

	/**
	 * prepares pdo statements and bind static params such as tablename and column names
	 */
	private function prepareStatements() {
		$keys = array_keys ( QUERYS );
		foreach ( $keys as $key ) {
			$loKey = strtolower ( $key );
			$this->statements[$loKey] = $this->pdo->prepare ( QUERYS [$key] );

			$this->statements[$loKey]->bindParam(':table',$this->columns['table']);
			if (strpos(QUERYS[$key], ':id') !== false) {
				$this->statements[$loKey]->bindParam(':id',$this->columns['id']);
			}
			if (strpos(QUERYS[$key], ':lft') !== false) {
				$this->statements[$loKey]->bindParam(':lft',$this->columns['lft']);
			}
			if (strpos(QUERYS[$key], ':rgt') !== false) {
				$this->statements[$loKey]->bindParam(':rgt',$this->columns['rgt']);
			}
			if (strpos(QUERYS[$key], ':parent') !== false) {
				$this->statements[$loKey]->bindParam(':parent',$this->columns['parent']);
			}
			if (strpos(QUERYS[$key], ':name') !== false) {
				$this->statements[$loKey]->bindParam(':name',$this->columns['name']);
			}
		}
	}

	/**
	 * Validates tree consistency
	 */
	private function validateTree() {
		return false;
	}
	
	/**
	 * close database connection
	 */
	private function closeConnection() {
		$this->pdo = null;
	}
}

?>