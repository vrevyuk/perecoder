<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 07.07.15
 * Time: 14:41
 */

$REGISTERED_MEDIA_EXTENSION = "mp4|avi|mkv|ts";
$FROM_DIR = $argv[1];
if(preg_match('|(.+)/$|i', $FROM_DIR, $match)) { $FROM_DIR = $match[1]; }
$FFPROBE = '/usr/local/bin/ffprobe';
$count =0;
$ipaddr = "";


echo $FROM_DIR . "\n";

$mysql_host = 'watcher.vivat-tv.com';
$mysq_user = 'terminals';
$mysql_passwd = 'hT9kqBdZ';
$mysql_db = 'terminals';
if(!$con = mysql_connect($mysql_host, $mysq_user, $mysql_passwd, false)) {
    die("MySQL not connected.\n");
};

mysql_select_db($mysql_db);
mysql_query("set names utf8");

function checkDB($file) {
    global $con, $ipaddr;
    $r = json_decode(file_get_contents("http://proxy.vivat-tv.com/findFilm.php?filename=" . urlencode($file)));
    if(isset($r)) {
        if($r->status == 0) {
            echo "DB - ok\n";
            return true;
        } else if($r->status == 1) {
            saveNoDbFiles($file, "Not found: ");
            return false;
        } else {
            saveNoDbFiles($file, "Too many records: ");
            return false;
        }
    } else {
        saveNoDbFiles($file, "Select failed: ");
        return false;
    }
}

function saveNoDbFiles($file, $rem) {
    try {
        $f = fopen('nodb.txt', 'a');
        fwrite($f, $rem . $file . "\r\n");
        fclose($f);
    } catch (Exception $e) { }
}

function viewInfo($file) {
    global $FFPROBE, $FROM_DIR;
    $vcodec = null;
    $acodec = null;
    $cmd = $FFPROBE . " -v quiet -print_format json -show_format -show_streams -i " . escapeshellarg($file) . " 2>&1";
    exec($cmd, $out, $err);
    echo $cmd . "\n";
    if($err == 0) {
        $str = "";
        for($i=0; $i<sizeof($out); $i++) {
            $str .= $out[$i];
            //echo "\t" . $out[$i] . "\n";
        }
        $data = json_decode($str);
        $sfile = str_replace($FROM_DIR, '', $data->{'format'}->{'filename'});
        echo "filename: " . $sfile . "\n";
        //$num_codecs = $data->{'format'}->{'nb_streams'};
        checkDB($sfile);
        foreach($data->{'streams'} as $stream) {
            if($stream->{'codec_type'} == 'audio') { $acodec = isset($stream->{'codec_name'})?$stream->{'codec_name'}:null; }
            if($stream->{'codec_type'} == 'video') { $vcodec = isset($stream->{'codec_name'})?$stream->{'codec_name'}:null; }
            echo "codec " . $stream->{'codec_type'} . ", " . (isset($stream->{'codec_name'})?$stream->{'codec_name'}:"") . "\n";
        }
        echo "\n";
        return isset($acodec) & isset($vcodec);
    } else { echo "Error executing " . $FFPROBE . "\n"; return false; }
}

function scanner($path) {
    global $REGISTERED_MEDIA_EXTENSION, $FROM_DIR, $TO_DIR;

    $handle = opendir($path);
    while($f = readdir($handle)) {
        if (filetype($path . $f) == 'dir') {
            if($f != '.' && $f != '..') {
                scanner($path . $f . "/");
            }
        } elseif (filetype($path . $f) == 'file') {
            if(preg_match("/\\.(".$REGISTERED_MEDIA_EXTENSION.")$/i", $f)) viewInfo($path . $f);
        } else {
            echo "\t\tUnknown file\n";
        }
    }
    closedir($handle);
}


if(!file_exists($FROM_DIR)) { die("Directory not found\n\n"); }
scanner($FROM_DIR . "/");
echo "All success $count films.\n";

//mysql_close($con);
?>