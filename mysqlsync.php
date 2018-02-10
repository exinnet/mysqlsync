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

        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $master['host'], $master["port"], $master['db']);
        $dbh_conn_master = new PDO($dsn, $master['user'], $master['pwd']);

        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $slave['host'], $slave["port"], $slave['db']);
        $dbh_conn_slave = new PDO($dsn, $slave['user'], $slave['pwd']);

        $this->conn['master'] = $dbh_conn_master;
        $this->conn['slave'] = $dbh_conn_slave;
        $this->sync = $conf["sync"];
    }

    private function create_values($values) {
        $result = [];
        foreach ($values as $value) {
            if (is_null($value)) {
                $result[] = "NULL"; 
            } else {
                $result[] = "'". addslashes($value) ."'";
            }
        }
        return implode(",", $result); 
    }
    public function run()
    {
        $last_id = $this->sync["start_id"];
        $end_id = $this->sync["end_id"];
        $limit = isset($this->sync["page_size"]) ? $this->sync["page_size"] : 1000;
        $sql_pattern = $this->sync["sql"] . " limit ". $limit;
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
                
                $values_str = $this->create_values($values);
                $insert_sql = "INSERT INTO $table (`$fields_str`) value($values_str)"; 
                echo $insert_sql."\n";
                $this->conn["slave"]->exec($insert_sql);
                $count++;
                $last_id = $row[$this->sync["primary_key"]];
            }
            $this->total += $count;
            echo "num:". $this->total ."\n";
            if ($count == 0) {
                break; 
            }
            if ($end_id != 0 && $end_id <= $last_id) {
                break;
            }
        }
         
    }


}

$conf = require dirname(__FILE__) . '/config.php';

$md = new MysqlSync($conf);
$md->run();
echo "导入". $md->total ."条数据\n";
