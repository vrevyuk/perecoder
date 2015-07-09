<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 06.07.15
 * Time: 14:21
 */



function checkCodecs($file) {
    $cmd = '/usr/local/bin/ffprobe -i ' . $file . ' 2>&1';
    $stdout = array();
    $result = -99;
    $video = $audio = null;

    exec($cmd, $out, $result);

    if($result == 0) {
        foreach($out as $s) {
            //echo $s . "\n";
            if(preg_match('/(h264)/i',$s, $matches)) {
                $video = $matches[1];
                echo "--------------------------->" . $video . "\n";
            }
            if(preg_match('/(aac)/i',$s, $matches)) {
                $audio = $matches[1];
                echo "--------------------------->" . $audio . "\n";
            }
        }
        return isset($video) & isset($audio);
    } else return false;
}


if(checkCodecs('/Users/glavnyjpolzovatel/Downloads/video/BestOf3D/10.mkv')) {
    echo "Ok";
} else {
    echo "Fail";
}
echo "\n";
?>