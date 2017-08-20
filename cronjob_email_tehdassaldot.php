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
		$latest_filepath = $filepath;
	}
}

$parser = new EmailParser( file_get_contents($latest_filepath) );

$b = array_map(
	function($val) { return explode(';', $val); },
	explode("\n", str_replace(array("\r\n","\n\r","\r"),"\n", $parser->getAttachments()[0]['body'])) );

array_shift( $b ); //TODO: slow for big arrays, find better way.


if ( !empty( $b ) ) {

	foreach ( $b as $t ) {
		$t[0] = utf8_encode( str_replace( [" ","'"], "", $t[ 0 ] ) );  // tuote artikkeli-nro
		$t[1] = (int)$t[ 1 ]; // tehdassaldo
		$t[] = $hankintapaikka_id;
	}

	$sql = "INSERT INTO toimittaja_tehdassaldo (hankintapaikka_id, tuote_articleNo, tehdassaldo) VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE tehdassaldo = VALUES(tehdassaldo)";

	$values = array_chunk( $values, $rows_in_query_at_one_time );

	foreach ( $values as $values_chunk ) {
		$db->query(
			str_replace("(?,?,?)",
						str_repeat('(?, ?, ?),', (count($values_chunk)/3)-1) . "(?, ?, ?)",
						$sql),
			$values_chunk);
	}
}
