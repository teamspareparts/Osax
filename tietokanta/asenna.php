<?php
print("<pre>");

$data = parse_ini_file("../tietokanta/db-config.ini.php", true);
require '../luokat/db_yhteys_luokka.class.php';

$data_db = $data['Tietokannan tiedot'];
$db = new DByhteys( $data_db['user'], $data_db['pass'], $data_db['name'], $data_db['host'] );
$mysqli = mysqli_connect( $data_db['host'], $data_db['user'], $data_db['pass'], $data_db['name'] )
	or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

// Luodaan tietokannan taulut // MySQLi, koska PDO ei pysty multi_queryyn
if (mysqli_multi_query($mysqli, file_get_contents('tietokanta.sql'))) {
    echo 'Tietokanta alustettiin onnistuneesti.<br>';
} else {
    echo 'Jokin meni pieleen tietokantaa alustettaessa<br>Virhe: ' . mysqli_error($mysqli) .
		'<br>Tarkista tietokanta ja suorita tarvittaessa <i>tietokanta.sql</i> käsin.';
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
$result = $db->query(
	"INSERT INTO yritys (nimi, y_tunnus) VALUES (?,?)",
	[	$data['Admin tunnukset']['y_nimi'],
		$data['Admin tunnukset']['y_tunnus']
	] );
$result = $db->query(
	"INSERT INTO ostoskori (yritys_id) VALUES (?)",
	[1]);

if ( $result ) {
    echo 'Ylläpitäjä luotu.<br>Tietokannan asennus on nyt suoritettu.<br>Poista tämä tiedosto (<i>asenna.php</i>) palvelimelta.';
}
