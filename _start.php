<?php declare(strict_types=1);

/**
 * For debugging. Tulostaa kaikki tiedot muuttujasta käyttäen print_r()- ja var_dump()-funktioita.
 * @param mixed $var
 * @param bool  $var_dump
 */
function debug($var,bool$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}

/**
 * Tulostaa numeron muodossa 1.000[,00 [€]]
 * @param mixed $number     <p> Tulostettava numero/luku/hinta
 * @param int   $dec_count  [optional] default=2 <p> Kuinka monta desimaalia. Jos nolla, ei €-merkkiä.
 * @param bool  $ilman_euro [optional] default=FALSE <p> Tulostetaanko float-arvo ilman €-merkkiä
 * @return string
 */
function format_number( $number, int $dec_count = 2, bool $ilman_euro = false ) : string {
	if ( $dec_count == 0 ) {
		return number_format( (float)$number, 0, ',', '.' );
	} else {
		return number_format( (float)$number, $dec_count, ',', '.' )
			. ( $ilman_euro ? '' : '&nbsp;&euro;' );
	}
}

/*
 * Luokat ladataan jatkossa tarpeen mukaan. PHP etsii tarvittavan luokan automaattisesti luokat-kansiosta
 */
set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();

/*
 * Aloitetaan sessio.
 * Sessio käyttäjän ID ja sähköposti, ja yrityksen ID.
 */
session_start();

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
