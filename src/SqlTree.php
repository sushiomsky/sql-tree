<?php
/**
 * The SqlTree.php stores the SqlTree class.
 *
 * @name SqlTree.php
 *
 * @copyright Copyright (C) 2001-2016 Webschreinerei / Suchomsky
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @link https://github.com/sushiomsky/SqlTree
 * @license GPL3
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace Suchomsky\SqlTree;

use PDO;

/**
 * SqlTree Tree data structure in a SQL Table.
 *
 * To create and manage a nested set tree structure in a SQL Database.
 * Infinite child nodes possible with linear runtime.
 * The objects created have a nodepointer, you navigate either wit the insert functions or with the levelUp().
 * Focus: Data/tree consistency
 *
 * @todo I think there is a inf loop when the table is empty...
 */
class SqlTree {

	/**
	 * @var \PDO|null PDO database connection object
	 */
	private $pdo = null;

	/**
	 * @var array|null pdo prepared statements
	 */
	private $statements = null;

	/**
	 * @var array|null stack of node id's last element is the id of the last node inserted
	 */
	private $nodePointer = null;

	/**
	 * @var array|null error messages
	 */
	private $errors = null;

	/**
	 * @var array QUERYS Sql querys nescessary to manage the tree structure
	 */
	const QUERYS = [
			'SELECT_WHERE_ID'         => 'SELECT id , rgt, lft, parent, name FROM `nested_set` WHERE id=:value_id LIMIT 1',
			'SELECT_TOP_RGT'          => 'SELECT rgt FROM `nested_set` WHERE 1 ORDER BY rgt DESC LIMIT 1',
	        'SELECT_COUNT_ENTRIES'    => 'SELECT n.name, COUNT(*)-1 AS level, ROUND ((n.rgt - n.lft - 1) / 2) AS offspring FROM `nested_set` AS n, `nested_set` AS p WHERE n.lft BETWEEN p.lft AND p.rgt GROUP BY n.lft ORDER BY n.lft;',
	        'UPDATE_PARENTS_RGT'      => 'UPDATE `nested_set` SET rgt = rgt +2 WHERE rgt >= :value_rgt',
			'UPDATE_PARENTS_LFT'      => 'UPDATE `nested_set` SET lft = lft +2 WHERE lft > :value_rgt',
			'INSERT_NODE'             => 'INSERT INTO `nested_set` (lft , rgt , parent , name) VALUES( :value_lft , :value_rgt , :value_parent , :value_name )',
	];

	/**
	 * @var mixed[] default table and column names.
	 */
	const COLUMNS = [
			'table' => 'nested_set',
			'id' => 'id',
			'lft' => 'lft',
			'rgt' => 'rgt',
			'parent' => 'parent',
			'name' => 'name'];

	/**
	 * @var mixed[] default database credentials.
	 */
	const DB_CREDS = [
	    'host' => 'localhost',
	    'db' => 'sqltree',
	    'user' => 'root',
	    'password' => '1234'];

	/**
	 * Validation of database/pdo object & table columns closes connection on error
	 *
	 * @param \PDO $pdo
	 * @param array|null $columns
	 */
	public function __construct(&$pdo, $columns = null) {
		if ($columns == null) {
			$this->columns = self::COLUMNS;
		}
		try {
			$this->pdo = $pdo;
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->prepareStatements();
			$this->pdo->beginTransaction();
		} catch (PDOException $e) {
			$this->errors[] = $e->getMessage ();
			$this->closeConnection ();
		}
	}

	/**
	 * Connects to the database and creates a \PDO object nescessary for the constructor
	 *
	 * @param unknown|null $dbCreds
	 * @return NULL|\PDO
	 */
	public static function connectDb($dbCreds = null) {
	    if ($dbCreds == null) {
	        $dbCreds = self::DB_CREDS;
	    }
		try {
			$pdo = new PDO( 'mysql:host=' . $dbCreds['host'] . ';dbname=' . $dbCreds['db'], $dbCreds['user'], $dbCreds['password'] );
		} catch (\PDOException $e) {
		    echo $e->getMessage();
			$pdo = null;
		}
		return $pdo;
	}

	/**
	 * Get a node by id
	 *
	 * @param int $id sql table row id
	 * @return mixed[]
	 */
	public function getNodeById($id){
	    $this->statements['select_where_id']->bindParam(':value_id', $id);
	    $this->statements['select_where_id']->execute();
	    return $this->statements['select_where_id']->fetch ( PDO::FETCH_ASSOC );
	}

	/**
	 *
	 */
	public function getTree(){
	    $this->statements['select_tree']->execute();
		return $this->statements['select_tree']->fetchAll( PDO::FETCH_ASSOC );
	}

	/**
	 * Returns an array with error messages.
	 *
	 * @return mixed[]
	 */
	public function getErrors(){
	    return $this->errors;
	}

	/**
	 * Add a node at current position at the same level as current node.
	 *
	 * @param string $name Nodename
	 * @return void
	 */
	public function insertNode($name){
		$neighbour = $this->getNodeById($this->nodePointer[count($this->nodePointer) - 1]);
		$this->updateWhereLftRgtHigherEqual($neighbour[$this->columns['rgt']]);

		$this->statements['insert_node']->bindValue( ':value_lft', $neighbour[$this->columns['rgt']] + 1, PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue( ':value_rgt', $neighbour[$this->columns['rgt']] + 2, PDO::PARAM_INT );
		$this->statements['insert_node']->bindParam( ':value_name', $name, PDO::PARAM_STR );
		$this->statements['insert_node']->bindValue( ':value_parent', $neighbour[$this->columns['parent']], PDO::PARAM_INT );
		$this->statements['insert_node']->execute ();
		$this->nodePointer[] = $this->pdo->lastInsertId ();
	}

	/**
	 * Add a Subnode at current position.
	 *
	 * @param string $name Nodename
	 * @return void
	 */
	public function insertSubNode($name){
		$parent = $this->getNodeById($this->nodePointer[count($this->nodePointer) - 1]);
		$this->updateWhereLftRgtHigherEqual($parent[$this->columns['rgt']]);

		$this->statements['insert_node']->bindValue( ':value_lft', $parent[$this->columns['rgt']], PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue( ':value_rgt', $parent[$this->columns['rgt']] + 1, PDO::PARAM_INT );
		$this->statements['insert_node']->bindParam( ':value_name', $name, PDO::PARAM_STR );
		$this->statements['insert_node']->bindValue( ':value_parent', $parent[$this->columns['parent']], PDO::PARAM_INT );
		$this->statements['insert_node']->execute ();
		$this->nodePointer[] = $this->pdo->lastInsertId ();
	}

	/**
	 * Add a root node
	 *
	 * @param string $name Nodename
	 * @return void
	 */
	public function insertRootNode($name){
		$this->nodePointer = null;
		$rgt = $this->selectRgtOrderDesc();
		$left = $rgt + 1;
		$right = $rgt + 2;
		$this->statements['insert_node']->bindValue ( ':value_lft', $rgt + 1, PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue ( ':value_rgt', $rgt + 2, PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue ( ':value_name', $name, PDO::PARAM_STR );
		$this->statements['insert_node']->bindValue ( ':value_parent', 0, PDO::PARAM_INT );
		$this->statements['insert_node']->execute ();
		$this->nodePointer[] = $this->pdo->lastInsertId('id');
	}

	/**
	 * Validates the table tree structure and commits/rolls back the transaction
	 *
	 * @return bool
	 */
	public function validateTree(){
	    $this->statements['select_count_entries']->execute();
	    $entries = $this->statements['select_count_entries']->fetch ( PDO::FETCH_ASSOC );
	    if ((round($this->selectRgtOrderDesc() / 2)) == $entries['entries']){
	        return true;
	    }

		return false;
	}

	/**
	 * puts the cursor a level higher.
	 *
	 * @return bool
	 */
	public function levelUp(){
	    array_pop($this->nodePointer);
	}

	/**
	 * commit/rollback the transaction.
	 *
	 * @return bool
	 */
	private function transactionHandler(){
	    if (count($this->errors) > 0 && $this->pdo->inTransaction()) {
	       $this->pdo->rollBack();
	       foreach ($errors as $error){echo $error;}
	       return false;
	    }
		if ($this->pdo->inTransaction()) {
	        $this->pdo->commit();
	    }
	    return true;
	}

	/**
	 * prepares pdo statements and bind static params such as tablename and column names.
	 *
	 * @return void
	 */
	private function prepareStatements() {
		$keys = array_keys ( self::QUERYS );
		foreach ($keys as $key) {
			$loKey = strtolower ( $key );
			$this->statements[$loKey] = $this->pdo->prepare ( self::QUERYS[$key] );
		}
	}

	/**
	 * @return int $columns['rgt'] highest rgt field in table.
	 */
	private function selectRgtOrderDesc() {
		$this->statements['select_top_rgt']->execute();
		$result = $this->statements['select_top_rgt']->fetch ( PDO::FETCH_ASSOC );
		return $result[$this->columns['rgt']];
	}

	/**
	 * Yes 2x times :value_rgt is correct!
	 *
	 * @param int $value the rgt/lft value
	 * @return void
	 */
	private function updateWhereLftRgtHigherEqual($value){
		$this->statements['update_parents_lft']->bindValue(':value_rgt', $value);
		$this->statements['update_parents_lft']->execute();
		$this->statements['update_parents_rgt']->bindValue(':value_rgt', $value);
		$this->statements['update_parents_rgt']->execute();
	}

	/**
	 * closes the database connection.
	 *
	 * @return void
	 */
	private function closeConnection() {
	    $this->transactionHandler();
		$this->pdo = null;
	}

	/**
	 * just in case there is an uncommited transaction or open database connection.
	 */
	function __destruct(){
	    $this->closeConnection();
	}

}
