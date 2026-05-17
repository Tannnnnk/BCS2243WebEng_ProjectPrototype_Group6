<?php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); 
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webeproject');


$server_host = (DB_PORT === '3306' || empty(DB_PORT)) ? DB_HOST : DB_HOST . ':' . DB_PORT;


$link = mysqli_connect($server_host, DB_USER, DB_PASS, DB_NAME);


if (!$link) {
    die('Database Connection Error (' . mysqli_connect_errno() . '): ' . mysqli_connect_error());
}


mysqli_set_charset($link, "utf8mb4");
?>