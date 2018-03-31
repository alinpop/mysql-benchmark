<?php

class BenchmarkSelect
{
	public $tableName;
	public $idName;
	
	protected $pdo;
	protected $idsList = [];
	protected $outputMessage = "";
	
    public function __construct($dbHost, $dbName, $dbUser, $dbPassword)
    {
        $dsn = "mysql:dbname={$dbName};host={$dbHost}";
        $this->pdo = new PDO($dsn, $dbUser, $dbPassword);

        var_dump($this->pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES));exit;

		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
	
	public function getTableIds($idName = 'id')
	{
		if ($idName) {
			$this->idName = $idName;
		}
		
		if (!$this->idsList) {
			$sql = "SELECT {$idName} FROM {$this->tableName}";
			$this->idsList = $this->pdo->query($sql)->fetchAll();
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
		$this->outputMessage = "SIMPLE SELECT";
		
		$callback = function ($row) {
			$sql = "SELECT * FROM {$this->tableName} WHERE {$this->idName} = $row[0]";
			$statement = $this->pdo->query($sql);
			$result = $statement->fetch();
		};
		
		$this->timing($callback);
	}
	
	public function runPreparedStatementSelect()
	{
		$this->outputMessage = "PREPARED STATEMENT SELECT";
		
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
			$sql = "SELECT * FROM {$this->tableName}";
			$statement = $this->pdo->query($sql);
			$result = $statement->fetchAll();
		};
		
		$this->timing($callback);
		
		$this->idsList = null;
	}
}