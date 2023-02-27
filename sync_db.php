<?php
$output = shell_exec("php .\public\index.php Api/getDBStruct");
file_put_contents("./dbsql/last_bgdb_struct.txt", $output);