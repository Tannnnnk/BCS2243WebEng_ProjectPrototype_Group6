<?php
require_once 'db_connection.php';

if(isset($_GET['userID'])){

    $id = mysqli_real_escape_string($link,$_GET['userID']);

    $res = mysqli_query($link,
        "SELECT admin_name
         FROM administrator
         WHERE userID='$id'"
    );

    if($row=mysqli_fetch_assoc($res)){
        echo $row['admin_name'];
    }
    else{
        echo "Advisor not found";
    }
}
?>