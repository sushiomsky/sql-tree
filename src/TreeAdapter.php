<?php
use Suchomsky\SqlTree\SqlTree;

/**
 * class: TreeAdapter
 * purpose: 
 * Focus: Data/tree consistency
 * 
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class TreeAdapter extends SqlTree 
{
	function __construct($dbCreds, $columns) {
		parent::__construct ( $dbCreds, $columns );
	}
	protected function createRootNode($name) {
	}
	protected function createSubNode() {
	}
	protected function deleteNode() {
	}
	protected function getAllNodes() {
	}
	protected function getChildren() {
	}
	protected function getParent() {
	}
	protected function getParents() {
	}
	protected function getRootNodes() {
	}
	protected function moveTree() {
	}
	protected function pickNode() {
	}
	protected function updateParent() {
	}
}