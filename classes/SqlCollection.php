<?php

namespace sqllayer;

class SqlCollection {

    private $handler;
    private $tConfig;
    private $sqlTable;
    private $placeHolders;
    private $columns;
    private $arrayExecutor = Null;
    private $arrayCond = array();

    public function __construct(array $config = null) {
        $this->setStr($config);
        $this->init();
    }

    public function setStr(array $config) {
        $this->handler = new \sqllayer\Connection($config);
        $this->tConfig = $config;
        $this->handler = $this->handler->getInstance();
    }

    private function init() {
        $this->sqlTable['get_all_regs'] = "SELECT <%col%> FROM <%table%> <%cond%>";
        $this->sqlTable['set_reg'] = "INSERT INTO <%table%>(<%col%>) VALUES (<%vals%>) <%cond%>";
        $this->sqlTable['update_reg'] = "UPDATE <%table%> SET <%col%> <%cond%>";
        $this->sqlTable['delete_reg'] = "DELETE <%col%> FROM <%table%> <%cond%>";
    }
    
    // DML
    private function queryExecutor($stat, $arg) {
        #$this->handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$arg['table']) {
            if (key_exists("table_default", $this->tConfig)) {
                $arg['table'] = $this->tConfig['table_default'];
            } else {
                return false;
            }
        }

        if ($arg['cond']) {
            $arg['cond'] = "WHERE " . $arg['cond'];
        }

        // getting the sql statement
        $statement = $this->sqlTable["$stat"];

        // doing a well formed statement
        foreach ($arg as $clave => $valor) {
            $statement = str_replace('<%' . $clave . '%>', $valor, $statement);
        }

        // "Statement Handle"
        if (!($STH = $this->handler->prepare("$statement"))) {
            return False;
        }

        // placeholders well formed
        if (is_array($this->arrayExecutor)) {
            $this->arrayExecutor = array_merge($this->arrayExecutor, $this->arrayCond);
        } else {
            $this->arrayExecutor = $this->arrayCond;
        }

        if (!($STH->execute($this->arrayExecutor))) {
            return False;
        }

        return $STH;
    }

    private function prepareUpdate() {
        if (substr_count($this->columns, ",") == 0) {
            $this->columns = "$this->columns = $this->placeHolders";
            return true;
        }

        $columns = explode(",", $this->columns);
        $this->columns = "";

        foreach ($columns as $value) {
            $this->columns .= " $value = :$value,";
        }

        $this->columns = rtrim($this->columns, ",");
        return true;
    }

    //
    public function set_col($cols, $vals) {
        // strip some spaces
        $cols = str_replace(" ", "", $cols);
        $vals = str_replace(" ", "", $vals);

        // creating the start up array
        $columns = explode(",", $cols);
        $colsVals = explode(",", $vals);

        if (substr_count($cols, ",") == 0) {
            unset($this->arrayExecutor);
            $this->placeHolders = ":$cols";
            $this->columns = "$cols";
            $this->arrayExecutor[":$cols"] = "$vals";
            return true;
        }

        // adding : to the cols
        $stringFormed = "";
        $stringCols = "";
        foreach ($columns as $value) {
            $value = trim($value);
            $stringCols .= "$value,";
            $stringFormed .= ":$value,";
        }

        // reconstructing the string
        $this->placeHolders = rtrim($stringFormed, ",");
        $this->columns = rtrim($stringCols, ",");

        // making the array for executing the pdo query 
        $columns = explode(",", $this->placeHolders);
        $i = 0;
        foreach ($columns as $col) {
            $col = trim($col);
            $this->arrayExecutor["$col"] = "$colsVals[$i]";
            $i += 1;
        }
    }

    public function set_cond($arr, $var = false, $val = false) {
        if (!$arr) {
            $this->arrayCond[":$var"] = "$val";
        } else {
            $this->arrayCond = $var;
        }
    }

    //
    public function get_reg($col, $cond = false, $table = false) {
        $arg = array('col' => $col, 'cond' => $cond, 'table' => $table);
        $type = "get_all_regs";
        $STH = $this->queryExecutor($type, $arg);
        unset($arg);
        return $STH;
    }

    public function set_reg($table = false) {
        $arg = array('col' => $this->columns, 'vals' => $this->placeHolders, 'cond' => false, 'table' => $table);
        $type = "set_reg";
        $r = $this->queryExecutor($type, $arg);
        unset($arg);
        return $r;
    }

    public function update_reg($cond = false, $table = false) {
        $this->prepareUpdate();
        $arg = array('col' => $this->columns, 'cond' => $cond, 'table' => $table);
        $type = "update_reg";
        $r = $this->queryExecutor($type, $arg);
        unset($arg);
        return $r;
    }

    public function delete_reg($col = Null, $cond = null, $table = false) {
        $arg = array('col' => $col, 'cond' => $cond, 'table' => $table);
        $type = "delete_reg";
        $r = $this->queryExecutor($type, $arg);
        unset($arg);
        return $r;
    }
    
    
    public function shutdown_Connection() {
        $this->handler = Null;
    }

    public function get_handler() {
        return $this->handler;
    }

}

?>
