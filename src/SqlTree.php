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
 * Focus: Data/tree consistency
 *
 * @todo I think there is a inf loop when the table is empty...
 * @todo every public & protected function should be tested with random data in random execution order
 * @todo data structure verification
 */
class SqlTree extends SqlDB{

	
	/**
	 * @var array QUERYS Sql querys nescessary to manage the tree structure
	 */
	const QUERYS = [
			'DELETE_NODE'             => 'DELETE
	                                      FROM `nested_set` 
	                                      WHERE id=:value_id;',

	        'SELECT_WHERE_ID'         => 'SELECT id , rgt, lft, parent, name
	                                      FROM `nested_set`
	                                      WHERE id=:value_id
	                                      LIMIT 1;',
	    	                                       
			'SELECT_TOP_RGT'          => 'SELECT rgt FROM `nested_set`
	                                      WHERE 1 
	                                      ORDER BY rgt DESC 
	                                      LIMIT 1;',
	    
	        'SELECT_COUNT_ENTRIES'    => 'SELECT name, 
	                                      COUNT(*) AS entries
	                                      FROM `nested_set`',
	    
	        'SELECT_CHILD_NODES'      => 'SELECT o.name,
                                    	  COUNT(p.id)-1 AS level
                                    	  FROM `nested_set` AS n,
                                    	  `nested_set` AS p,
                                    	  `nested_set` AS o
                                    	  WHERE o.lft BETWEEN p.lft AND p.rgt
                                    	  AND o.lft BETWEEN n.lft AND n.rgt
                                    	  AND n.id = 2
                                    	  GROUP BY o.lft
                                    	  ORDER BY o.lft;',
	                	                           
	        'UPDATE_RGT_BEFORE_INSERT'    => 'UPDATE `nested_set` 
	                                      SET rgt = rgt +2
	                                      WHERE rgt >= :value_rgt;',
	    
	        'UPDATE_LFT_BEFORE_INSERT'    => 'UPDATE `nested_set`
	                                      SET lft = lft +2
	                                      WHERE lft > :value_rgt;',
	    	                                       
			'INSERT_NODE'             => 'INSERT INTO `nested_set` (lft , rgt , parent , name) 
	                                      VALUES( :value_lft , :value_rgt , :value_parent , :value_name )',
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

	function __construct(&$pdo){
	    parent::__construct($pdo);
	    $this->prepareStatements(SqlTree::QUERYS);   
	}
	/**
	 * Get a node by id.
	 *
	 * @param int $id sql table row id
	 * @return mixed[]
	 */
	public function getNode($id){
	    $this->statements['select_where_id']->bindValue(':value_id', $id);
	    return $this->executeSelectStatement('select_where_id');
	}

	/**
	 * Get Node and childnodes below $id.
	 * 
	 * @param int $id id of the node
	 */
	public function getChildNodes($id){
	    $this->statements['select_child_nodes']->bindParam(':value_id', $id);
	    $this->executeStatement('select_child_nodes');
	    if ($this->statements['select_where_id']->rowCount > 0) {
	       return $this->statements['select_child_nodes']->fetchAll ( PDO::FETCH_ASSOC );
	    }else {
	       return false;
	    }
	}
	
	/**
	 * Add a node position is specified by $id.
	 * 
	 * @param unknown $name The node name.
	 * @param number $id The id of the parent node.
	 * @return string
	 */
	public function addRootNode($name){
	    $rgt = $this->selectRgtOrderDesc();
    	$this->statements['insert_node']->bindValue ( ':value_lft', $rgt + 1, PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue ( ':value_rgt', $rgt + 2, PDO::PARAM_INT );
		$this->statements['insert_node']->bindValue ( ':value_name', $name, PDO::PARAM_STR );
		$this->statements['insert_node']->bindValue ( ':value_parent', 0, PDO::PARAM_INT );
		$this->executeStatement('insert_node');
		return $this->pdo->lastInsertId('id');
	}

	/**
	 * Add a node position is specified by $id.
	 *
	 * @param unknown $name The node name.
	 * @param number $id The id of the parent node.
	 * @return string
	 */
	public function addNode($name, $id){
        $parent = $this->getNode($id);
        if ($parent[0][SqlTree::COLUMNS['id']] == $id) {
            $rgt = $parent[0][SqlTree::COLUMNS['rgt']];
            $this->statements['insert_node']->bindValue ( ':value_lft', $rgt, PDO::PARAM_INT );
    	    $this->statements['insert_node']->bindValue ( ':value_rgt', $rgt + 1, PDO::PARAM_INT );
    	    $this->statements['insert_node']->bindValue ( ':value_name', $name, PDO::PARAM_STR );
    	    $this->statements['insert_node']->bindValue ( ':value_parent', $id, PDO::PARAM_INT );
		    $this->executeStatement('insert_node');
    	    $id = $this->pdo->lastInsertId('id');
    	    $this->updateBeforeInsert($rgt);
    	    return $id;
        }else {
            return false;
        }
	}
	
	/**
	 * Validates the table tree structure.
	 *
	 * @return bool
	 */
	public function validateTree(){
	    $this->executeStatement('select_count_entries');
	    $entries = $this->statements['select_count_entries']->fetchAll ( PDO::FETCH_ASSOC );
	    if ((round($this->selectRgtOrderDesc() / 2)) == $entries[0]['entries']){
	            return true;
	    }else {
    	   return false;
	    }
	}

	/**
	 * Returns the right most root node
	 * 
	 * @return int $columns['rgt'] highest rgt field in table.
	 */
	private function selectRgtOrderDesc() {
		$this->executeStatement('select_top_rgt');
		$result = $this->statements['select_top_rgt']->fetchAll ( PDO::FETCH_ASSOC );
		if ($this->statements['select_top_rgt']->rowCount() > 0) {
    		return $result[0][SqlTree::COLUMNS['rgt']];
		}else {
		    return 0;
		}
	}

	/**
	 * Updates the Tree lft/rgt after an insert.
	 *
	 * @param int $value the rgt/lft value
	 * @return void
	 */
	private function updateBeforeInsert($value){
		$this->statements['update_rgt_before_insert']->bindValue(':value_rgt', $value);
		$this->executeStatement('update_rgt_before_insert');
		$this->statements['update_lft_before_insert']->bindValue(':value_rgt', $value);
		$this->executeStatement('update_lft_before_insert');
	}
}
