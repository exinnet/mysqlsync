<?php
return [
	"master" => [
		"host" => "127.0.0.1",
		"port" => "3306",
		"user" => "root",
		"pwd" => "root",
		"db" => "test",
	],
	"slave" => [
		"host" => "127.0.0.1",
		"port" => "3306",
		"user" => "root",
		"pwd" => "root",
		"db" => "test2",
	],
    "sync" => [
        "sql" => "select * FROM repair_table where id > #start_id#",
        "start_id" => 1,
        "primary_key" => "id",
    ],
];
