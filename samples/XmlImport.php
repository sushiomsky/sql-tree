<?php
error_reporting(E_ALL);
use Suchomsky\SqlTree\XmlTree;
use Suchomsky\SqlTree\SqlTree;

require __DIR__ . '/../vendor/autoload.php';
/**
 * class: classname
 * purpose: description
 * 
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @todo  
*/
$pdo = SqlTree::connectDb();
$tree = new XmlTree('../tests/_files/xmlimport.xml', $pdo);