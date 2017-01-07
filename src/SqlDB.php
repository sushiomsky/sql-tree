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
*/
namespace Suchomsky\SqlTree;

use PDO;

abstract class SqlDB{
    
    /**
     * @var \PDO|null PDO database connection object
     */
    protected $pdo = null;
    
    /**
     * @var array|null error messages
     */
    protected $errors = null;
    
    /**
	 * @var array|null pdo prepared statements
	 */
	protected $statements = null;

	/**
     * Validation of database/pdo object & table columns closes connection on error
     *
     * @param \PDO $pdo
     * @param array|null $columns
     */
    public function __construct(&$pdo) {
        try {
            $this->pdo = $pdo;
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    public static function connectDb($host, $db, $user, $password) {
        try {
            $pdo = new PDO( 'mysql:host=' . $host . ';dbname=' . $db, $user, $password );
        } catch (PDOException $e) {
            echo $e->getMessage();
            $pdo = null;
        }
        return $pdo;
    }
    

    /**
     * Returns an array of strings with error messages.
     *
     * @return mixed[]
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Calls the transaction Handler and closes the database connection.
     *
     * @return void
     */
    private function closeConnection() {
        $this->transactionHandler();
        $this->pdo = null;
        print_r($this->errors);
    }
    
    /**
     * commit/rollback the transaction.
     *
     * @return bool
     */
    private function transactionHandler(){
        try{
            if (count($this->errors) > 0 && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                return false;
            }elseif ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            return true;
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage ();
            $this->closeConnection ();
        }
    }
    
    /**
     * prepares pdo statements and bind static params such as tablename and column names.
     *
     * @return void
     */
    protected function prepareStatements($querys) {
        try{
            $keys = array_keys ( $querys );
            foreach ($keys as $key) {
                $loKey = strtolower ( $key );
                $this->statements[$loKey] = $this->pdo->prepare ( $querys[$key] );
            }
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage ();
            $this->closeConnection ();
        }
    }
    
    /**
     * executes a prepared statement
     *
     * @return void
     */
    protected function executeStatement($queryName) {
        try {
            $this->statements[$queryName]->execute();
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage ();
            $this->closeConnection ();
        }
    }
    
    /**
     * executes a prepared statement
     *
     * @return void
     */
   protected function executeSelectStatement($queryName) {
        try {
            $this->statements[$queryName]->execute();
            $data = $this->statements[$queryName]->fetchAll(\PDO::FETCH_ASSOC);
            $this->statements[$queryName]->closeCursor();
            return $data;
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage ();
            $this->closeConnection ();
        }
    }
    
    /**
     * just in case there is an uncommited transaction or open database connection.
     */
    function __destruct(){
        $this->closeConnection();
    }
}