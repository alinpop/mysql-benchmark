<?php

class BenchmarkInsert
{
    public $tableName;
    public $insertData;

    protected $pdo;
    protected $outputMessage = "";
    protected $truncateTable = true;

    public function __construct($dbHost, $dbName, $dbUser, $dbPassword)
    {
        $dsn = "mysql:dbname={$dbName};host={$dbHost}";
        $this->pdo = new PDO($dsn, $dbUser, $dbPassword);

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    protected function insertQueryBuilder($table, $rows)
    {
        $columns = implode(",", array_keys($rows[0]));

        $insertValues = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                switch(gettype($value)) {
                    case 'boolean': $values[] = (int) $value; break;
                    case 'string': $values[] = "'{$value}'"; break;
                    case 'NULL': $values[] = "NULL"; break;
                    default: $values[] = $value;
                }
            }
            $insertValues[] = "\n(" . implode(",", $values) . ")";
        }

        $query = "INSERT INTO {$table} ";
        $query .= "\n($columns) ";
        $query .= "\nVALUES ".implode(",", $insertValues);

        return $query;
    }

    protected function insertPreparedStatementQueryBuilder($table, $row)
    {
        $columns = implode(",", array_keys($row));
        $placeholders = implode(", :", array_keys($row));

        return "INSERT INTO {$table} ({$columns}) \nVALUES (:{$placeholders})";
    }

    public function looping($max = 1000, $callbackBeforeLoop, $callbackInsideLoop, $callbackAfterLoop)
    {
        if ($this->truncateTable) {
            $this->pdo->exec("TRUNCATE TABLE {$this->tableName}");
        }

        $time_start = microtime(true);

        $callbackBeforeLoop();

        $i = 0;
        while ($i < $max) {
            $callbackInsideLoop();
            $i++;
        }

        $callbackAfterLoop();

        $time_end = microtime(true);
        $time = number_format($time_end - $time_start, 2);
        echo "{$this->outputMessage} in {$time} seconds\n";
    }

    public function runSimpleInsert($max = 1000)
    {
        $this->outputMessage = "SIMPLE INSERT";
        $sql = $this->insertQueryBuilder($this->tableName, [$this->insertData]);
        $callbackBeforeLoop = function(){};

        $callbackInsideLoop = function() use ($sql){
            $this->pdo->exec($sql);
        };

        $callbackAfterLoop = function(){};

        $this->looping($max, $callbackBeforeLoop, $callbackInsideLoop, $callbackAfterLoop);
    }

    public function runPreparedStatementInsert($max = 1000)
    {
        $this->outputMessage = "PREPARED STATEMENT INSERT";

        $pdoStatement = null;

        $callbackBeforeLoop = function() use (&$pdoStatement){
            $sql = $this->insertPreparedStatementQueryBuilder($this->tableName, $this->insertData);
            $pdoStatement = $this->pdo->prepare($sql);
        };

        $callbackInsideLoop = function() use (&$pdoStatement){
            $pdoStatement->execute($this->insertData);
        };

        $callbackAfterLoop = function(){};

        $this->looping($max, $callbackBeforeLoop, $callbackInsideLoop, $callbackAfterLoop);
    }

    public function runTransactionWithStatementInsert($max = 1000)
    {
        $this->outputMessage = "TRANSACTION & PREPARED STATEMENT INSERT";

        $pdoStatement = null;

        $callbackBeforeLoop = function() use (&$pdoStatement){
            $this->pdo->beginTransaction();
            $sql = $this->insertPreparedStatementQueryBuilder($this->tableName, $this->insertData);
            $pdoStatement = $this->pdo->prepare($sql);
        };

        $callbackInsideLoop = function() use (&$pdoStatement){
            $pdoStatement->execute($this->insertData);
        };

        $callbackAfterLoop = function(){
            $this->pdo->commit();
        };

        $this->looping($max, $callbackBeforeLoop, $callbackInsideLoop, $callbackAfterLoop);
    }

    public function runTransactionInsert($max = 1000)
    {
        $this->outputMessage = "TRANSACTION INSERT";

        $sql = $this->insertQueryBuilder($this->tableName, [$this->insertData]);

        $callbackBeforeLoop = function(){
            $this->pdo->beginTransaction();
        };

        $callbackInsideLoop = function() use ($sql){
            $this->pdo->exec($sql);
        };

        $callbackAfterLoop = function(){
            $this->pdo->commit();
        };

        $this->looping($max, $callbackBeforeLoop, $callbackInsideLoop, $callbackAfterLoop);
    }
}
