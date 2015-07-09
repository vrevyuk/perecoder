<?php
/**
 * Created by PhpStorm.
 * User: glavnyjpolzovatel
 * Date: 09.07.15
 * Time: 15:37
 */

$query = "UPDATE add_video_url as t2, update_video as t1
            SET t2.url = t1.toUrl
              WHERE t2.url like CONCAT('%', REPLACE(t1.fromUrl, '%', '\%'), '%')"

?>