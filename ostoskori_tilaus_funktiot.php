<?php
/**
 * Tämä tiedosto sisältää funktioita ostoskorin ja tilaus-sivun toimintaa varten.
 */


/**
 * Hakee tietokannasta kaikki ostoskorissa olevat tuotteet.
 * 
 * @param Mysqli-connection
 * @return Array( ostoskorin tuotteet || Empty )
 */
function get_products_in_shopping_cart ( mysqli $connection ) {
    $cart = get_shopping_cart();
    if (empty($cart)) {
        return [];
    }

    $ids = addslashes(implode(', ', array_keys($cart)));
	$result = mysqli_query($connection, "
		SELECT	id, hinta_ilman_alv, varastosaldo, minimisaldo, minimimyyntiera, alennusera_kpl, alennusera_prosentti,
			(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta, 
			ALV_kanta.prosentti AS alv_prosentti
		FROM	tuote  
		LEFT JOIN	ALV_kanta
			ON		tuote.ALV_kanta = ALV_kanta.kanta
		WHERE 	id in ($ids);");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
            $row->cartCount = $cart[$row->id];
			array_push($products, $row);
		}
		merge_products_with_tecdoc($products);
		return $products;
	}
	return [];
}

/**
 * Tilaa ostoskorissa olevat tuotteet
 * 
 * @param array $products
 * @param mysqli $connection
 * @param int $kayttaja_id
 * @param float $pysyva_rahtimaksu
 * @param int $pysyva_toimitusosoite; toimitusosoitteen ID, joka tallennetaan pysyviin tietoihin.
 * @return boolean, onnistuiko tilaaminen
 */
function order_products ( array $products, mysqli $connection, /* int */ $kayttaja_id,
		/* float */ $pysyva_rahtimaksu, /* int */ $toimitusosoite_id) {

	if ( empty($products) ) {
		return false;
	}

	// Lisätään uusi tilaus
	$result = mysqli_query($connection, "INSERT INTO tilaus (kayttaja_id, pysyva_rahtimaksu) VALUES ($kayttaja_id, $pysyva_rahtimaksu);");

	if ( !$result ) {
		return false;
	}
	
	$tilaus_id = mysqli_insert_id($connection);

	// Lisätään tilaukseen liittyvät tuotteet
	foreach ($products as $product) {
		$article = $product->directArticle;
		$product_id = addslashes($article->articleId);
		$product_price = addslashes($product->hinta_ilman_alv);
		$alv_prosentti = addslashes($product->alv_prosentti);
		$alennus_prosentti = addslashes($product->alennusera_prosentti);
		$product_count = addslashes($product->cartCount);
		$result = mysqli_query($connection, "
			INSERT INTO tilaus_tuote 
				(tilaus_id, tuote_id, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl) 
			VALUES 
				($tilaus_id, $product_id, $product_price, $alv_prosentti, $alennus_prosentti, $product_count);");
		if (!$result) {
			return false;
		}
		
		$uusi_varastosaldo = $product->varastosaldo - $product_count; //päivitetään varastosaldo
		$query = "
			UPDATE	tuote
			SET		varastosaldo = '$uusi_varastosaldo'
			WHERE 	id = '$product_id'";
		$result = mysqli_query($connection, $query);
	}

	/**
	 * Haetaan toimitusosoitteen tiedot, ja tallennetaan ne pysyviin.
	 * //TODO: Tee tästä parempi. Käytä SELECT INTO:a. Tämä on vain temp, helppo ratkaisu
	 * @var Ambiguous $query; "Ambiguous" indeed...
	 */
	$query = "	SELECT	etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka
				FROM	toimitusosoite
				WHERE	kayttaja_id = '$kayttaja_id' 
					AND osoite_id = '$toimitusosoite_id';";
	$result = mysqli_query($connection, $query);
	$row = $result->fetch_assoc();
	$pysyva_etunimi = $row['etunimi']; $pysyva_sukunimi = $row['sukunimi']; $pysyva_sahkoposti = $row['sahkoposti']; $pysyva_puhelin = $row['puhelin']; $pysyva_yritys = $row['yritys'];
	$pysyva_katuosoite = $row['katuosoite']; $pysyva_postinumero = $row['postinumero']; $pysyva_postitoimipaikka = $row['postitoimipaikka'];
	$query = "	INSERT INTO tilaus_toimitusosoite 
					(tilaus_id, pysyva_etunimi, pysyva_sukunimi, pysyva_sahkoposti, pysyva_puhelin, pysyva_yritys, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka) 
				VALUES 
					('$tilaus_id', '$pysyva_etunimi', '$pysyva_sukunimi', '$pysyva_sahkoposti', '$pysyva_puhelin', '$pysyva_yritys', '$pysyva_katuosoite', '$pysyva_postinumero', '$pysyva_postitoimipaikka');";
	$result = mysqli_query($connection, $query);
	
	/**
	 * Laitan sähköpostin lähetyksen kommentiksi niin kukaan ei lähettele vahingossa sähköpostia
	 */
	//lähetetään tilausvahvistus asiakkaalle
	//laheta_tilausvahvistus($_SESSION["email"], $products, $order_id);
	//lähetetään tilaus ylläpidolle
	//laheta_tilaus_yllapitajalle($_SESSION["email"], $products, $order_id);
	return true;
}

/**
 * Hakee annetun käyttäjän rahtimaksun.
 * Hakee tietokannasta käyttäjän tiedot (rahtimaksu, ja ilmaisen toimituksen rajan).
 * Asettaa uuden hinnan, ja sen jälkeen tarkistaa, onko tilauksen summa yli ilm. toim. rajan.
 * 
 * @param mysqli $connection
 * @param int $kayttaja_id
 * @param int $tilauksen_summa
 * @return Array(rahtimaksu, ilmaisen toimituksen raja); indekseillä 0 ja 1. Kumpikin float
 */
function hae_rahtimaksu ( mysqli $connection, /* int */ $kayttaja_id, /* int */ $tilauksen_summa ) {
	$rahtimaksu = [15, 1000];

	$result = mysqli_query($connection, "SELECT	rahtimaksu, ilmainen_toimitus_summa_raja FROM kayttaja WHERE id = '$kayttaja_id';");
	$row = mysqli_fetch_array( $result, MYSQLI_ASSOC );

	$rahtimaksu[0] = $row["rahtimaksu"];
	$rahtimaksu[1] = $row["ilmainen_toimitus_summa_raja"];

	if ( $tilauksen_summa > $rahtimaksu[1] ) { //Onko tilaus-summa ilm. toim. rajan yli?
		$rahtimaksu[0] = 0; }

	return $rahtimaksu;
}

/**
 * Tulostaa rahtimaksun alennushuomautuksen, tarkistuksen jälkeen.
 * @param int $rahtimaksu
 * @param boolean $ostoskori; onko funktio ostoskoria, vai tilaus-vahvistusta varten
 */
function tulosta_rahtimaksu_alennus_huomautus ( /* int */ $rahtimaksu, /* bool */ $ostoskori ) {

	if ( $rahtimaksu[0] === 0 ) { $alennus = "Ilmainen toimitus";
	} elseif ( $ostoskori ) { $alennus = "Ilmainen toimitus " . format_euros($rahtimaksu[1]) . ":n jälkeen.";
	} else { $alennus = "---"; }
	
	return $alennus;
}

/**
 * Hakee kaikki annetun käyttäjän toimitusosoitteet, ja luo niistä JSON-arrayn.
 * 
 * @param mysqli $connection
 * @param int $kayttaja_id
 * @return array|boolean; riippuen kuinka pitkä array on
 */
function hae_kaikki_toimitusosoitteet_ja_luo_JSON_array ( mysqli $connection, /* int */ $kayttaja_id ) {
	$osoitekirja_array = array();
	$sql_query = "	SELECT	sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka
					FROM	toimitusosoite
					WHERE	kayttaja_id = '$kayttaja_id'
					ORDER BY osoite_id;";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$i = 0;
	while ( $row = $result->fetch_assoc() ) {
		$i++;
		foreach ( $row as $key => $value ) {
			$osoitekirja_array[$i][$key] = $value;
		}
	}
	
	return $osoitekirja_array;
}

/**
 * Tulostaa kaikki osoitteet (jo valmiiksi luodusta) osoitekirjasta, ja tulostaa ne Modaliin
 * 
 * @param array $osoitekirja_array
 */
function hae_kaikki_toimitusosoitteet_ja_tulosta_Modal ( array $osoitekirja_array ) {

	foreach ( $osoitekirja_array as $index => $osoite ) {
		echo '<div> Osoite ' . $index . '<br><br> \\';
		
		$osoite['Sähköposti'] = $osoite['sahkoposti']; unset($osoite['sahkoposti']);
		
		foreach ( $osoite as $key => $value ) {
			echo '<label><span>' . ucfirst($key) . '</span></label>' . $value . '<br> \\';
		}
		echo '
			<br> \
			<input class="nappi" type="button" value="Valitse" onClick="valitse_toimitusosoite(' . $index . ');"> \
		</div>\
		<hr> \
		';
	}
}

/**
 * Tarkistaa onko toimitusosoitteita, ja sen mukaan tulostaa toimitusosoitteen valinta-napin
 * @param int $osoitekirja_pituus
 * @return string; HTML-nappi
 */
function tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled ( /* int */ $osoitekirja_pituus ) {
	$nappi_html_toimiva = '<a class="nappi" type="button" onClick="avaa_Modal_valitse_toimitusosoite();">Valitse<br>toimitusosoite</a>';
	$nappi_html_disabled = '
					<a class="nappi disabled" type="button" onClick="avaa_Modal_valitse_toimitusosoite();">Valitse<br>toimitusosoite</a>
					<p>Sinulla ei ole yhtään toimitusosoitetta profiilissa!</p>';
	
	if ( $osoitekirja_pituus > 0 ) {
		return $nappi_html_toimiva;
	} else return $nappi_html_disabled;
}

/**
 * Tarkistaa annetun tuotteen hinnan; erityisesti määräalennuksen
 * @param stdClass $product
 */
function tarkista_hinta_era_alennus ( stdClass $product ) {
	$jakotulos =  $product->cartCount / $product->alennusera_kpl;
	
	if ( $jakotulos >= 1 ) {
		$alennus_prosentti = 1 - (float)$product->alennusera_prosentti;
		$product->hinta = ($product->hinta * $alennus_prosentti);
		return $product->hinta;
	
	} else { return $product->hinta; }
}

/**
 * Tulostaa huomautuksen tuotteen kohdalle, jos sopivaa.
 * @param stdClass $product
 * @param bool $ostoskori
 */
function laske_era_alennus_tulosta_huomautus ( stdClass $product, /* bool */ $ostoskori ) {
	$jakotulos =  $product->cartCount / $product->alennusera_kpl; //Onko tuotetta tilattu tarpeeksi eräalennukseen, tai huomautuksen tulostukseen

	$tulosta_huomautus = ( $jakotulos >= 0.75 && $jakotulos < 1 ) && ( $product->alennusera_kpl != 0 && $product->alennusera_prosentti != 0 );
	//Jos: kpl-määrä 75% alennuserä kpl-rajasta, mutta alle 100%. Lisäksi tuotteella on eräalennus asetettu (kpl-raja ei ole nolla, ja prosentti ei ole nolla).
	$tulosta_alennus = ( $jakotulos >= 1 ) && ( $product->alennusera_kpl != 0 && $product->alennusera_prosentti != 0 );
	//Jos: kpl-määrä yli 100%. Lisäksi tuotteella on eräalennus asetettu.

	if ( $tulosta_huomautus && $ostoskori ) {
		$puuttuva_kpl_maara = $product->alennusera_kpl - $product->cartCount;
		$alennus_prosentti = round((float)$product->alennusera_prosentti * 100 ) . ' %';
		echo "Lisää $puuttuva_kpl_maara kpl saadaksesi $alennus_prosentti alennusta!";

	} elseif ( $tulosta_alennus ) {
		$alennus_prosentti = round((float)$product->alennusera_prosentti * 100 ) . ' %';
		echo "Eräalennus ($alennus_prosentti) asetettu.";

	} else { echo "---"; }
}

/**
 * Tarkistaa pystyykö tilauksen tekemään, ja tulostaa tilaus-napin sen mukaan.
 * Syitä, miksi ei: ostoskori tyhjä | tuotetta ei varastossa | minimimyyntierä alitettu.<br>
 * Tulostaa lisäksi selityksen napin mukana, jos disabled.
 * @param array $products
 * @param bool $ostoskori; onko ostoskori, vai tilauksen vahvistus
 */
function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled ( array $products, /* bool */ $ostoskori ) {
	$enough_in_stock = true;
	$enough_ordered = true;
	$tuotteita_ostoskorissa = true;
	$huomautus = "";
	$linkki = "";
	
	if ( $ostoskori ) { $linkki = 'href="newfile.php"';
	} else { $linkki = 'onClick="laheta_Tilaus();"'; } //Tilauksen lähetys toimii hieman eri tavalla
	
	if ( $products ) {
		foreach ($products as $product) {
			if ($product->cartCount > $product->varastosaldo) {
				$enough_in_stock = false;
				$huomautus = "Tuotteita ei voi tilata, koska jotain tuotetta ei ole tarpeeksi varastossa.";
			}
			if ($product->cartCount < $product->minimimyyntiera) {
				$enough_ordered = false;
				$huomautus = "Tuotteita ei voi tilata, koska jonkin tuotteen minimimyyntierää ei ole ylitetty.";
			}
		}
	} else {
		$tuotteita_ostoskorissa = false;
		$huomautus = "Ostoskori tyhjä.";
	}
	
	if ( $tuotteita_ostoskorissa && $enough_in_stock && $enough_ordered ) {
		?><p><a class="nappi" <?= $linkki ?>>Tilaa tuotteet</a></p><?php
	} else {
		?><p><a class="nappi disabled">Tilaa tuotteet</a> <?= $huomautus ?> </p><?php 
	}
}