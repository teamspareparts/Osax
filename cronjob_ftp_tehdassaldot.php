<?php print('<pre>');
chdir(__DIR__); // Määritellään työskentelykansio // This breaks symlinks on Windows
set_time_limit(300); // 5min

require './luokat/dbyhteys.class.php';
$db = new DByhteys();


function debug($var,$var_dump=false){
	echo"<br>\r\n<pre>Print_r ::<br>\r\n";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>\r\n";var_dump($var);echo"</pre><br>\r\n";};
}


$ftp_server = "FTP.NIPPON-PIECES.COM";
$ftp_user_name = "OSAX";
$ftp_user_pass = "5G876BBQ=DOJ3J)8+674KQ1(RZRR6KX";

$conn_id = ftp_connect($ftp_server);

// login with username and password
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// check connection
if ((!$conn_id) || (!$login_result)) {
	echo "FTP connection has failed!";
	echo "Attempted to connect to $ftp_server for user $ftp_user_name";
	exit;
} else {
	echo "Connected to $ftp_server, for user $ftp_user_name";
}

// close the FTP stream
ftp_close($conn_id);
