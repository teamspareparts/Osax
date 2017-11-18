<?php //declare(strict_types=1);
spl_autoload_register(function (string $class_name) { require './luokat/' . $class_name . '.class.php'; });
chdir(__DIR__); // Määritellään työskentelykansio // This breaks symlinks on Windows
set_time_limit(300); // 5min

$db = new DByhteys();
$rows_in_query_at_one_time = 15000;
$hankintapaikka_id = 170;
$tiedosto_polku = '../../../imap/osax.fi/myynti/Maildir/cur';
$latest_filepath = '';
$latest_emtime = 0;

$kansio = dir( $tiedosto_polku );
while (false !== ($file_name = $kansio->read())) {
	$filepath = "{$tiedosto_polku}/{$file_name}";
	// could do also other checks than just checking whether the entry is a file
	if ( is_file($filepath) && (filemtime($filepath) > $latest_emtime) ) {
		$latest_emtime = filemtime($filepath);
		$latest_filepath = $filepath;
	}
}

$parser = new EmailParser( file_get_contents($latest_filepath) );

print_r( $parser );

$csv_array = array_map(
	function($val) { return explode(';', $val); },
	explode(PHP_EOL, str_replace(array("\r\n","\n\r","\r"),PHP_EOL, $parser->getAttachments()[0]['body']))
);

array_shift( $csv_array ); //TODO: slow for big arrays, find better way.

if ( !empty( $csv_array ) ) {

	foreach ( $csv_array as &$row ) {
		$row[0] = utf8_encode( str_replace( [" ","'"], "", $row[ 0 ] ) );  // tuote artikkeli-nro
		$row[1] = (int)$row[ 1 ]; // tehdassaldo
		$row[] = $hankintapaikka_id;
	}

	$sql = "INSERT INTO toimittaja_tehdassaldo (tuote_articleNo, tehdassaldo, hankintapaikka_id) VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE tehdassaldo = VALUES(tehdassaldo)";

	$values = array_chunk( $csv_array, $rows_in_query_at_one_time );

	echo count( $values ) . PHP_EOL . "<br>";

	foreach ( $values as $values_chunk ) {
		$db->query(
			str_replace("(?,?,?)",
						str_repeat('(?, ?, ?),', (count($values_chunk)-1)) . "(?, ?, ?)",
						$sql),
			iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($values_chunk)),false)
		);
		echo count($values_chunk) . " -1" . PHP_EOL . "<br>";
		echo str_replace("(?,?,?)",
						 str_repeat('(?, ?, ?),', (count($values_chunk)-1)/10) . "(?, ?, ?)",
						 $sql) . PHP_EOL . "<br>";
	}

	$db->query( "UPDATE hankintapaikka SET tehdassaldo_viim_paivitys = NOW() WHERE id = ?",
				[$hankintapaikka_id] );
}
