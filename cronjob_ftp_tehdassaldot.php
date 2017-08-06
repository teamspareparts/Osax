<?php print('<pre>');
//chdir(__DIR__); // Määritellään työskentelykansio // This breaks symlinks on Windows
set_time_limit(300); // 5min

require './luokat/dbyhteys.class.php';
$db = new DByhteys();


function debug($var,$var_dump=false){
	echo"<br>\r\n<pre>Print_r ::<br>\r\n";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>\r\n";var_dump($var);echo"</pre><br>\r\n";};
}


//$ftp_server = "FTP.NIPPON-PIECES.COM";
//$ftp_user_name = "OSAX";
//$ftp_user_pass = "5G876BBQ=DOJ3J)8+674KQ1(RZRR6KX";

$ftp_server = "www35.zoner.fi";
$ftp_user_name = "indev@osax.fi";
$ftp_user_pass = "indev";


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

// get contents of the current directory
$contents = ftp_nlist($conn_id, ".");
debug($contents);

/*
 * Get the file
 * ftp_pwd
 * ftp_nlist
 * ftp_fget
 */

$temp_handle = fopen('php://temp', 'r+');
ftp_fget( $conn_id, $temp_handle, "", FTP_ASCII );
// close the FTP stream
ftp_close($conn_id);

if ( !empty( $temp_handle ) ) {

	// Alustukset
	$ohita_otsikkorivi = !empty($_POST['otsikkorivi']);
	$rows_in_query_at_one_time = 15000;
	$values = array();

	if ( $ohita_otsikkorivi ) {
		fgetcsv($temp_handle, 100, ";");
	}

	while (($data = fgetcsv($temp_handle, 1000, ";")) !== false) {
		$values[] = (int)$hankintapaikka_id; // hkp-ID
		$values[] = utf8_encode($data[0]);  // tuote artikkeli-nro
		$values[] = (int)$data[1]; // tehdassaldo
	}

	$sql = "INSERT INTO toimittaja_tehdassaldo (hankintapaikka_id, tuote_articleNo, tehdassaldo) VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE tehdassaldo = VALUES(tehdassaldo)";

	$values = array_chunk($values,$rows_in_query_at_one_time);

	foreach ( $values as $values_chunk ) {
		$db->query(
			str_replace("(?,?,?)",
						str_repeat('(?, ?, ?),', (count($values_chunk)/3)-1) . "(?, ?, ?)",
						$sql),
			$values_chunk);
	}
}

