<?php
use Suchomsky\SqlTree\SqlTree;

abstract class SqlTreeSetup extends PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;

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

class SqlTreeTest extends SqlTreeSetup
{
    public function testInitialDataStructure()
    {
    	$dataSet = $this->getConnection()->createDataSet(['nested_set']);
        $expectedDataSet = $this->createMySQLXMLDataSet('./tests/_files/treetable.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }
    
    public function testTreeModifier(){
    	$dbCreds['host'] = 'localhost';
    	$dbCreds['db'] = $GLOBALS['DB_DBNAME'];
    	$dbCreds['user'] = $GLOBALS['DB_USER'];
    	$dbCreds['password'] = $GLOBALS['DB_PASSWD'];
    	
    	$pdo = SqlTree::connectDb($dbCreds);
    	$sqlTree = new SqlTree($pdo);

    	$this->assertTrue($sqlTree->validateTree());
    	$sqlTree->insertRootNode('rootnode');
    	$this->assertTrue($sqlTree->validateTree());
    	$sqlTree->insertSubNode('subnode');
    	$this->assertTrue($sqlTree->validateTree());
    	$sqlTree->insertNode('brothernode');
    	$this->assertTrue($sqlTree->validateTree());
    	
    	$xmlTree = new XmlTree('https://www.jpc.de/jpcng/home/xml/-/task/search/dosearch/1/k/jazz/medium/LP/searchcount/40', $dbCreds);
    }
    
    
}
?>