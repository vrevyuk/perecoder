<?php
/**
 * Created by PhpStorm.
 * User: Vitaly Revyuk
 * Date: 06.07.15
 * Time: 08:50
 */
$REGISTERED_MEDIA_EXTENSION = "mp4|avi|mkv|ts";
$FROM_DIR = $argv[1];
if(preg_match('|(.+)/$|i', $FROM_DIR, $match)) { $FROM_DIR = $match[1]; }
$FFMPEG = '/usr/local/bin/ffmpeg';
$FFPROBE = '/usr/local/bin/ffprobe';
$count =0;

echo $FROM_DIR . "\n";

function checkDB($file) {
    global $con, $ipaddr;
    $r = json_decode(file_get_contents("http://proxy.vivat-tv.com/findFilm.php?filename=" . urlencode($file)));
    //echo "http://proxy.vivat-tv.com/findFilm.php?filename=" . urlencode($file) . "\n";
    if(isset($r)) {
        if($r->status == 0) {
            echo "DB - ok\n";
            return true;
        } else if($r->status == 1) {
            saveNoDbFiles($file, "Not found: ");
            echo "DB - fail 1\n";
            return false;
        } else {
            saveNoDbFiles($file, "Too many records: ");
            echo "DB - fail 2\n";
            return false;
        }
    } else {
        saveNoDbFiles($file, "Select failed: ");
        echo "DB - fail 3\n";
        return false;
    }
}

function updateDB($from, $to) {
    global $con, $ipaddr;
    $r = json_decode(file_get_contents("http://proxy.vivat-tv.com/updateFilm.php?from=" . urlencode($from) . "&to=" . urlencode($to)));
    //echo "http://proxy.vivat-tv.com/updateFilm.php?from=" . urlencode($from) . "&to=" . urlencode($to) . "\n";
    if(isset($r)) {
        return $r->status > 0;
    } else return false;
}

function saveNoDbFiles($file, $rem) {
    try {
        $f = fopen('nodb.txt', 'a');
        fwrite($f, $rem . $file . "\r\n");
        fclose($f);
    } catch (Exception $e) {  }
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
        }
        $data = json_decode($str);
        $sfile = str_replace($FROM_DIR, '', $data->{'format'}->{'filename'});
        checkDB($sfile);
        foreach($data->{'streams'} as $stream) {
            if($stream->{'codec_type'} == 'audio') { $acodec = isset($stream->{'codec_name'})?$stream->{'codec_name'}:null; }
            if($stream->{'codec_type'} == 'video') { $vcodec = isset($stream->{'codec_name'})?$stream->{'codec_name'}:null; }
            echo "codec " . $stream->{'codec_type'} . ", " . (isset($stream->{'codec_name'})?$stream->{'codec_name'}:"") . "\n";
        }
        if(isset($acodec) & isset($vcodec)) {
            if(($acodec == 'aac' || $acodec == 'mp3') && $vcodec == 'h264') return true; else return false;
        } else return false;
    } else { echo "Error executing " . $FFPROBE . "\n"; return false; }
}


function convert($fromFile, $toFile) {
    global $FFMPEG, $count, $REGISTERED_MEDIA_EXTENSION, $FROM_DIR, $con;
    $cmd = $FFMPEG . " -v warning -i " . escapeshellarg($fromFile) . " -map 0 -sn -vcodec libx264 -acodec libfaac " . escapeshellarg($toFile) . " -y 2>&1";
    //$cmd = $FFMPEG . " -v warning -i '" . $fromFile . "' -map 0 -sn -vcodec copy -acodec copy '" . $toFile . "' -y 2>&1";
    //$cmd = "cp '" . $fromFile . "'  '" . $toFile. "' 2>&1";
    echo $cmd . "\n";
    $exec_output = array();
    $return_var = -1;
    exec($cmd, $exec_output, $return_var);
    $path = preg_split("|/|i", $fromFile);

    if($return_var == 0) {
        //echo $fromFile . " - Ok\n";
        $updDB = updateDB(str_replace($FROM_DIR, '', $fromFile), str_replace($FROM_DIR, '', $toFile));
        if($updDB) {
            echo str_replace($FROM_DIR, '', $fromFile) . " updated\n";
            exec("rm -f " . escapeshellarg($fromFile));
        } else {
            echo str_replace($FROM_DIR, '', $fromFile) . " NOT UPDATED !!!!!\n";
        }
    } else {
        echo "\tError executing:\n";
        for($i=0; $i<sizeof($exec_output); $i++) {
            echo "\t" . $exec_output[$i] . "\n";
        }
    }
}

function scanner($path) {
    global $REGISTERED_MEDIA_EXTENSION, $FROM_DIR, $TO_DIR, $count;

    $handle = opendir($path);
    while($f = readdir($handle)) {
        if (filetype($path . $f) == 'dir') {
            if($f != '.' && $f != '..') {
                scanner($path . $f . "/");
            }
        } elseif (filetype($path . $f) == 'file') {
            if(preg_match("/\\.(".$REGISTERED_MEDIA_EXTENSION.")$/i", $f)) {
                $count++;
                $file = $path . $f;
                if(viewInfo($file) && preg_match("/\\.mp4$/i", $file)) {
                    echo "File true \n";
                } else {
                    $tofile = str_replace(".mp4", "_.mp4", $file);
                    $tofile = preg_replace("/\\.(".$REGISTERED_MEDIA_EXTENSION.")$/i", ".mp4", $tofile);
                    convert($file, $tofile);
                }
                echo "\n";
            }
        } else {
            echo "\t\tUnknown file\n";
        }
    }
    closedir($handle);
}

if(!file_exists($FROM_DIR)) { die("Directory not found\n\n"); }
scanner($FROM_DIR . "/");
echo "All success $count films.\n";
?>