<?php
/*
 * ================================================
 *  BY DANIEL V. MORALES
 * ------------------------------------------------
 * medennysoft@outlook.com
 * SQLML:
 *       https://github.com/MeDennySoft/sqlml
 * ------------------------------------------------
 *      10/02/2014
 * ================================================
 */
namespace sqllayer;

class Connection {

    private $host;
    private $user;
    private $password;
    private $dataBase;
    private $dbms = "mysql";
    private $connection;
    private $language = "DML";

    /**
     * ConstructorClass
     *
     * Initialize the members when a new object is created
     *
     * @param expects an array with the information to connect using PDO
     *        array['host']
     *        array['user']
     *        array['password']
     *        array['database']
     *        array['dbms'] 
     *        array['lang'] = DDL | DML
     */
    public function __construct(array $tCfg) {
        if (!is_array($tCfg)) {
            return false;
        }

        if (key_exists("dbms", $tCfg)) {
            $this->dbms = $tCfg['dbms'];
        }

        if (!key_exists("database", $tCfg)) {
            $this->language = "DDL";
        } else {
            $this->dataBase = $tCfg['database'];
        }

        try {
            $this->host = $tCfg['host'];
            $this->user = $tCfg['user'];
            $this->password = $tCfg['password'];
            unset($tCfg);
            $this->createConnection();
        } catch (PDOException $e) {
            echo $e->getMessage();
            die();
        }
    }

    private function createConnection() {
        $connectionString = $this->makeConString();
        try {
            if ($this->dbms == "sqlite") {
                $this->connection = new \PDO("sqlite:$this->host");
            } else {
                $this->connection = new \PDO("$connectionString", "$this->user", "$this->password");
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
            die();
        }
    }

    private function makeConString() {
        $str = "$this->dbms:host=$this->host";
        if ($this->language === "DML") {
            $str .= ";dbname=$this->dataBase";
        }
        return $str;
    }

    public function getInstance() {
        return $this->connection;
    }
    
    public function __destruct() {
        $this->connection = null;
    }
}
?>
