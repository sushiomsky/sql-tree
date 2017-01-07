<?php
use Suchomsky\SqlTree\SqlTree;
error_reporting(E_ALL);
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
require __DIR__ . '/../vendor/autoload.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=sqltree;charset=UTF8",'root','1234');
    $tree = new SqlTree($pdo);
   $id = $tree->addRootNode('hallo');
    $tree->addNode('sub',$id);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    echo $tree->getErrors();
}
