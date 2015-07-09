<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 09.07.15
 * Time: 11:05
 */

$mysql_host = 'localhost';
$mysq_user = 'vivattv';
$mysql_passwd = 'bpeDtacUnsAcKAKN';
$mysql_db = 'vivattv';

if(!$con = mysql_connect($mysql_host, $mysq_user, $mysql_passwd, false)) {
    die("MySQL not connected.\n");
};
mysql_select_db($mysql_db);
mysql_query("set names utf8");

$filename = $_REQUEST['filename'];

if(isset($filename)) {
    $query = "select * from add_video_url where url like '%$filename%'";
    if($result = mysql_query($query)) {
        if(mysql_num_rows($result) == 0) {
            echo "{\"status\":1}";
        } else if(mysql_num_rows($result) == 1) {
            echo "{\"status\":0}";
        } else {
            echo "{\"status\":2}";
        }
    } else {
        echo "{\"status\":3}";
    }
} else {
    echo "{\"status\":4}";
}

mysql_close($con);

?>
