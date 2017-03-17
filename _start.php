<?php
/**
 * @version 2017-03-09 <p> DByhteys.class-tiedoston nimeä vaihdettu
 */
/**
 * For debugging. Tulostaa kaikki tiedot muuttujasta käyttäen print_r()- ja var_dump()-funktioita.
 * @param $var
 */
function debug($var){echo"<br><pre>Print_r ::<br>";print_r($var);return;echo"<br>Var_dump ::<br>";var_dump($var);echo"</pre><br>";}

/**
 * Tulostaa numeron muodossa 1.000[,00 [€]]
 * @param float|int $number     <p> Tulostettava numero/luku/hinta
 * @param bool       $int        [optional] default=FALSE <p> Kokonaisluvuille eri tulostus ilman decimaalipaikkoja tai euro-merkkiä.
 * @param bool       $ilman_euro [optional] default=FALSE <p> Tulostetaanko float-arvo ilman euro-merkkiä
 * @param int        $dec_count  [optional] default=2 <p> Kuinka monta desimaalia.
 * @return string
 */
function format_number( /*mixed*/$number, /*bool*/$int = false, /*bool*/$ilman_euro = false, /*int*/ $dec_count = 2 ) {
	if ( $int ) { return number_format( (int)$number, 0, ',', '.' );
	} else { return number_format( (float)$number, $dec_count, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' ); }
}

/*
 * Aloitetaan sessio.
 * Sessio käyttäjän ID ja sähköposti, ja yrityksen ID.
 */
session_start();

/*
 * Ladataan sivuston käyttöön tarkoitetut luokat.
 * Joitakin näistä ei käytetä joka sivulla, mutta ihan varmuuden vuoksi ne ladataan kuitenkin tässä.
 */
require "luokat/dbyhteys.class.php";
require "luokat/user.class.php";
require "luokat/yritys.class.php";
require "luokat/ostoskori.class.php";
require "luokat/tuote.class.php";

/*
 * Luodaan tarvittava oliot
 * Näitä tarvitaan joka sivulla, joten ne luodaan jo tässä vaiheessa.
 */
$db = new DByhteys();
$user = new User( $db, $_SESSION['id'] );
$cart = new Ostoskori( $db, $user->yritys_id ); // Headerin ostoskori-linkki ja tiedot

/*
 * Tarkistetaan, että käyttäjä on olemassa, ja oikea.
 */
if ( !$user->isValid() ) {
	$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
	header( 'Location: index.php?redir=4' ); exit;
}
/*
 * Lisäksi tarkistetaan EULA, jotta käyttäjä ei pysty käyttämään sivustoa ilman hyväksyntää.
 */
elseif ( !$user->eulaHyvaksytty() ) {
	$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header( 'Location: eula.php' ); exit;
}
