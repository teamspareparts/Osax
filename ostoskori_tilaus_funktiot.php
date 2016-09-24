<?php
/**
 * Tämä tiedosto sisältää funktioita ostoskorin ja tilaus-sivun toimintaa varten.
 */

/**
 * Hakee tietokannasta kaikki ostoskorissa olevat tuotteet.
 * @param DByhteys $db
 * @param Ostoskori $cart
 * @return array
 */
function get_products_in_shopping_cart ( DByhteys $db, Ostoskori $cart ) {
	$products = [];

	//Tarkistetaan, että tuotteiden ID:t on haettu ostoskorissa, ja jos ei, niin tehdään se.
	if ( $cart->cart_mode != 1 ) { $cart->hae_ostoskorin_sisalto( $db, TRUE ); }

	if ( !empty( $cart->tuotteet ) ) {
		$ids = implode( ',', array_keys( $cart->tuotteet ) );
		$sql = "SELECT	id, articleNo, hinta_ilman_alv, varastosaldo, minimimyyntiera, alennusera_kpl, 
					alennusera_prosentti, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta,
					ALV_kanta.prosentti AS alv_prosentti
				FROM	tuote
				LEFT JOIN ALV_kanta
					ON tuote.ALV_kanta = ALV_kanta.kanta
				WHERE 	tuote.id IN ({$ids})"; //TODO: Unsafe use of sql-statements

		$rows = $db->query( $sql, NULL, DByhteys::FETCH_ALL );

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$row->cartCount = $cart->tuotteet[$row->id][1];
				$products[] = $row;
			}
			merge_catalog_with_tecdoc($products, true);
		}
	}

	return $products;
}

/**
 * Tilaa ostoskorissa olevat tuotteet
 *
 * @param array $products
 * @param DByhteys $db
 * @param User $user
 * @param float $pysyva_rahtimaksu
 * @param int $to_id <p> toimitusosoitteen ID, joka tallennetaan pysyviin tietoihin.
 * @return bool <p> onnistuiko tilaaminen
 */
function order_products ( array $products, DByhteys $db, User $user, /*float*/ $pysyva_rahtimaksu, /*int*/ $to_id) {

	if ( empty($products) ) {
		return false;
	}

	$result = $db->query( "INSERT INTO tilaus (kayttaja_id, pysyva_rahtimaksu) VALUES (?, ?)",
		[$user->id, $pysyva_rahtimaksu] );

	if ( !$result ) {
		return false;
	}

	$tilaus_id = mysqli_insert_id($connection); //FIXME: PDO vastike?

	// Lisätään tilaukseen liittyvät tuotteet

	$db->prepare_stmt("
		INSERT INTO tilaus_tuote
			(tilaus_id, tuote_id, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl)
		VALUES
			(?, ?, ?, ?, ?, ?)" );
	foreach ($products as $product) {
		$result = $db->run_prepared_stmt( [
			$tilaus_id, $product->id, $product->hinta_ilman_alv, $product->alv_prosentti,
			$product->alennusera_prosentti, $product->cartCount
		] );

		if ( !$result ) {

		}
		$db->query( "UPDATE tuote SET varastosaldo = ? WHERE id = ?",
			[($product->varastosaldo - $product->cartCount), $product->id] );
	}

	$user->haeToimitusosoitteet( $db, $to_id );
	//TODO: Tässä voisi käyttää SELECT INTO:a.
	$query = "	INSERT INTO tilaus_toimitusosoite
					(tilaus_id, pysyva_etunimi, pysyva_sukunimi, pysyva_sahkoposti, pysyva_puhelin, pysyva_yritys, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka)
				VALUES
					( ?, ?, ?, ?, ?, ?, ?, ?, ? )";

	$db->query( $query, [
		$tilaus_id, $user->toimitusosoitteet['etunimi'], $user->toimitusosoitteet['sukunimi'],
		$user->toimitusosoitteet['sahkoposti'], $user->toimitusosoitteet['puhelin'],
		$user->toimitusosoitteet['yritys'], $user->toimitusosoitteet['katuosoite'],
		$user->toimitusosoitteet['postinumero'], $user->toimitusosoitteet['postitoimipaikka'],
		$user->toimitusosoitteet['maa']
	] );

	//lähetetään tilausvahvistus asiakkaalle
	laheta_tilausvahvistus( $user->sahkoposti, $products, $tilaus_id );
	//lähetetään tilaus ylläpidolle
	//laheta_tilaus_yllapitajalle($_SESSION["email"], $products, $tilaus_id);
	return true;
}

/**
 * //TODO: Päivitä PhpDoc
 * @param Yritys $yritys
 * @param int $tilauksen_summa
 * @return array <p> Rahtimaksun ja ilmaisen toimitusksen rajan, indekseillä 0 ja 1. Kumpikin float.
 */
function hae_rahtimaksu ( Yritys $yritys, /*int*/ $tilauksen_summa ) {
	if ( $tilauksen_summa > $yritys->ilm_toim_sum_raja ) {
		$yritys->rahtimaksu = 0;
	}
	return [$yritys->rahtimaksu, $yritys->ilm_toim_sum_raja];
}

/**
 * Tulostaa rahtimaksun alennushuomautuksen, tarkistuksen jälkeen.
 * @param array $rahtimaksu
 * @param boolean $ostoskori; onko funktio ostoskoria, vai tilaus-vahvistusta varten
 * @return string
 */
function tulosta_rahtimaksu_alennus_huomautus ( array $rahtimaksu, /*bool*/ $ostoskori ) {
	if ( $rahtimaksu[0] == 0 ) {
		$alennus = "Ilmainen toimitus";
	} elseif ( $ostoskori ) {
		$alennus = "Ilmainen toimitus " . format_euros($rahtimaksu[1]) . ":n jälkeen.";
	} else {
		$alennus = "---"; }

	return $alennus;
}

/**
 * Tulostaa kaikki osoitteet (jo valmiiksi luodusta) osoitekirjasta, ja tulostaa ne Modaliin
 * @param array $osoitekirja_array
 * @return string
 */
function toimitusosoitteiden_Modal_tulostus ( array $osoitekirja_array ) {
	$s = '';
	foreach ( $osoitekirja_array as $index => $osoite ) {
		$s .= '<div> Osoite ' . $index . '<br><br> \\';

		$osoite['Sähköposti'] = $osoite['sahkoposti']; unset($osoite['sahkoposti']);

		foreach ( $osoite as $key => $value ) {
			$s .= '<label><span>' . ucfirst($key) . '</span></label>' . $value . '<br> \\';
		}
		$s .= '
			<br> \
			<input class="nappi" type="button" value="Valitse" onClick="valitse_toimitusosoite(' . $index . ');"> \
		</div>\
		<hr> \
		';
	}
	return $s;
}

/**
 * Tarkistaa onko toimitusosoitteita, ja sen mukaan tulostaa toimitusosoitteen valinta-napin
 * @param int $osoitekirja_pituus
 * @return string <p> HTML-nappi
 */
function tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled ( /*int*/ $osoitekirja_pituus ) {
	$nappi_html_toimiva = '
		<a class="nappi" type="button" onClick="avaa_Modal_valitse_toimitusosoite();">Valitse<br>toimitusosoite</a>';
	$nappi_html_disabled = '
		<a class="nappi disabled" type="button">Valitse<br>toimitusosoite</a>
		<p>Sinulla ei ole yhtään toimitusosoitetta profiilissa!</p>';

	if ( $osoitekirja_pituus > 0 ) {
		return $nappi_html_toimiva;
	} else return $nappi_html_disabled;
}

/**
 * Tarkistaa annetun tuotteen hinnan; erityisesti määräalennuksen
 * @param stdClass $product <p> Tuote-olio
 * @return float <p> Palauttaa olion hinnan.
 */
function tarkista_hinta_era_alennus ( stdClass $product ) {
	if ( (int)$product->alennusera_kpl != 0 ) {
		$jakotulos =  (int)$product->cartCount / (int)$product->alennusera_kpl;

		if ( $jakotulos >= 1 ) {
			$alennus_prosentti = 1 - (float)$product->alennusera_prosentti;
			$product->hinta = (float)$product->hinta * $alennus_prosentti;
		}
	} else {
		$product->alennusera_prosentti = 0.0;
	}
	return $product->hinta;
}

/**
 * Palauttaa huomautuksen tuotteen kohdalle, jos sopivaa.
 * Mahdollisia huomautuksia: määräalennus | minimimyyntierä | --- (tyhjä)
 * @param stdClass $product
 * @param bool $ostoskori [optional] default = TRUE <p> onko ostoskori, vai tilauksen vahvistus
 * @return string <p> palauttaa huomautuksen
 * 		TODO: Pitäisiko olla väritystä huomautuksissa?
 */
function laske_era_alennus_palauta_huomautus ( stdClass $product, /*bool*/ $ostoskori = TRUE ) {
	if ( $product->cartCount >= $product->minimimyyntiera ) { //Tarkistetaan, onko tuotetta tilattu tarpeeksi

		if ( $product->alennusera_kpl > 0 && $product->alennusera_prosentti > 0 ) {
			$jakotulos =  $product->cartCount / $product->alennusera_kpl; // Miten paljon tuotteuta alennuserään?

			$tulosta_huomautus = // "Tilaa #kpl saadaksesi alennuksen!"
				$jakotulos >= 0.75 && $jakotulos < 1; // Kpl-määrä 75% alennuserän kpl-rajasta, mutta alle 100%

			$tulosta_alennus = $jakotulos >= 1; // Kpl-määrä yli 100%. // "Alennus asetettu"
		} else { $tulosta_alennus = FALSE; $tulosta_huomautus = FALSE; }

		if ( $tulosta_huomautus && $ostoskori ) {
			$puuttuva_kpl_maara = $product->alennusera_kpl - $product->cartCount;
			$alennus_prosentti = round( (float)$product->alennusera_prosentti * 100 );
			return "Lisää {$puuttuva_kpl_maara} kpl saadaksesi {$alennus_prosentti} % alennusta!";

		} elseif ( $tulosta_alennus ) {
			$alennus_prosentti = round((float)$product->alennusera_prosentti * 100 );
			return "Eräalennus ({$alennus_prosentti} %) asetettu.";

		} else { return "---"; }
	} else { return "<span style='color:red;'>Minimyyntierä: {$product->minimimyyntiera} kpl</span>";}
}

/**
 * Tarkistaa pystyykö tilauksen tekemään, ja tulostaa tilaus-napin sen mukaan.
 * Syitä, miksi ei: ostoskori tyhjä | tuotetta ei varastossa | minimimyyntierä alitettu | ei toimitusosoitetta.<br>
 * Tulostaa lisäksi selityksen napin mukana, jos disabled.
 * @param array $products
 * @param int $user_addr_count <p> Kuinka monta toimitusosoitetta käyttäjällä on.
 * @param bool $ostoskori [optional] default = TRUE <p> onko ostoskori, vai tilauksen vahvistus
 *        Onko käyttäjän profiilissa toimitusosoitteita. Ei tarvita ostoskorissa. Pakollinen tilauksen vahvistuksessa.
 * @return string <p> Palauttaa tilausnapin HTML-muodossa. Mukana huomautus, jos ei pysty tilaamaan.
 */
function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled (
		array $products, /*int*/ $user_addr_count, /* bool */ $ostoskori = TRUE ) {
	$enough_in_stock = TRUE;
	$enough_ordered = TRUE;
	$tuotteita_ostoskorissa = TRUE;
	$tmo_valittu = TRUE; //TODO: Haluaisin että tämä tarkistaa myös ostoskorissa tämän. Keksin jotain myöhemmin.
	$huomautus = "";
	$linkki = 'href="tilaus.php"';

	if ( !$ostoskori ) { //Tilauksen lähetys toimii hieman eri tavalla
		$linkki = 'onClick="laheta_Tilaus();"';
	}

	if ( $user_addr_count < 1 ) {
		$tmo_valittu = false;
		$huomautus .= 'Tilaus vaatii toimitusosoitteen.<br>';
	}

	if ( $products ) {
		foreach ($products as $product) {
			if ($product->cartCount > $product->varastosaldo) {
				$enough_in_stock = false;
				$huomautus .= "Tuotteita ei voi tilata, koska jotain tuotetta ei ole tarpeeksi varastossa.<br>";
			}
			if ($product->cartCount < $product->minimimyyntiera) {
				$enough_ordered = false;
				$huomautus .= "Tuotteita ei voi tilata, koska jonkin tuotteen minimimyyntierää ei ole ylitetty.<br>";
			}
		}
	} else {
		$tuotteita_ostoskorissa = false;
		$huomautus .= "Ostoskori tyhjä.<br>";
	}

	if ( $tuotteita_ostoskorissa && $enough_in_stock && $enough_ordered && $tmo_valittu ) {
		return "<p><a class='nappi' {$linkki}>Tilaa tuotteet</a></p>";
	} else {
		return "<p><a class='nappi disabled'>Tilaa tuotteet</a> {$huomautus} </p>";
	}
}
