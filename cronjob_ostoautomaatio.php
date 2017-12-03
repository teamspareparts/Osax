<?php declare(strict_types=1);
/**
 * Ostoautomaatio. Listään tarpeen mukaan tuotteita ostotilauskirjoille.
 */

chdir(dirname(__FILE__)); //Määritellään työskentelykansio
set_time_limit(300);

require "luokat/dbyhteys.class.php";
$db = new DByhteys();

/*Config*/
//TODO: Configit ini -tiedostoon
$min_paivat_myynnissa = 30; //Montako päivää ollut myynnissä, vaikka olisi oikeasti ollut vähemmän
$varmuusprosentti = 0; //Montako prosenttia tilataan enemmän kuin tarvitaan
$automaatin_selite = "AUTOMAATTI"; //Ostotilauskirjalle menevä selite, jos automaation lisäämä tuote

$sql_insert_values = [];
/**
 * Lasketaan halutun hankintapaikan keskimääräinen toimitusaika
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return int
 */
function get_toimitusaika(DByhteys $db, int $hankintapaikka_id) : float {
    $oletus_toimitusaika = 7; //Käytetään mikäli aikaisempia tilauksia ei ole
    //Toimitusaika (lasketaan kolmen viime lähetyksen keskiarvo)
    $sql = "	SELECT lahetetty, saapumispaiva 
		  		FROM ostotilauskirja_arkisto 
				WHERE hankintapaikka_id = ? AND saapumispaiva IS NOT NULL
				LIMIT 3";
    $ostotilauskirjan_aikaleimat = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);
    $toimitusaika = $i = 0;
    foreach ( $ostotilauskirjan_aikaleimat as $aikaleimat ) {
        $toimitusaika += ceil((strtotime($aikaleimat->saapumispaiva) - strtotime($aikaleimat->lahetetty)) / (60 * 60 * 24));
        $i++;
    }
    if ( $toimitusaika ) {
        $toimitusaika = ceil($toimitusaika / $i); //keskiarvo
    } else {
        $toimitusaika = $oletus_toimitusaika; //default
    }
    return $toimitusaika;
}

/**********************************************************
 * Haetaan tuotteet, joille pitää laskea uusi vuosimyynti
 **********************************************************/

// Tuote päivitettävä jos: tilaus, ostotilauskirjan muokkaus, varastosaldon muokkaus
//TODO: Fetch limit, että ei kaadu...
$sql = "  SELECT id, ensimmaisen_kerran_varastossa, hankintapaikka_id, varastosaldo , articleNo
  		  FROM tuote
  		  WHERE aktiivinen = 1 AND ensimmaisen_kerran_varastossa IS NOT NULL AND (hyllypaikka IS NOT NULL AND hyllypaikka <> '')";
  		  		/*AND paivitettava = 1
  		  		AND ensimmaisen_kerran_varastossa IS NOT NULL
  		  		AND (hyllypaikka IS NOT NULL AND hyllypaikka <> '')";*/
$tuotteet = $db->query($sql, [], FETCH_ALL);

$date = date("Y-m-d", strtotime("-1 year", time())); //Vuoden takainen pvm

foreach ($tuotteet as $tuote) {

	/*************************************************************
	 * Lasketaan tuotteelle viime vuoden myynti
	 ************************************************************/
	//Haetaan viimeisen vuoden tilaukset, jotka odottavassa tilassa tai maksettu
	$sql = "	SELECT SUM(tilaus_tuote.kpl) AS myynti
  			  	FROM tilaus_tuote
  			  	LEFT JOIN tilaus
  			  		ON tilaus.id = tilaus_tuote.tilaus_id
 			  	WHERE tilaus.paivamaara >= curdate() - INTERVAL 1 YEAR
 			  		AND tilaus_tuote.tuote_id = ? 
 			  		AND tilaus.maksettu >= 0 ";
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

	//Otetaan id ja vuosimyynti talteen myöhempää inserttiä varten
    array_push($sql_insert_values, $tuote->id, $vuoden_myynti);

    if ( !$vuoden_myynti ) {
		continue;
	}


    //Tilausväli & päivät seuraavaan lähetykseen
	$sql = "	SELECT id, toimitusjakso, oletettu_lahetyspaiva
				FROM ostotilauskirja
			  	WHERE hankintapaikka_id = ? AND toimitusjakso > 0
			  	LIMIT 1";
	$otk = $db->query($sql, [$tuote->hankintapaikka_id]);

	if( !$otk ) {
		continue;
	}

	/**************************************************
	 * Lasketaan montako päivää tuotteen on riitettävä
	 * ennen uutta tilausmahdollisuutta.
	 **************************************************/

	$toimitusaika = get_toimitusaika($db, $tuote->hankintapaikka_id);

	$paivat_seuraavaan_lahetykseen = ceil((strtotime($otk->oletettu_lahetyspaiva) - time()) / (60 * 60 * 24));
	// oltava > 0
	$paivat_seuraavaan_lahetykseen = ( $paivat_seuraavaan_lahetykseen <= 0 ) ? 0 : $paivat_seuraavaan_lahetykseen;

	//PÄIVÄT SEURAAVAAN LÄHETYKSEEN + TOIMITUSAIKA + TOIMITUSJAKSO
	$paivat_riitettava = $paivat_seuraavaan_lahetykseen + $toimitusaika + ($otk->toimitusjakso * 7);


	/************************************************
	 * Lasketaan montako kappaletta tarvitaan ennen
	 * uutta tilausmahdollisuutta.
	 ************************************************/

	//Montako tuotetta menee tuona aikana.
	$menekki_ennen_tilausta = ( $vuoden_myynti / 365 ) * $paivat_riitettava;
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
	  					(ostotilauskirja_id, tuote_id, automaatti, kpl, lisays_kayttaja_id, selite)
  				VALUES ( ?, ?, ?, ?, ?, ? )
  				ON DUPLICATE KEY UPDATE
  				kpl = VALUES(kpl)";
		//kpl = IF(lisays_kayttaja_id = 0, VALUES(kpl), kpl)"; Ignore, jos käyttäjä muuttanut automaation lisäämää tuotetta
		$result = $db->query($sql, [$otk->id, $tuote->id, 1, $kpl_tarvittavat, 0, $automaatin_selite]);
	} else {
		//Poistetaan tilauskirjalta, jos automaatin lisäämä
		$sql = "DELETE FROM ostotilauskirja_tuote 
	  			WHERE tuote_id = ? AND ostotilauskirja_id = ? AND automaatti = 1";
		$result = $db->query($sql, [$tuote->id, $otk->id]);
	}


    echo    "Tuote: <b>{$tuote->articleNo}</b> " .
	    "Varastossa: " . $tuote->varastosaldo .
		" Myynti/vuosi: " . $vuoden_myynti .
		" Paivat riitettava: " . $paivat_riitettava .
		" Menekki: " . $menekki_ennen_tilausta .
		" Tarvitaan: " . $kpl_tarvittavat .
		" Toimitusaika: " . $toimitusaika .
		" Seuraava lähetys paivat: " . $paivat_seuraavaan_lahetykseen ."<br><br>";



}

// Luodaan väliaikainen taulu, jonka avulla päivitetään tuotteiden vuosimyynti
// ja merkataan tuotteet päivitetyiksi
if ( count($tuotteet) ) {
    $db->query("CREATE TABLE IF NOT EXISTS `temp_tuote_vuosimyynti`(`id` MEDIUMINT UNSIGNED NOT NULL, `vuosimyynti` INT(11) NOT NULL, PRIMARY KEY (`id`))");
    $questionmarks = implode(',', array_fill(0, count($tuotteet), '(?,?)'));
    $sql = "INSERT IGNORE INTO temp_tuote_vuosimyynti (id, vuosimyynti) VALUES {$questionmarks}";
    $db->query($sql, $sql_insert_values);

    $db->query("UPDATE tuote JOIN temp_tuote_vuosimyynti
            ON tuote.id = temp_tuote_vuosimyynti.id 
            SET tuote.vuosimyynti = temp_tuote_vuosimyynti.vuosimyynti ,
                tuote.paivitettava = 0");

    $db->query("DROP TABLE temp_tuote_vuosimyynti");
}




