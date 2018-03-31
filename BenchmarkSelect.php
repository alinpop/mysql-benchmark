<?php

class BenchmarkSelect
{
    public $tableName;
    public $idName;
    public $limit;

    protected $pdo;
    protected $idsList = [];
    protected $outputMessage = "";

    public function __construct($dbHost, $dbName, $dbUser, $dbPassword)
    {
        $dsn = "mysql:dbname={$dbName};host={$dbHost}";
        $this->pdo = new PDO($dsn, $dbUser, $dbPassword);

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function getTableIds($idName = 'id')
    {
        if (!$this->idName && $idName) {
            $this->idName = $idName;
        }

        if (!$this->idsList) {
            $limit = "";
            if ($this->limit) {
                $limit = "LIMIT {$this->limit}";
            }

            $sql = "SELECT {$this->idName} FROM {$this->tableName} {$limit}";

            $statement = $this->pdo->prepare($sql);

            $statement->execute();

            $this->idsList = $statement;
        }

        return $this->idsList;
    }

    protected function timing($callback)
    {
        $this->getTableIds();

        $time_start = microtime(true);

        $i = 0;
        foreach ($this->getTableIds() as $row) {
            $callback($row);
            $i++;
        }

        $time_end = microtime(true);
        $time = number_format($time_end - $time_start, 2);
        echo "{$this->outputMessage} [{$i}] in {$time} seconds\n";
    }

    public function runSimpleSelect()
    {
        $this->idsList = null;
        $this->outputMessage = "SIMPLE LOOP SELECT";

        $callback = function ($row) {
            $value = is_numeric($row[0]) ? $row[0] : "'{$row[0]}'";
            $sql = "SELECT * FROM {$this->tableName} WHERE {$this->idName} = {$value}";
            $statement = $this->pdo->query($sql);
            $result = $statement->fetch();
        };

        $this->timing($callback);
    }

    public function runPreparedStatementSelect()
    {
        $this->idsList = null;
        $this->outputMessage = "PREPARED STATEMENT LOOP SELECT";

        $sql = "SELECT * FROM {$this->tableName} WHERE {$this->idName} = :idName";
        $statement = $this->pdo->prepare($sql);

        $callback = function ($row) use (&$statement) {
            $statement->execute(['idName' => $row[0]]);
            $result = $statement->fetch();
        };

        $this->timing($callback);
    }

    public function runNormalSelect()
    {
        $this->outputMessage = "ONE SELECT";

        $this->idsList = ['one_time_query'];

        $callback = function ($row) {
            $limit = "";
            if ($this->limit) {
                $limit = "LIMIT {$this->limit}";
            }
            $sql = "SELECT * FROM {$this->tableName} {$limit}";
            $statement = $this->pdo->query($sql);
            $result = $statement->fetchAll();
        };

        $this->timing($callback);

        $this->idsList = null;
    }
}