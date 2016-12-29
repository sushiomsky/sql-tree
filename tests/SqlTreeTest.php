<?php
use Suchomsky\SqlTree\SqlTree;

abstract class GenericDatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;
    }
    
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
  		return $this->createMySQLXMLDataSet('./tests/_files/treetable.xml');
    }
}

class SqlTreeTest extends GenericDatabaseTestCase
{
    public function testCreateQueryTable()
    {
    	$dataSet = $this->getConnection()->createDataSet(['nested_set']);
        $expectedDataSet = $this->createMySQLXMLDataSet('./tests/_files/treetable.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }
    
    public function testTreeModifier(){
    	$dbCreds['host'] = 'localhost';
    	$dbCreds['db'] = 'sqltree';
    	$dbCreds['user'] = 'root';
    	$dbCreds['password'] = '1234';
    	
    	$sqlTree = new SqlTree(SqlTree::connectDb($dbCreds));
    	$sqlTree->insertRootNode('rootnode');
    	$sqlTree->insertSubNode('subnode');
    	$sqlTree->insertNode('brothernode');
    }
}
?>