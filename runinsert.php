<?php

include 'BenchmarkInsert.php';

$benchmark = new BenchmarkInsert('127.0.0.1', 'my_database', 'my_user', 'my_password');
$benchmark->tableName = 'my_table';
$benchmark->insertData = [
    'col_1' => 'val_1',
    'col_2' => 'val_2',
    'col_3' => 'val_3',
];

echo "\n";
$benchmark->runTransactionInsert();
$benchmark->runTransactionWithStatementInsert();
$benchmark->runPreparedStatementInsert();
$benchmark->runSimpleInsert();

