<?php

function rewrite($filename, $data) {
    $filenum = fopen ( $filename, "w" );
    flock ( $filenum, LOCK_EX );
    fwrite ( $filenum, $data );
    fclose ( $filenum );
}
?>
