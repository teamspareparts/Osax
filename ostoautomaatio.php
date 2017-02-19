<?php
/**
 * Ostoautomaatio, yöajossa...
 */
require "luokat/db_yhteys_luokka.class.php";
$db = new DByhteys();

/*Config*/
$min_paivat_myynnissa = 90; //Montako päivää ollut myynnissä, vaikka olisi oikeasti ollut vähemmän
$varmuusprosentti = 0; //Montako prosenttia tilataan enemmän kuin tarvitaan
$automaatin_selite = "AUTOMAATTI"; //Ostotilauskirjalle menevä selite, jos automaation lisäämä tuote


/**
 * Lasketaan tuotteen vuosimyynti
 * @param DByhteys $db
 * @param $date
 * @param $min_paivat_myynnissa
 * @param $tuote
 * @return float|int
 */
function laske_vuosimyynti(DByhteys $db, /*String*/ $date, /*int*/ $min_paivat_myynnissa, $tuote){
	//Haetaan viimeisen vuoden tilaukset
	$sql = "	SELECT SUM(tilaus_tuote.kpl) AS myynti
  			  	FROM tilaus_tuote
  			  	LEFT JOIN tilaus
  			  		ON tilaus.id = tilaus_tuote.tilaus_id
 			  	WHERE tilaus.paivamaara >= curdate() - INTERVAL 1 YEAR
 			  		AND tilaus_tuote.tuote_id = ? ";
	$vuoden_myynti = $db->query($sql, [$tuote->id])->myynti;

	if ( $vuoden_myynti ) { //Jos tuotetta myyty
		//Jos ollut alle vuoden myynnissä, arvioidaan myynti.
		if ( $tuote->ensimmaisen_kerran_varastossa > $date ) {
			$paivat_myynnissa = intval((time() - strtotime($tuote->ensimmaisen_kerran_varastossa)) / (60 * 60 * 24));
			if ($paivat_myynnissa < $min_paivat_myynnissa) {
				$paivat_myynnissa = $min_paivat_myynnissa; //Myyntiaika oltava laskettaessa järkevän pituinen.
			}
			$myynnin_kerroin = 365 / $paivat_myynnissa;
			$vuoden_myynti = ceil($vuoden_myynti * $myynnin_kerroin);
		}
	} else {
		$vuoden_myynti = 0;
	}
	return $vuoden_myynti;
}


/**********************************************************
 * Haetaan tuotteet, joille pitää laskea uusi vuosimyynti
 **********************************************************/

// Tuote päivitettävä jos: tilaus, ostotilauskirjan muokkaus, varastosaldon muokkaus
//TODO: Fetch limit, että ei kaadu...
$sql = "  SELECT id, ensimmaisen_kerran_varastossa, hankintapaikka_id, varastosaldo 
  		  FROM tuote
  		  WHERE aktiivinen = 1 AND paivitettava = 1 AND ensimmaisen_kerran_varastossa IS NOT NULL";
$tuotteet = $db->query($sql, [], FETCH_ALL);

$date = date("Y-m-d", strtotime("-1 year", time())); //Vuoden takainen pvm

foreach ($tuotteet as $tuote) {

	$vuoden_myynti = laske_vuosimyynti($db, $date, $min_paivat_myynnissa, $tuote);
	//Tallennetaan tuotteen vuosimyynti ja merkataan päivitetyksi
	$db->query("UPDATE tuote SET vuosimyynti = ?, paivitettava = 0 WHERE id = ? ", [$vuoden_myynti, $tuote->id]);

	//Tilausväli & päivät seuraavaan lähetykseen
	$sql = "	SELECT id, toimitusjakso, oletettu_lahetyspaiva 
				FROM ostotilauskirja 
			  	WHERE hankintapaikka_id = ? AND toimitusjakso > 0 
			  	LIMIT 1";
	$otk = $db->query($sql, [$tuote->hankintapaikka_id]);

	if( !$otk || !$vuoden_myynti ) {
		continue;
	}

	/**************************************************
	 * Lasketaan montako päivää tuotteen on riitettävä
	 * ennen uutta tilausmahdollisuutta.
	 **************************************************/

	//Toimitusaika
	//TODO: Laske toimitusaika keskiarvo
	$sql = "	SELECT lahetetty, saapumispaiva 
		  		FROM ostotilauskirja_arkisto 
				WHERE hankintapaikka_id = ? 
				LIMIT 1";
	$ostotilauskirjan_aikaleimat = $db->query($sql, [$tuote->hankintapaikka_id]);
	$toimitusaika = ceil((strtotime($ostotilauskirjan_aikaleimat->saapumispaiva) - strtotime($ostotilauskirjan_aikaleimat->lahetetty)) / (60 * 60 * 24));
	$paivat_seuraavaan_lahetykseen = ceil((strtotime($otk->oletettu_lahetyspaiva) - time()) / (60 * 60 * 24));

	//PÄIVÄT SEURAAVAAN LÄHETYKSEEN + TOIMITUSAIKA + TOIMITUSJAKSO
	$paivat_riitettava = $paivat_seuraavaan_lahetykseen + $toimitusaika + ($otk->toimitusjakso * 7);


	/************************************************
	 * Lasketaan montako kappaletta tarvitaan ennen
	 * uutta tilausmahdollisuutta.
	 ************************************************/

	//Montako tuotetta menee tuona aikana.
	$menekki_ennen_tilausta = $vuoden_myynti / (365 / $paivat_riitettava);
	//Lasketaan kappalemäärä, joka on odottavalla ostotilauskirjalla
	$sql = "SELECT IFNULL(SUM(kpl), 0) AS kpl FROM ostotilauskirja_tuote_arkisto
  			  LEFT JOIN ostotilauskirja_arkisto 
  			  	ON ostotilauskirja_tuote_arkisto.ostotilauskirja_id = ostotilauskirja_arkisto.id
  			  WHERE hankintapaikka_id = ? AND tuote_id = ? AND ostotilauskirja_arkisto.saapumispaiva IS NULL";
	$kpl_odottavalla_tilauskirjalla = $db->query($sql, [$tuote->hankintapaikka_id, $tuote->id])->kpl;
	$kpl_tarvittavat = $menekki_ennen_tilausta - $tuote->varastosaldo - $kpl_odottavalla_tilauskirjalla;


	/***********************************************
	 * Lisätään tuote ostotilauskirjalle
	 ***********************************************/

	if ( $kpl_tarvittavat > 0 ) {
		$varmuusprosentti = ($varmuusprosentti != 0) ? ($varmuusprosentti/100) : 0;
		$kpl_tarvittavat = ceil( $kpl_tarvittavat * ( 1 + $varmuusprosentti ));
		//Ei päivitetä, jos käyttäjän lisäämä/muokkaama tuote
		$sql = "INSERT INTO ostotilauskirja_tuote 
	  					(ostotilauskirja_id, tuote_id, kpl, lisays_kayttaja_id, selite)
  				VALUES ( ?, ?, ?, ?, ? )
  				ON DUPLICATE KEY UPDATE
  					kpl = IF(lisays_kayttaja_id = 0, VALUES(kpl), kpl)";
		$result = $db->query($sql, [$otk->id, $tuote->id, $kpl_tarvittavat, 0, $automaatin_selite]);
	} else {
		//Poistetaan tilauskirjalta, jos automaatin lisäämä
		$sql = "DELETE FROM ostotilauskirja_tuote 
	  			WHERE tuote_id = ? AND ostotilauskirja_id = ? AND lisays_kayttaja_id = 0";
		$result = $db->query($sql, [$tuote->id, $otk->id]);
	}

	echo 	"Myynti/vuosi: " . $vuoden_myynti .
		" Paivat riitettava: " . $paivat_riitettava .
		" Menekki: " . $menekki_ennen_tilausta .
		" Tarvitaan: " . $kpl_tarvittavat .
		" Toimitusaika: " . $toimitusaika .
		" Seuraava lähetys paivat: " . $paivat_seuraavaan_lahetykseen ."<br>";



}

