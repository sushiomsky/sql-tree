<?php
/**
 * class: classname
 * purpose: description
 * 
 * @copyright Copyright (C) 2001-2016 Webschreinerei
 * @author Dennis Suchomsky dennis.suchomsky@gmail.com
 * @license GPL
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @todo
 *
 */
use PHPUnit\Framework\TestCase;
class MoneyTest extends TestCase {
	public function testWorkingEnvieroment() {
		$pdo = new PDO ( 'mysql:host=localhost;dbname=nested_sets' );
	}
}
?>