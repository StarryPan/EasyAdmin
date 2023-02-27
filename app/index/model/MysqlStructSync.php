<?php
/**
 * MysqlSync.php
 *
 * @Author  : 9rax.dev@gmail.com
 * @DateTime: 2019/3/3 2:00
 */
namespace app\index\model;

class MysqlStructSync
{
    /**
     * Execute sql status statistics
     *
     * @var array
     */
    private $execute_sql_stat = array();
    private $diff_sql = array();
    private $self_database_struct;
    private $refer_database_struct;
    private $self_connection;
    private $self_db;
    private $refer_connection;
    private $refer_db;
    private $remove_auto_increment = false;
    static $advance = [
        'VIEW' => ["SELECT TABLE_NAME as Name FROM information_schema.VIEWS WHERE TABLE_SCHEMA='#'", 'Create View'],
        'TRIGGER' => ["SELECT TRIGGER_NAME as Name FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='#'", 'SQL Original Statement'],
        'EVENT' => ["SELECT EVENT_NAME  as Name FROM information_schema.EVENTS WHERE EVENT_SCHEMA='#'", 'Create Event'],
        'FUNCTION' => ["SHOW FUNCTION STATUS  WHERE Db='#'", 'Create Function'],
        'PROCEDURE' => ["show PROCEDURE STATUS WHERE Db='#'", 'Create Procedure']
    ];
    /**
     * MysqlStructSync constructor.
     *
     * @param $old_database_config
     * @param $new_database_config
     */
    /*public function __construct($old_database_config, $new_database_config)
    {
        $this->self_connection = new \Mysqli($old_database_config['host'],
            $old_database_config['username'],
            $old_database_config['passwd'],
            $old_database_config['dbname'],
            $old_database_config['port']
        );
        if (isset($_POST['MysqlStructSync']) && $_POST['MysqlStructSync']) {
            $this->diff_sql = $_POST['MysqlStructSync'];
            $this->dump($this->execute());
            die;
        }
        $this->refer_connection = new \Mysqli($new_database_config['host'],
            $new_database_config['username'],
            $new_database_config['passwd'],
            $new_database_config['dbname'],
            $new_database_config['port']
        );
        $this->self_db = $old_database_config['dbname'];
        $this->refer_db = $new_database_config['dbname'];
    }*/


    public function __construct($old_database_config){
        $pass = $old_database_config['passwd'];
        $this->self_connection = new \Mysqli($old_database_config['host'],
            $old_database_config['username'],
            "$pass",
            $old_database_config['dbname'],
            $old_database_config['port']
        );
        $this->self_db = $old_database_config['dbname'];
    }


    public function getStruct(){
        return $this->getStructure($this->self_connection);
    }


    /**
     * remove_auto_increment
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/12 19:56
     */
    function removeAutoIncrement()
    {
        $this->remove_auto_increment = true;
    }
    /**
     * Compare database structure
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/12 19:54
     */
    function baseDiff()
    {
        $this->self_database_struct = $this->getStructure($this->self_connection);
        $this->refer_database_struct = $this->getStructure($this->refer_connection);
        $res['ADD_TABLE'] = array_diff($this->refer_database_struct['tables'], $this->self_database_struct['tables']);
        $res['DROP_TABLE'] = array_diff($this->self_database_struct['tables'], $this->refer_database_struct['tables']);
        $develop_columns = array_intersect_assoc($this->refer_database_struct['columns'], $this->self_database_struct['columns']);
        $self_columns = array_intersect_assoc($this->self_database_struct['columns'], $this->refer_database_struct['columns']);
        if ($develop_columns) {
            foreach ($develop_columns as $table => $columns) {
                foreach ($columns as $field => $sql) {
                    //add
                    if (!isset($self_columns[$table][$field])) {
                        $res['ADD_FIELD'][$table][$field] = $sql;
                        //modify
                    } elseif ($self_columns[$table][$field] !== $sql) {
                        $res['MODIFY_FIELD'][$table][$field] = $sql;
                        unset($self_columns[$table][$field]);
                    } else {
                        unset($self_columns[$table][$field]);
                    }
                }
            }
        }
        $res['DROP_FIELD'] = array_filter($self_columns);
        $res['DROP_CONSTRAINT'] = self::array_diff_assoc_recursive($this->self_database_struct['constraints'], $this->refer_database_struct['constraints'], $res['DROP_TABLE']);
        $res['ADD_CONSTRAINT'] = self::array_diff_assoc_recursive($this->refer_database_struct['constraints'], $this->self_database_struct['constraints'], $res['ADD_TABLE']);
        foreach (array_filter($res) as $type => $data) {
            $this->getExecuteSql($data, $type);
        }
    }

    function baseDiffWithOrg($org_str , $old_database_config ){
        $pass = $old_database_config['passwd'];
        $this->self_connection = new \Mysqli($old_database_config['host'],
            $old_database_config['username'],
            "$pass",
            $old_database_config['dbname'],
            $old_database_config['port']
        );

        $this->self_database_struct = $this->getStructure($this->self_connection);
        $this->refer_database_struct =  $org_str;//json_decode($org_str , true ); //$this->getStructure($this->refer_connection);
        $res['ADD_TABLE'] = array_diff($this->refer_database_struct['tables'], $this->self_database_struct['tables']);
        $res['DROP_TABLE'] = array_diff($this->self_database_struct['tables'], $this->refer_database_struct['tables']);









        $develop_columns = $this->refer_database_struct['columns'];
        $self_columns = $this->self_database_struct['columns'];

        if ($develop_columns) {
            foreach ($develop_columns as $table => $columns) {
                foreach ($columns as $field => $sql) {
                    //add
                    if (!isset($self_columns[$table][$field])) {
                        $res['ADD_FIELD'][$table][$field] = $sql;
                        //modify
                    } elseif ($self_columns[$table][$field] !== $sql) {
                        $res['MODIFY_FIELD'][$table][$field] = $sql;
                        unset($self_columns[$table][$field]);
                    } else {
                        unset($self_columns[$table][$field]);
                    }
                }
            }
        }
        //print_r($develop_columns);
        //echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
        //print_r($self_columns);
        $res['DROP_FIELD'] = array_filter($self_columns);

        /*print_r($this->self_database_struct['constraints']['test_tb'] );
        print_r($this->refer_database_struct['constraints']['test_tb'] ); */
        /**///
        $res['DROP_CONSTRAINT'] = [];//self::array_diff_assoc_recursive($this->self_database_struct['constraints'], $this->refer_database_struct['constraints'], $res['DROP_TABLE']);


        $selfConts = $this->self_database_struct['constraints'];
		//echo json_encode($selfConts);
        $tgtConts = $this->refer_database_struct['constraints'];
		//echo json_encode($tgtConts);
		//exit;

        $add_cnts = [];
        foreach($tgtConts as $tb => $tbkeys ){

            if( empty( $selfConts[$tb] ) ){

            }else{

                $selfTb = $selfConts[$tb];
                $tb_sql = [];
                foreach($tbkeys as $sql){

                    if(in_array($sql ,$selfTb ) || in_array( $sql." USING BTREE" , $selfTb ) || in_array( str_replace(" USING BTREE","",$sql)  , $selfTb ) ){

                        //echo $sql."\n";

                        //$tb_sql[] = $sql;
                    }else{
						$tb_sql[] = $sql;
						
					}

                }

                if(count($tb_sql) > 0){
                    $add_cnts[$tb] = $tb_sql;

                }

            }

        }

        $res['ADD_CONSTRAINT'] = $add_cnts;

        //$res['ADD_CONSTRAINT'] = self::array_diff_assoc_recursive($this->refer_database_struct['constraints'], $this->self_database_struct['constraints'], $res['ADD_TABLE']);
        //print_r($res); 
        foreach (array_filter($res) as $type => $data) {
            $this->getExecuteSql($data, $type);
        }

        //print_r($this->diff_sql);
        //exit;
    }


    /**
     * advanceDiff
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/12 19:54
     */
    function advanceDiff()
    {
        $arr = [];
        $diff = [];
        foreach (self::$advance as $type => $list_sql) {
            foreach (['self', 'refer'] as $who) {
                $conn = $who . '_connection';
                $db = $who . '_db';
                $sql = str_replace('#', $this->$db, $list_sql[0]);
                $connect = $this->$conn->query($sql);
                $res = $connect->fetch_all(MYSQLI_ASSOC);
                foreach ($res as $row) {
                    $show_create_conn = $this->$conn->query('SHOW CREATE ' . $type . ' ' . $row['Name']);
                    //p($show_create_conn->fetch_assoc());
                    $arr[$type][$who][$row['Name']] = preg_replace('/DEFINER=[^\s]*/', '', $show_create_conn->fetch_assoc()[$list_sql[1]]);
                }
                $diff['ADD_' . $type] = self::array_diff_assoc_recursive($arr[$type]['refer'], $arr[$type]['self']);
                $diff['DROP_' . $type] = self::array_diff_assoc_recursive($arr[$type]['self'], $arr[$type]['refer']);
            }
        }
        foreach (array_filter($diff) as $type => $data) {
            $this->getExecuteSql($data, $type);
        }
    }
    /**
     * getResult
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/12 19:54
     */
    function getDiffSql()
    {
        return $this->diff_sql;
    }
    /**
     * getExecuteSql
     *
     * @param $arr
     * @param $type
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/11 22:35
     */
    function getExecuteSql($arr, $type)
    {
        foreach ($arr as $table => $rows) {
            $sql = '';
            if (in_array($type, ['ADD_TABLE', 'DROP_TABLE'])) {
                $sql = $type == 'ADD_TABLE' ? $this->refer_database_struct['show_create'][$rows] : "DROP TABLE IF EXISTS {$rows}";
                $this->diff_sql[$type][] = rtrim($sql, ',');
                continue;
            }
            if (in_array($type, ['ADD_VIEW', 'DROP_VIEW', 'ADD_TRIGGER', 'DROP_TRIGGER', 'ADD_EVENT', 'DROP_EVENT', 'ADD_FUNCTION', 'DROP_FUNCTION', 'ADD_PROCEDURE', 'DROP_PROCEDURE'])) {
                $sql = strpos($type, 'ADD') !== false ? $rows : str_replace('_', '', $rows) . ' ' . $table;
                $this->diff_sql[$type][] = $sql;
                continue;
            }
            foreach ($rows as $key => $val) {
                switch ($type) {
                    case 'MODIFY_FIELD':
                        $sql = "ALTER TABLE `{$table}` MODIFY {$val}";
                        break;
                    case 'DROP_FIELD':
                        $sql = "ALTER TABLE `{$table}` DROP {$key}";
                        break;
                    case 'ADD_FIELD':
                        $sql = "ALTER TABLE `{$table}` ADD {$val}";
                        break;
                    case 'ADD_CONSTRAINT':
                        //$sql = self::getConstraintQuery($val, $table)['add'];

                        if(strpos($val, "UNIQUE KEY") === 0){
                            $sql = "ALTER TABLE `{$table}` ADD {$val}";

                        }else if(strpos($val, "KEY") === 0){
                            $tmp = "INDEX ".substr($val,3);
                            $sql = "ALTER TABLE `{$table}` ADD ".$tmp;
                        }else if(strpos($val , "CONSTRAINT") === 0){

                            $sql = "ALTER TABLE `{$table}` ADD ".$val;
                        }else if(strpos($val , "PRIMARY KEY") === 0){

                            $sql = "ALTER TABLE `{$table}` ADD ".$val;
                        }



                        break;
                    case 'DROP_CONSTRAINT':
                        $sql = self::getConstraintQuery($val, $table)['drop'];
                        break;
                }
                $this->diff_sql[$type][] = rtrim($sql, ',');
            }
        }
    }
    /**
     * @var array $patterns
     *  Here is the reference  "https://github.com/ibraheem-ghazi/dbdiff/blob/master/src/SqlDumper.class.php"
     */
    static $patterns = [
        'primary' => '(^[^`]\s*PRIMARY KEY .*[,]?$)',
        'key' => '(^[^`]\s*KEY\s+(`.*`) .*[,]?$)',
        'constraint' => '(^[^`]\s*CONSTRAINT\s+(`.*`) .*[,]?$)',
        'unique' => '(^[^`]\s*UNIQUE KEY\s+(`.*`) .*[,]?$)',
    ];
    /**
     * getConstraintQuery
     * Here is the reference  "https://github.com/ibraheem-ghazi/dbdiff/blob/master/src/SqlDumper.class.php"
     * @param $constraint
     * @param $table
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/11 22:35
     */
    static function getConstraintQuery($constraint, $table)
    {
        foreach (static::$patterns as $key => $pattern) {
            if (preg_match("/" . str_replace('^[^`]', '', $pattern) . "$/m", $constraint, $matches)) {
                switch ($key) {
                    case 'primary':
                        return ['drop' => 'ALTER TABLE `' . $table . '` DROP PRIMARY KEY;', 'add' => 'ALTER TABLE `' . $table . '` ADD ' . rtrim($constraint, ',')];
                    case 'unique':
                        return ['drop' => "ALTER TABLE `{$table}` DROP KEY $matches[2];", 'add' => 'ALTER TABLE `' . $table . '` ADD ' . rtrim($constraint, ',')];
                    case 'key':
                        $tmp_arr = rtrim($constraint, ',');
                        $drop = "ALTER TABLE `{$table}` DROP KEY $matches[2];";
                        $tmp_arr = explode(" ",$tmp_arr);
                        $tmp_arr[0] = "INDEX";
                        $tmp = "";
                        foreach($tmp_arr as $k => $v){
                            $tmp = $tmp.$v;
                            if($k < count($tmp_arr) - 1){
                                $tmp = $tmp." ";
                            }
                        }
                        $add = 'ALTER TABLE `' . $table . '` ADD  ' . $tmp;
                        //$add = "ALTER TABLE actor ADD INDEX idx_actor_first_name (first_name); ";
                        return ['drop' => $drop, 'add' => $add];
                    case 'constraint':

                        return [
                            'drop' => "ALTER TABLE `{$table}` DROP CONSTRAINT $matches[2];",
                            'add' => 'ALTER TABLE `' . $table . '` ADD   ' . rtrim($constraint, ',')];
                }
                break;
            }
        }
    }
    /**
     * array_diff_assoc_recursive
     *
     * @param       $array1
     * @param       $array2
     * @param array $exclude
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/11 22:35
     */
    static function array_diff_assoc_recursive($array1, $array2, $exclude = [])
    {
        $ret = array();
        foreach ($array1 as $k => $v) {
            if ($exclude && in_array($k, $exclude)) {
                continue;
            }
            if (!isset($array2[$k])) $ret[$k] = $v;
            else if (is_array($v) && is_array($array2[$k])) $ret[$k] = self::array_diff_assoc_recursive($v, $array2[$k]);
            else if ($v != $array2[$k]) $ret[$k] = $v;
            else {
                unset($array1[$k]);
            }
        }
        return array_filter($ret);
    }
    /**
     * Manually call the execute sql   for manuallySelectUpdates
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/4 11:12
     */
    function execute()
    {
        $this->execute_sql_stat = array();
        $diff_sqls = array_filter($this->diff_sql);
        if ($diff_sqls) {
            $add_tables=isset($diff_sqls['ADD_TABLE'])?$diff_sqls['ADD_TABLE']:null;
            if($add_tables){
                unset($diff_sqls['ADD_TABLE']);
                $this->executeAddTables($add_tables);
            }
            foreach ($diff_sqls as $type => $sqls) {
                foreach ($sqls as $sql) {
                    /*if ($this->self_connection->query($sql)) {
                        $this->execute_sql_stat['success'][$type][] = $sql;
                    } else {
                        $this->execute_sql_stat['error'][$type][] = $sql;
                    }*/
                    if(strpos($type, "DROP") !== 0){
                        //echo $type.":".$sql."\n";

                        if ($this->self_connection->query($sql)) {
                            $this->execute_sql_stat['success'][$type][] = $sql;
                        } else {
                            $this->execute_sql_stat['error'][$type][] = $sql;
                        }
                    }


                }
            }
        }
        return $this->execute_sql_stat;
    }

    function exportExecuteSql()
    {
        $arr = [];
        $this->execute_sql_stat = array();
        $diff_sqls = array_filter($this->diff_sql);
        if ($diff_sqls) {
            $add_tables=isset($diff_sqls['ADD_TABLE'])?$diff_sqls['ADD_TABLE']:null;
            if($add_tables){
                unset($diff_sqls['ADD_TABLE']);
                $this->executeAddTables($add_tables);
            }
            foreach ($diff_sqls as $type => $sqls) {
                foreach ($sqls as $sql) {
                    /*if ($this->self_connection->query($sql)) {
                        $this->execute_sql_stat['success'][$type][] = $sql;
                    } else {
                        $this->execute_sql_stat['error'][$type][] = $sql;
                    }*/
                    if(strpos($type, "DROP") !== 0){
                        //echo $type.":".$sql."\n";

                        $arr[] = $sql;
                    }


                }
            }
        }
        return $arr;
    }
    /**
     * executeAddTables
     * @param $add_tables_sqls
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/12 23:38
     */
    private function executeAddTables($add_tables_sqls){
        $this->self_connection->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($add_tables_sqls as $key=>$sql){
            if ($this->self_connection->query($sql)) {
                $this->execute_sql_stat['success']['ADD_TABLE'][] = $sql;
                unset($add_tables_sqls[$key]);
            }else{
                $this->execute_sql_stat['error']['ADD_TABLE'][] = $sql;
            }
        }
        $this->self_connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
    /**
     * Manually select the updated sql
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/4 11:14
     */
    function manuallySelectUpdates()
    {
        if ($this->diff_sql) {
            $form = '<form action="" method="post" style="font-size: 12px;padding: 0 50px;">';
            foreach ($this->diff_sql as $type => $sqls) {
                $form .= '<h2>' . $type . '</h2>';
                foreach ($sqls as $sql) {
                    $form .= '<div style="height: auto;overflow-y: hidden;"><input type="checkbox" name="MysqlStructSync[' . $type . '][]" value="' . $sql . '" style="float: left;position: absolute;margin-left: -15px;margin-top: 20px;"><pre style="float: left;"><code class="language-sql line-numbers">' . $sql . '</code></pre></div>';
                }
            }
            $form .= '<input type="submit"></form>';
            $form .= '<link rel="stylesheet" href="https://cdn.staticfile.org/prism/9000.0.1/themes/prism.min.css"><script src="https://cdn.staticfile.org/prism/9000.0.1/prism.min.js"></script><script src="https://cdn.staticfile.org/prism/9000.0.1/components/prism-sql.min.js"></script>';
            die($form);
        }
    }
    /**
     * getStructure
     *
     * @param $resource
     *
     * @return array
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/11 13:06
     */
    private function getStructure($resource)
    {
        $resource->query("SET NAMES UTF8");
        $stmt1 = $resource->query("SHOW TABLE STATUS WHERE Comment!='VIEW'");
        $alert_columns = [];
        $constraints = [];
        $show_create = [];
        $tables = [];
        $pattern = '/' . implode('|', self::$patterns) . '/m';
        foreach ($stmt1->fetch_all(MYSQLI_ASSOC) as $row) {
            //��ȡ�������
            $alert_columns_conn = $resource->query('SHOW CREATE TABLE ' . $row['Name']);
            $sql = $alert_columns_conn->fetch_assoc();
            preg_match_all('/^\s+[`]([^`]*)`.*?$/m', $sql['Create Table'], $key_value);
            $alert_columns[$row['Name']] = array_combine($key_value[1], array_map(function ($item) {
                return trim(rtrim($item, ','));
            }, $key_value[0]));
            //��ȡ��������
            preg_match_all($pattern, $sql['Create Table'], $matches);
            $consrt = array_map(function ($item) {
                return trim(rtrim($item, ','));
            }, $matches[0]);
            $constraints[$row['Name']] = $consrt;
            $show_create[$row['Name']] = $this->remove_auto_increment?preg_replace('/AUTO_INCREMENT=[^\s]*/','',$sql['Create Table']):$sql['Create Table'];
            $tables[] = $row['Name'];
        }
        ksort($alert_columns);
        ksort($constraints);
        ksort($show_create);
        ksort($tables);
        return ['tables' => $tables, 'columns' => $alert_columns, 'show_create' => $show_create, 'constraints' => $constraints];
    }
    /**
     * dump
     *
     * @param $arr
     *
     * @Author  : 9rax.dev@gmail.com
     * @DateTime: 2019/6/10 12:12
     */
    private function dump($arr)
    {
        echo '<pre>' . print_r($arr, true) . '</pre>';
    }
}