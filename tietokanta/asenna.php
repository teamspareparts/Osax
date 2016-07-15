<?php

//
// Tämä tiedosto alustaa tietokannan siten, että järjestelmä on sen jälkeen käyttövalmis.
// Oletuksena tietokantaan luodaan uusi ylläpitäjä, jonka käyttäjätunnus ja salasana on admin.
// Voit halutessasi muokata käyttäjätunnusta ja salasanaa alla olevilla muuttujilla.
//

define('ADMIN_USERNAME', 'admin@admin');
define('ADMIN_PASSWORD', 'admin');

//
// Älä muokkaa tästä eteenpäin!
//

require '../tietokanta.php';

$yhteys = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa.<br>Virhe: ' . mysqli_connect_error() . '<br>Tarkista tietokannan asetukset tiedostossa <i>tietokanta.php</i>!');

// Ei tehdä mitään, jos tietokanta on jo alustettu
if (mysqli_query($yhteys, 'SELECT 1 FROM kayttaja LIMIT 1;')) {
    die('Tietokanta on jo alustettu!');
}

// Luodaan tietokannan taulut
if (mysqli_multi_query($yhteys, file_get_contents('tietokanta.sql'))) {
    echo 'Tietokanta alustettiin onnistuneesti.<br>';
} else {
    echo 'Jokin meni pieleen tietokantaa alustettaessa<br>Virhe: ' . mysqli_error($yhteys) . '<br>Tarkista tietokanta ja suorita tarvittaessa <i>tietokanta.sql</i> käsin.';
}

// Luodaan ylläpitäjälle käyttäjä
$yllapitajan_tunnus = addslashes(ADMIN_USERNAME);
$hajautettu_salasana = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
$kysely = "INSERT INTO kayttaja (sahkoposti, salasana_hajautus, yllapitaja, vahvista_eula, etunimi) VALUES ('$yllapitajan_tunnus', '$hajautettu_salasana', 1, 0, 'Admin');";

while (mysqli_more_results($yhteys)) {
    mysqli_next_result($yhteys);
    if ($tulos = mysqli_store_result($yhteys))
        $tulos->free();
}

if (mysqli_query($yhteys, $kysely)) {
    echo 'Ylläpitäjä luotu.<br>Tietokannan asennus on nyt suoritettu.<br>Poista tämä tiedosto (<i>asenna.php</i>) palvelimelta.';
} else {
    echo 'Ylläpitäjän luonti ei onnistunut!<br>Virhe: ' . mysqli_error($yhteys);
}
