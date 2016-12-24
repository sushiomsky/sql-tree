<?php
/**
 * class: NestedSets
 * purpose: manage a tree structure in a SQL DB
 * Focus: Data/tree consistency
 * 
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

class NestedSets extends NestedPdo{
	/**
	 * Connects to the database and prepare the sql statment objects.
	 * @param mixed[] $dbParams Array structure with database host information and credentials.
	 * @param mixed[] $columns Array structure with table column names.
	 * @return NestedSql Returns a NestedSql Object
	 */
	function __construct($dbParams, $columns) {
		parent::__construct($dbParams, $columns);
	}
	/**
	 * Creates a root node
	 * @param string $name name of the node.
	 */
	protected  function createRootNode($name){
		$id = $this->insertRootNode($name);
		return $id;
	}
	
	protected function createSubNode($parentId, $name){
		$id = $this->insertNode($parentId, $name);
		return $id;
	}
	
	protected function deleteNode(){
	
	}
	
	protected function getAllNodes(){
	
	}
	
	protected function getChildren(){
	
	}
	
	protected function getParent(){
	
	}
	
	protected function getParents(){
	
	}
	
	protected function getRootNodes(){
	
	}
	
	protected function moveTree(){
	
	}
	
	protected function pickNode(){
	
	}
	
	protected function updateParent(){
	
	}
}