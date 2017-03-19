<?php
print("<pre>");

$data = parse_ini_file("./db-config.ini.php", true);
require '../luokat/dbyhteys.class.php';

$db = new DByhteys(['root','','tuoteluettelo_database','localhost']);
$f = file('./tietokanta.sql', FILE_IGNORE_NEW_LINES); // Tietokannan taulut

foreach ( $f as $k => $v ) { // Poistetaan .sql-tiedoston kommentit
	$f[$k] = strstr($v, '--', true) ?: $v;
}

$db_file = explode( ";", implode("", $f) ); // Muunnetaan jokainen query omaan indexiin
foreach ( $db_file as $sql ) {
	if ( !empty($sql) && strlen($sql) > 5 ) {
		$db->query( $sql );
	}
}

// Ei tehdä mitään, jos tietokanta on jo alustettu
if ( $db->query( "SELECT 1 FROM kayttaja LIMIT 1" ) ) {
	die('Tietokanta on jo alustettu!');
}


$db->prepare_stmt( "INSERT INTO kayttaja (sahkoposti, salasana_hajautus, yllapitaja, yritys_id) 
		VALUES (?, ?, 1, 1)");
for ( $i=0; $i<count($data['Admin tunnukset']['kayttajatunnus']); $i++ ) {
	$db->run_prepared_stmt([
		$data['Admin tunnukset']['kayttajatunnus'][$i],
		password_hash($data['Admin tunnukset']['salasana'][$i], PASSWORD_DEFAULT)
	]);
}

// Luodaan ylläpitäjälle yritys ja ostoskori
$db->query(
	"INSERT INTO yritys (nimi, y_tunnus, maa, sahkoposti, puhelin, katuosoite, postinumero, postitoimipaikka) VALUES (?,?,?,?,?,?,?,?)",
	[	$data['Admin tunnukset']['y_nimi'],
		$data['Admin tunnukset']['y_tunnus'],
		$data['Admin tunnukset']['y_maa'],
		$data['Admin tunnukset']['y_sahkoposti'],
		$data['Admin tunnukset']['y_puhelin'],
		$data['Admin tunnukset']['y_osoite'][0],
		$data['Admin tunnukset']['y_osoite'][1],
		$data['Admin tunnukset']['y_osoite'][2]
	] );
$db->query( "INSERT INTO ostoskori (yritys_id) VALUES (?)",	[1]);

$db->query( "INSERT INTO laskunumero (laskunro) VALUES (?)", [1]);
$db->query( "INSERT INTO alv_kanta (kanta, prosentti) VALUES (?,?)", [0,0.00]);

//TODO: Undefined variable: result --SL 19.3
/*
if ( $result ) {
    echo 'Ylläpitäjä luotu.<br>Tietokannan asennus on nyt suoritettu.<br>Poista tämä tiedosto (<i>asenna.php</i>) palvelimelta.';
}
*/
