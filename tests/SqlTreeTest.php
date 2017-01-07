<?php
use Suchomsky\SqlTree\SqlTree;
use Suchomsky\SqlTree\SqlDB;

abstract class SqlTreeSetup extends PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;

    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWORD'] );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_NAME']);
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

    private function setupTestCondition(){
        $dataSet = $this->getConnection()->createDataSet(['nested_set']);
        $expectedDataSet = $this->createMySQLXMLDataSet('./tests/_files/treetable.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }
    
    private function getSqlTree(){
        $pdo = SqlTree::connectDb( $GLOBALS['DB_HOST'],  $GLOBALS['DB_NAME'],  $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWORD']);
        $this->sqlTree = new SqlTree($pdo);
    }
    
    public function testTreeModifier(){
        $this->setupTestCondition();
        $this->getSqlTree();
    
        $this->assertTrue($this->sqlTree->validateTree());
        $rootId = $this->sqlTree->addRootNode('rootnode');
        $this->assertTrue($this->sqlTree->validateTree());
        $id = $this->sqlTree->addNode('subnode',$rootId);
        $this->assertTrue($this->sqlTree->validateTree());
        $this->sqlTree->addNode('subsubnode',$id);
        $this->assertTrue($this->sqlTree->validateTree());
    }
    
    
    
    /*
    public function testXmlTree(){
    	$dbCreds['host'] = 'localhost';
    	$dbCreds['db'] = $GLOBALS['DB_DBNAME'];
    	$dbCreds['user'] = $GLOBALS['DB_USER'];
    	$dbCreds['password'] = $GLOBALS['DB_PASSWD'];
    	
    	$pdo = SqlTree::connectDb($dbCreds);
      	$xmlTree = new XmlTree('./tests/_files/xmlimport.xml', $pdo);
    	$this->assertTrue($xmlTree->validateTree());
    }
    */
    
}
?>