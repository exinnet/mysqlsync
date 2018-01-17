<?php

class MysqlSync
{
    public $conn = [];
    public $total = 0;

    public function __construct(array $conf)
    {
        $dbms = 'mysql';

        $master = $conf['master'];
        $slave = $conf['slave'];

        $dsn = sprintf("mysql:host=%s;dbname=%s", $master['host'], $master['db']);
        $dbh_conn_master = new PDO($dsn, $master['user'], $master['pwd']);

        $dsn = sprintf("mysql:host=%s;dbname=%s", $slave['host'], $slave['db']);
        $dbh_conn_slave = new PDO($dsn, $slave['user'], $slave['pwd']);

        $this->conn['master'] = $dbh_conn_master;
        $this->conn['slave'] = $dbh_conn_slave;
        $this->sync = $conf["sync"];
    }

    public function run()
    {
        $last_id = $this->sync["start_id"];
        $sql_pattern = $this->sync["sql"] . " limit 1";
        $pattern = "/from(.*?) where/i";
        preg_match($pattern, $sql_pattern, $match);
        if (!isset($match[1])) {
            die("can not get table name from sql");
        }
        $table = $match[1];
        $total = 0;
        while (true) {
            $sql = str_replace("#start_id#", $last_id, $sql_pattern);
            $stmt = $this->conn['master']->query($sql);
            $count = 0;
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fields = array_keys($row);
                $fields_str = implode("`,`", $fields);
                $values = array_values($row);
                $values_str = implode("','", array_map("addslashes", $values));
                $insert_sql = "INSERT INTO $table (`$fields_str`) value('$values_str')"; 
                $this->conn["slave"]->exec($insert_sql);
                $count++;
                $last_id = $row[$this->sync["primary_key"]];
            }
            if ($count == 0) {
                break; 
            }
            $this->total += $count;
        }
         
    }


}

$conf = require dirname(__FILE__) . '/config.php';

$md = new MysqlSync($conf);
$md->run();
echo "导入". $md->total ."条数据\n";
