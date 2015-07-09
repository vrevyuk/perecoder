<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 08.07.15
 * Time: 15:05
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

$from = isset($_REQUEST['from'])?$_REQUEST['from']:"";
$to = isset($_REQUEST['to'])?$_REQUEST['to']:"";

if(strlen($from) > 5 & strlen($to) > 5) {
    $query = "select * from add_video_url where url like '%$from%'";
    if($result = mysql_query($query)) {
        if(mysql_num_rows($result) == 0) {
            echo "{\"status\":-1}";
        } else {
            while($res = mysql_fetch_assoc($result)) {
                $newquery = "insert into update_video values(0, '".$from."', '".$to."')";
                if(mysql_query($newquery)) {
                    $ok += 1;
                } else {
                    $ok += 0;
                }
            }
            echo "{\"status\":$ok}";
        }
    } else {
        echo "{\"status\":-3}";
    }
} else {
    echo "{\"status\":-4}";
}

mysql_close($con);
?>
