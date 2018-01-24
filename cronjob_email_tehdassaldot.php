<?php declare(strict_types=1);
// Setting error reporting on, just in case
error_reporting(E_ERROR); // Don't want to be surprised.
ini_set('display_errors', "1"); // Though pretty sure it doesn't matter through cronjob
// Autoloading classes
set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();
// Working directory, for cronjob. Mandatory.
chdir(__DIR__); // This breaks symlinks on Windows
// Just a random timelimit. Incase it get's stuck
set_time_limit(300); // 5min

function debug($var,bool$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}

echo "<pre>";
$db = new DByhteys();
$rows_in_query_at_one_time = 10000;
$hankintapaikka_id = 170;
//$tiedosto_polku = '../../../imap/osax.fi/myynti/Maildir/cur';
$tiedosto_polku = '../../../../imap/osax.fi/myynti/Maildir/cur'; // indev-versio
$latest_filepath = '';
$latest_emtime = 0;

$kansio = dir( $tiedosto_polku );
$file_name = $kansio->read();

while ( $file_name !== false ) {
	$filepath = "{$tiedosto_polku}/{$file_name}";
	// could do also other checks than just checking whether the entry is a file
	// Like what? I don't rememeber/know anymore. --jj 180124
	if ( is_file($filepath) && (filemtime($filepath) > $latest_emtime) ) {
		$latest_emtime = filemtime($filepath);
		$latest_filepath = $filepath;
	}
	$file_name = $kansio->read();
}

$parser = new EmailParser( file_get_contents($latest_filepath) );
echo "From: " . $parser->getHeader('from') . "<br>" . PHP_EOL;

/**
 * Tarkistetaan, että meillä on oikea sähköposti hallussa.
 * fileemtime() olisi pitänyt toimia, mutta jostain syystä se saattaa lukea väärän emailin.
 */
if (strpos($parser->getHeader('from'), "oradb@werner-metzger.de") === false ) {
	echo "Väärä sähköposti!" . "<br>" . PHP_EOL;
	echo "From: " . $parser->getHeader('from') . "<br>" . PHP_EOL;
	echo "Subject: " . $parser->getHeader('subject') . "<br>" . PHP_EOL;
	echo "Date: " . $parser->getHeader('date') . "<br>" . PHP_EOL;
	echo "File emtime: " . $latest_emtime . "<br>" . PHP_EOL;
	echo "Filepath: " . $latest_filepath . "<br>" . PHP_EOL;
	exit();
}

$csv_array = array_map(
	function($val) { return explode(';', $val); },
	explode(PHP_EOL, str_replace(array("\r\n","\n\r","\r"),PHP_EOL, $parser->getAttachments()[0]['body']))
);

array_shift( $csv_array ); //TODO: slow for big arrays, find better way. --JJ 251217

if ( !empty( $csv_array ) ) {

	foreach ( $csv_array as &$row ) {
		if ( empty($row) OR empty($row[0]) OR !isset($row[1]) ) {
			debug($row);
		}
		$row[0] = utf8_encode( str_replace( [" ","'"], "", $row[ 0 ] ) );  // tuote artikkeli-nro
		$row[1] = (int)$row[ 1 ]; // tehdassaldo
		$row[] = $hankintapaikka_id;
	}

	$sql = "INSERT INTO toimittaja_tehdassaldo (tuote_articleNo, tehdassaldo, hankintapaikka_id) VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE tehdassaldo = VALUES(tehdassaldo)";

	$values = array_chunk( $csv_array, $rows_in_query_at_one_time );

	foreach ( $values as $values_chunk ) {
		$val_count = count($values_chunk);
		$kysymysmerkit = str_repeat('(?,?,?),', ($val_count-1)) . "(?,?,?)";
		$sql_w_templates = str_replace("(?,?,?)", $kysymysmerkit, $sql);
		$value_array = iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($values_chunk)),false);

		$value_array_len = count($value_array);
		if ( $value_array_len % 3 != 0 ) {
			$last_array = array_pop($value_array[$value_array_len-1]);
			$value_array[] = $last_array[0];
			$value_array[] = $last_array[1];
			$value_array[] = $last_array[2];
		}

		$db->query( $sql_w_templates, $value_array );
	}

	$db->query( "UPDATE hankintapaikka SET tehdassaldo_viim_paivitys = NOW() WHERE id = ?",
				[$hankintapaikka_id] );
}
