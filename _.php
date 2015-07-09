<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 09.07.15
 * Time: 15:01
 */

$REGISTERED_MEDIA_EXTENSION = "mp4|avi|mkv|ts";
$FROM_DIR = '/data/Temp/';
$TO_DIR = '/data/Temp2/';
$FFMPEG = '/usr/local/bin/ffmpeg';
$count =0;

function convert($fromFile, $toFile) {
    global $FFMPEG, $count;
    $cmd = $FFMPEG . " -v warning -i " . escapeshellarg($fromFile) . " -map 0 -sn -vcodec libx264 -acodec libfaac " . escapeshellarg($toFile) . " -y 2>&1";
    //$cmd = $FFMPEG . " -v warning -i " . $fromFile . " -map 0 -sn -vcodec libx264 -acodec libfaac " . $toFile . " -y";
    echo $cmd . "\n";
    $count++;
    $buff = shell_exec($cmd);
}

function scanner($path) {
    global $REGISTERED_MEDIA_EXTENSION, $FROM_DIR, $TO_DIR;

    $handle = opendir($path);
    while($f = readdir($handle)) {
        if (filetype($path . $f) == 'dir') {
            if($f != '.' && $f != '..') {
                $dir = $path . $f;
                $new_dir = str_replace($FROM_DIR, $TO_DIR, $dir);
                if(!file_exists($new_dir)) {
                    mkdir($new_dir);
                }
                scanner($path . $f . "/");
            }
        } elseif (filetype($path . $f) == 'file') {
            if(preg_match("/\\.(".$REGISTERED_MEDIA_EXTENSION.")$/i", $f)) {
                $file = $path . $f;
                $file = preg_replace("/\\.(".$REGISTERED_MEDIA_EXTENSION.")$/i", ".mp4", $file);
                if(!file_exists(str_replace($FROM_DIR, $TO_DIR, $file))) {
                    convert($path . $f, str_replace($FROM_DIR, $TO_DIR, $file));
                }
            }
        } else {
            echo "unknown\n";
        }
    }
    closedir($handle);
}

if(!file_exists($FROM_DIR)) { die("Directory not found\n\n"); }
if(!file_exists($TO_DIR)) { mkdir($TO_DIR); }
scanner($FROM_DIR);
echo "All success $count films.";
?>
