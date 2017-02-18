<?php
/**
 * Tämä sivu on puhtaasti PHP:tä, ei yhtään tulostusta käyttäjälle. Tarkoitus on tarkistaa
 * kirjautumisen kaikki vaiheet, ja lähettää eteenpäin seuraavalle sivulle.
 * @version 2017-02-09 <p> Versionumero lisätty
 */
require 'luokat/db_yhteys_luokka.class.php';
require 'luokat/email.class.php';
require 'luokat/IP.class.php';
require 'tecdoc.php';

/**
 * Tarkistaa käyttäjän käyttöoikeuden sivustoon, keskitetysti yhdessä funktiossa.
 * Tarkistaa salasanan, aktiivisuuden, ja demo-tilanteen, siinä järjestyksessä.
 * @param stdClass $user <p> sisältää käyttäjän tiedot
 * @param string $user_password <p> käyttäjän antama salasana
 * @param bool $skip_pw_check [optional] <p> jos pw_reset, niin salasanaa ei tarvitse tarkistaa.
 * 		Huom. jos TRUE, ei tarkista salasanaa!
 */
function beginning_user_checks ( stdClass $user, /*string*/ $user_password, /*bool*/ $skip_pw_check = FALSE ) {
	if ( !password_verify($user_password, $user->salasana_hajautus) && !$skip_pw_check ) {
		header("Location:index.php?redir=2"); exit; //Salasana väärin
	}
	if ( $user->aktiivinen == 0 ) { // Tarkistetaan käyttäjän aktiivisuus
		header("Location:index.php?redir=3"); exit; //Käyttäjä de-aktivoitu
	}
	if ( $user->demo == 1 ) { // Onko käyttäjätunnus väliaikainen
		if ( new DateTime( $user->voimassaolopvm ) < new DateTime() ) { //tarkistetaan, onko kokeilujakso loppunut
			header( "Location:index.php?redir=9" );
			exit(); //Käyttöoikeus vanhentunut
		}
	}
}

/**
 * Tarkistaa käyttäjän IP:n ja lähettää ylläpitäjälle sähköpostin epäilyttävästä käytöksestä.
 * Lisäksi, jos löytää uuden sijainnin, päivittää sen tietokantaan.
 * Huom. toimii vain staattisilla IP-osoitteilla.
 * @param DByhteys $db
 * @param stdClass $user
 */
function check_IP_address ( DByhteys $db, stdClass $user ) {
	$remoteaddr = new RemoteAddress();
	$ip = $remoteaddr->getIpAddress(); //Haetaan asiakkaan IP-osoite
	$details = json_decode( file_get_contents("http://ipinfo.io/{$ip}") );
	//Haetaan kaupunki lähettämällä asiakkaan ip ipinfo.io serverille
	$nykyinen_sijainti = $details->city;
	 
	if ( $nykyinen_sijainti != "" ) { //Jos sijainti tiedossa
		if ( $user->viime_sijainti != "" ) {
			$match = strcmp( $nykyinen_sijainti, $user->viime_sijainti );
			if ( $match != 0 ) {
				Email::lahetaIlmoitus_EpailyttavaIP( $user, $user->viime_sijainti, $nykyinen_sijainti );
			}
		}
		//päivitetään sijainti tietokantaan
		$sql = "UPDATE kayttaja
				SET viime_sijainti = $nykyinen_sijainti
				WHERE sahkoposti = ?";
		$db->query( $sql, [$user->sahkoposti] );
	}
}

/**
 * Resetoidaan käyttäjän salasana, joko käyttäjän toimesta, tai ylläpidollisista syistä.
 * Lähettää käyttäjälle linkin sähköpostilla pw_reset-sivulle, tai jos salasana vanhentunut:
 *  ohjaa suoraan kyseiselle sivulle.
 * @param DByhteys $db
 * @param stdClass $user
 * @param string $reset_mode <p> onko kyseessä 'reset' vai 'expired'. Eka lähettää linkin, toka ohjaa suoraan.
 */
function password_reset ( DByhteys $db, stdClass $user, /*string*/ $reset_mode ) {
	$key = GUID();
	$key_hashed = sha1( $key );
	
	$sql = "INSERT INTO pw_reset (kayttaja_id, reset_key_hash) VALUES ( ?, ? )";
	$db->query( $sql, [$user->id, $key_hashed] );
	
	if ( $reset_mode == "expired" ) { //Jos salasana vanhentunut, ohjataan suoraan salasananvaihtosivulle
		header("Location:pw_reset.php?id={$key}"); exit;
	}
	else { // jos salasanaa pyydetty sähköpostiin, lähetetään linkki
		Email::lahetaSalasanaLinkki( $user->sahkoposti, $key );
		header("Location:index.php?redir=6"); exit(); // Palautuslinkki lähetetty
	}
}

/**
 * So apparently, com_-aluiset funktiot on poistettu PHP-coresta 5 version jälkeen,
 * ja ne saa vaan lisäämällä manuaalisti.
 * Jälkimmäinen osio luo käytännössä saman asian kuin com_create_guid.
 * Koodi kopioitu PHP-manuaalista, user comment.
 * @return string
 */
function GUID()	{
	if ( function_exists('com_create_guid') ) {
		return trim(com_create_guid(), '{}');
	} else
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

if ( empty($_POST["mode"]) ) {
	header("Location:index.php?redir=4"); exit(); // Not logged in
}

$db = new DByhteys();
$mode = $_POST["mode"];
$email = isset($_POST["email"]) ? trim($_POST["email"]) : NULL;
$password = (isset($_POST["password"]) && strlen($_POST["password"]) < 300)
	? trim($_POST["password"]) : NULL;
$salasanan_voimassaoloaika = 180;

date_default_timezone_set("Europe/Helsinki");

/*************************
 *  Sisäänkirjautuminen  *
 *************************/
if ( $mode == "login" ) {
	session_start();

	// Haetaan käyttäjän tiedot
	$sql = "SELECT id, sahkoposti, salasana_hajautus, yllapitaja, vahvista_eula, aktiivinen, demo,
				voimassaolopvm,	viime_sijainti, yritys_id, salasana_vaihdettu, salasana_uusittava,
				CONCAT(etunimi, ' ', sukunimi) AS koko_nimi
			FROM kayttaja
			WHERE sahkoposti = ?";
	$login_user = $db->query( $sql, [$email] );
	
	if ( $login_user ) {
		beginning_user_checks( $login_user, $password ); //Tarkistetaan salasana, aktiivisuus, ja demo-tilanne
		// Jos läpi tarkistuksista -->

        //check_IP_address( $db, $user->id, $user_info->viime_sijainti );

   		/** Tarkistetaan salasanan voimassaoloaika */ 		
   		$time_then 	= new DateTime( strval($login_user->salasana_vaihdettu) ); // muunnettuna DateTime-muotoon
		$time_now	= new DateTime();
		//Jos salasana vanhentunut tai salasana on uusittava
   		if ( ($time_then->modify("+{$salasanan_voimassaoloaika} days") < $time_now) ||
			$login_user->salasana_uusittava ) {
   			password_reset( $db, $login_user, 'expired' );
   		}
   		
   		else { //JOS KAIKKI OK->
            $_SESSION['id']	= $login_user->id;
            $_SESSION['yritys_id'] = $login_user->yritys_id;
            $_SESSION['email'] = $login_user->sahkoposti;
            addDynamicAddress();

			if ( $login_user->vahvista_eula ) {
				header("Location:eula.php"); exit;
			} // else ...
   			header("Location:etusivu.php"); exit;
   		}
	   
	} else { //Ei tuloksia == väärä käyttäjätunnus --> lähetä takaisin
		header("Location:index.php?redir=1"); exit; // Sähköpostia ei löytynyt
	}
}

/***************************
 *  Salasanan vaihtaminen  *
 ***************************/
elseif ( $mode == "password_reset" ) {
	
	$sql = "SELECT id, sahkoposti, aktiivinen, demo, voimassaolopvm
			FROM kayttaja
			WHERE sahkoposti = ?";
	$login_user = $db->query( $sql, [$email] );
	
	if ( $login_user ) {
		beginning_user_checks( $login_user, NULL, TRUE );
		password_reset( $db, $login_user, 'reset' );
	} else {
		header("Location:index.php?redir=1"); //Sähköpostia ei löytynyt
		exit();
	}
}

//TODO: Make better --JJ 17-02-13
header("Location:index.php?redir=666"); exit(); // Something has gone horribly wrong
