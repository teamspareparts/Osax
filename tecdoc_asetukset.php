<?php
$config = parse_ini_file( "./config/config.ini.php" );

define( 'TECDOC_PROVIDER', $config[ 'tecdoc_koodi' ] );
define( 'TECDOC_SERVICE_URL', 'https://webservice.tecalliance.services/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint' );
define( 'TECDOC_THUMB_URL', 'https://webservice.tecalliance.services/pegasus-3-0/documents/' . TECDOC_PROVIDER . '/' );
define( 'TECDOC_DEBUG', false );
define( 'TECDOC_COUNTRY', 'FI' );
define( 'TECDOC_LANGUAGE', 'FI' );
