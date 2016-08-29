<?php
print("<pre>");
//
// Tämä tiedosto alustaa tietokannan siten, että järjestelmä on sen jälkeen käyttövalmis.
// Oletuksena tietokantaan luodaan uusi ylläpitäjä, jonka käyttäjätunnus ja salasana on admin.
// Voit halutessasi muokata käyttäjätunnusta ja salasanaa alla olevilla muuttujilla.
//

define('ADMIN_USERNAME', 'admin@admin');
define('ADMIN_PASSWORD', password_hash('admin', PASSWORD_DEFAULT));

define('ADMIN_COMPANY', 'Osax Oy');
define('ADMIN_YTUNNUS', '00000-0');
//
// Älä muokkaa tästä eteenpäin!
//

require '../tietokanta.php';

// Ei tehdä mitään, jos tietokanta on jo alustettu
if ( mysqli_query( $connection, "SELECT 1 FROM kayttaja LIMIT 1" ) ) {
	die('Tietokanta on jo alustettu!');
}

// Luodaan tietokannan taulut
if (mysqli_multi_query($connection, file_get_contents('tietokanta.sql'))) {
    echo 'Tietokanta alustettiin onnistuneesti.<br>';
} else {
    echo 'Jokin meni pieleen tietokantaa alustettaessa<br>Virhe: ' . mysqli_error($connection) . '<br>Tarkista tietokanta ja suorita tarvittaessa <i>tietokanta.sql</i> käsin.';
	exit;
}

// Luodaan ylläpitäjälle käyttäjä ja yritys
$db->query(
	"INSERT INTO kayttaja (sahkoposti, salasana_hajautus, yllapitaja, vahvista_eula, etunimi, yritys_id) VALUES (?, ?, 1, 0, 'Admin', 1)",
	[ADMIN_USERNAME, ADMIN_PASSWORD] );
$result = $db->query(
	"INSERT INTO yritys (nimi, y_tunnus) VALUES (?,?)",
	[ADMIN_COMPANY, ADMIN_YTUNNUS]);
$result = $db->query(
	"INSERT INTO ostoskori (yritys_id) VALUES (?)",
	[1]);

if ( $result ) {
    echo 'Ylläpitäjä luotu.<br>Tietokannan asennus on nyt suoritettu.<br>Poista tämä tiedosto (<i>asenna.php</i>) palvelimelta.';
}
