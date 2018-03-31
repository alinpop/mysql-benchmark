<?php

include 'BenchmarkSelect.php';

$benchmarkSelect = new BenchmarkSelect('127.0.0.1', 'my_database', 'my_user', 'my_password');

$benchmarkSelect->tableName = 'my_table';

$benchmarkSelect->idName = 'id';

$benchmarkSelect->runNormalSelect();
$benchmarkSelect->runPreparedStatementSelect();
$benchmarkSelect->runSimpleSelect();
