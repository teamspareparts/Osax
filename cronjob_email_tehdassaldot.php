<?php
chdir(__DIR__); // Määritellään työskentelykansio // This breaks symlinks on Windows
set_time_limit(300); // 5min

require "./luokat/emailparser.class.php";
require './luokat/dbyhteys.class.php';

$db = new DByhteys();
$rows_in_query_at_one_time = 15000;
$hankintapaikka_id = 170;
$tiedosto_polku = '../../../../imap/osax.fi/myynti/Maildir/cur';
$latest_filepath = '';

$d = dir( $tiedosto_polku );
while (false !== ($entry = $d->read())) {
	$filepath = "{$tiedosto_polku}/{$entry}";
	// could do also other checks than just checking whether the entry is a file
	if (is_file($filepath) && filemtime($filepath) > $latest_emtime) {
		$latest_emtime = filemtime($filepath);
		$latest_filepath = $filepath;
	}
}

$parser = new EmailParser( file_get_contents($latest_filepath) );

$csv_array = array_map(
	function($val) { return explode(';', $val); },
	explode("\n", str_replace(array("\r\n","\n\r","\r"),"\n", $parser->getAttachments()[0]['body'])) );

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

	foreach ( $values as $values_chunk ) {
		$db->query(
			str_replace("(?,?,?)",
						str_repeat('(?, ?, ?),', (count($values_chunk)-1)) . "(?, ?, ?)",
						$sql),
			iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($values_chunk)),false)
		);
	}
}
