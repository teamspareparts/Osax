<?php
/***********************************************
 * Tiedostojen lataaminen serveriltä.
 **********************************************/


$filepath = isset($_POST['filepath']) ? $_POST['filepath'] : null;
$path_parts = isset($filepath) ? pathinfo($filepath) : null;
$allowed_extensions = ["pdf", "txt"];
if ($filepath) {
    //Varmistetaan, että ladattava tiedoston tiedostopääte on pdf tai txt
    if (!in_array($path_parts['extension'], $allowed_extensions, true)) {
        return;
    }
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=" . $path_parts['basename'] . "");
    header("Content-Transfer-Encoding: binary");
    header("Content-Type: binary/octet-stream");
    readfile($filepath);
}
