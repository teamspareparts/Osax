<?php 

print('<pre>');

require '../luokat/dbyhteys.class.php'; 

$db = new DByhteys( null, '../config/config.ini.php' );

print_r( $db );