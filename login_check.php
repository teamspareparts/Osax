<?php
/** Tämä sivu on puhtaasti PHP:tä, ei yhtään tulostusta käyttäjälle. Tarkoitus on tarkistaa kirjautumisen kaikki vaiheet,
 * ja lähettää eteenpäin seuraavalle sivulle. */
require 'tietokanta.php';
require 'email.php';
require 'IP.php';

/**
 * Tarkistaa käyttäjän käyttöoikeuden sivustoon, keskitetysti yhdessä funktiossa.
 * Tarkistaa salasanan, aktiivisuuden, ja demo-tilanteen, siinä järjestyksessä.
 * @param array $user_info_array; sisältää käyttäjän tiedot
 * @param string $user_password; käyttäjän antama salasana
 * @param bool $skip_pw_check; jos pw_reset, niin salasanaa ei tarvitse tarkistaa. Huom. jos TRUE, ei tarkista salasanaa!
 */
function beginning_user_checks ( array $user_info_array, /* string */ $user_password, /* bool */ $skip_pw_check ) {
	
	if ( !password_verify($user_password, $user_info_array['salasana_hajautus']) && !$skip_pw_check ) { // Varmistetaan salasanan
		header("Location:index.php?redir=2"); exit; //Salasana väärin
	}
	
	if ( $user_info_array["aktiivinen"] == 0 ) { // Tarkistetaan käyttäjän aktiivisuus
		header("Location:index.php?redir=3"); exit; //Käyttäjä de-aktivoitu
	}
	
	if ( $user_info_array['demo'] == 1 ) { // Onko käyttäjätunnus väliaikainen
		if ( new DateTime($user_info_array['voimassaolopvm']) < new DateTime() ) { //tarkistetaan, onko kokeilujakso loppunut
			header("Location:index.php?redir=9"); exit(); //Käyttöoikeus vanhentunut
		}
	}
}

/**
 * Tarkistaa käyttäjän IP:n ja lähettää ylläpitäjälle sähköpostin epäilyttävästä käytöksestä.
 * Lisäksi, jos löytää uuden sijainnin, päivittää sen tietokantaan.
 * Huom. toimii vain staattisilla IP-osoitteilla.
 * @param int $user_id; käyttäjän ID
 * @param string $viime_sijainti; käyttäjän tietokantaan tallennettu viimeinen sijainti.
 */
function check_IP_address ( /* int */ $user_id, /* string */ $viime_sijainti ) {
	
	$remoteaddr = new RemoteAddress(); // Haetaan asiakkaan oikea ip osoite
	$ip = $remoteaddr->getIpAddress(); //ditto
	$details = json_decode( file_get_contents("http://ipinfo.io/{$ip}") ); //Haetaan kaupunki lähettämällä asiakkaan ip ipinfo.io serverille
	$nykyinen_sijainti = $details->city;
	 
	if ( $nykyinen_sijainti != "" ){ //Jos sijainti tiedossa
		if ( $viime_sijainti != "" ) {
			$match = strcmp( $nykyinen_sijainti, $viime_sijainti );
			if ($match != 0){
				laheta_ilmoitus_epailyttava_IP( $email, $viime_sijainti, $nykyinen_sijainti_sijainti ); //lähetetään ylläpidolle ilmoitus
			}
		}
		//päivitetään sijainti tietokantaan
		$query = "	UPDATE	kayttaja
					SET		viime_sijainti = $viime_sijainti 
					WHERE	kayttaja_id = $id";
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	}
}

/**
 * Resetoidaan käyttäjän salasana, joko käyttäjän toimesta, tai ylläpidollisista syistä.
 * Lähettää käyttäjälle linkin sähköpostilla pw_reset-sivulle, tai jos salasana vanhentunut: ohjaa suoraan kyseiselle sivulle.
 * @param array $user_info
 * @param string $reset_mode; onko kyseessä 'reset' vai 'expired'. Eka lähettää linkin, toka ohjaa suoraan.
 */
function password_reset ( array $user_info, /* string */ $reset_mode ) {
	$user_mail = $user_info['sahkoposti'];
	$key = GUID();
	
	$sql_query = "	INSERT INTO pw_reset (reset_key, user_id)
					VALUES ('$key','$user_email')";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	if ( $reset_mode == "expired" ) { //Jos salasana vanhentunut, ohjataan suoraan salasananvaihtosivulle
		header("Location:pw_reset.php?id=".$key); exit;
	}
	
	else { // jos salasanaa pyydetty sähköpostiin, lähetetään linkki
		laheta_salasana_linkki($user_email, $key);
		header("Location:index.php?redir=6"); exit(); // Palautuslinkki lähetetty
	}
}

/**
 * So apparently, com_-aluiset funktiot on poistettu PHP-coresta 5 version jälkeen, ja ne saa vaan lisäämällä manuaalisti.
 * Jälkimmäinen osio luo käytännössä saman asian kuin com_create_guid.
 * Koodi kopioitu PHP-manuaalista, user comment.
 * @return string
 */
function GUID()	{
	if ( function_exists('com_create_guid') === true ) {
		return trim(com_create_guid(), '{}');
	} else
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

if ( empty($_POST["mode"]) ) {
	header("Location:index.php?redir=4"); // Not logged in
	exit();
}

$mode = $_POST["mode"];
$salasanan_voimassaoloaika = 180;

date_default_timezone_set("Europe/Helsinki");
session_start();


/*************************
 *  Sisäänkirjautuminen  *
 *************************/
if ( $mode == "login" ) {
	$email 		= trim(strip_tags( $_POST["email"] ));
	$password 	= trim(strip_tags( $_POST["password"] ));
	
	// Haetaan käyttäjän tiedot
	$sql_query = "	SELECT	id, sahkoposti, salasana_hajautus, yllapitaja, vahvista_eula, aktiivinen, demo, 
						voimassaolopvm,	viime_sijainti,
						CONCAT(etunimi, ' ', sukunimi) AS koko_nimi 
					FROM 	kayttaja
					WHERE 	sahkoposti = '$email'";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$user_info = mysqli_fetch_assoc($result);
	
	if ( $user_info ) {
		beginning_user_checks( $user_info, $password, FALSE ); //Tarkistetaan salasana, aktiivisuus, ja demo-tilanne
		// Jos läpi tarkistuksista...
		
   		$_SESSION['email']	= $user_info['sahkoposti'];
   		$_SESSION['id']		= $user_info['id'];
   		$_SESSION['admin']	= $user_info['yllapitaja'];
   		$_SESSION['demo']	= $user_info['demo'];
   		$_SESSION['koko_nimi'] = $user_info['koko_nimi'];
   		$_SESSION['vahvista_eula'] = $user_info['vahvista_eula'];
   		
//   		check_IP_address( $user_info['id'], $user_info['viime_sijainti'] );

   		/** Tarkistetaan salasanan voimassaoloaika */ 		
   		$time_then 	= new DateTime( $user_info['salasana_vaihdettu'] ); // muunnettuna DateTime-muotoon
		$time_now	= new DateTime();
		//Jos salasana vanhentunut tai salasana on uusittava
   		if ( ($time_then->modify("+{$salasanan_voimassaoloaika} days") < $time_now) || $user_info['salasana_uusittava'] ) {
   			password_reset( $user_info, 'expired' );
   		}
   		
   		else { //JOS KAIKKI OK->
   			if ( $_SESSION['vahvista_eula'] ) { header("Location:eula.php"); exit; } // else ...
   			header("Location:etusivu.php"); exit;
   		}
	   
	} else { //Ei tuloksia == väärä käyttäjätunnus --> lähetä takaisin
		header("Location:index.php?redir=1"); exit; // Sähköpostia ei löytynyt
	}
}



/***************************
 *  Salasanan vaihtaminen  *
 ***************************/
elseif ( $mode == "password_reset" || $mode == "password_expired") {
	$email = trim(strip_tags( $_POST['email'] ));
	
	$sql_query = "	SELECT	id, sahkoposti, aktiivinen, demo, voimassaolopvm
					FROM	kayttaja
					WHERE	sahkoposti = '$email'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	if ( $result->num_rows > 0 ) {
		$user_info = $result->fetch_assoc();
		beginning_user_checks( $user_info, NULL, TRUE );
		
		password_reset( $user_info, 'reset' );
	} else {
		header("Location:index.php?redir=1"); //Sähköpostia ei löytynyt
		exit();
	}
}

?>
