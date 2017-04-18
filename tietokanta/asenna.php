<?php
print("<pre>");

$config = parse_ini_file( "../config/config.ini.php", true);
require '../luokat/dbyhteys.class.php';

$db = new DByhteys( ['root','','tuoteluettelo_database','localhost'] );
$f = file('./tietokanta.sql', FILE_IGNORE_NEW_LINES); // Tietokannan taulut

// Poistetaan .sql-tiedoston kommentit
foreach ( $f as $k => $v ) {
	$f[$k] = strstr($v, '--', true) ?: $v;
}

// Muunnetaan jokainen query omaan indexiin
$db_file = explode( ";", implode("", $f) );
foreach ( $db_file as $sql ) {
	if ( !empty($sql) && strlen($sql) > 5 ) {
		$db->query( $sql );
	}
}

// Ei tehdä mitään, jos tietokanta on jo alustettu, ja täytetty alustavilla tiedoilla
if ( $db->query( "SELECT 1 FROM kayttaja LIMIT 1" ) ) {
	die('Tietokanta on jo alustettu!');
}


$db->prepare_stmt( "INSERT INTO kayttaja (sahkoposti, salasana_hajautus, yllapitaja, yritys_id) 
		VALUES (?, ?, 1, 1)");
for ( $i=0; $i<count( $config['Admin']['kayttajatunnus']); $i++ ) {
	$db->run_prepared_stmt([
		$config['Admin']['kayttajatunnus'][$i],
		password_hash( $config['Admin']['salasana'][$i], PASSWORD_DEFAULT)
	]);
}

// Luodaan ylläpitäjälle yritys ja ostoskori
$db->query(
	"INSERT INTO yritys (nimi, y_tunnus, maa, sahkoposti, puhelin, katuosoite, postinumero, postitoimipaikka) VALUES (?,?,?,?,?,?,?,?)",
	[	$config['Admin']['y_nimi'],
		$config['Admin']['y_tunnus'],
		$config['Admin']['y_maa'],
		$config['Admin']['y_sahkoposti'],
		$config['Admin']['y_puhelin'],
		$config['Admin']['y_osoite'][0],
		$config['Admin']['y_osoite'][1],
		$config['Admin']['y_osoite'][2]
	] );
$db->query( "INSERT INTO ostoskori (yritys_id) VALUES (?)",	[1]);

$db->query( "INSERT INTO laskunumero (laskunro) VALUES (?)", [1]);
$db->query( "INSERT INTO alv_kanta (kanta, prosentti) VALUES (?,?)", [0,0.00]);

echo 'Done.';
