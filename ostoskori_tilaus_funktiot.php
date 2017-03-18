<?php
/**
 * Tämä tiedosto sisältää funktioita ostoskorin ja tilaus-sivun toimintaa varten.
 */

/**
 * Tarkistaa ostoskorin tuotteiden hinnat alennuksien varalta. Tarkistaa lisäksi rahtimaksun tuotteiden jälkeen.
 * @param Ostoskori $cart
 * @param User $user
 */
function check_products_in_shopping_cart ( Ostoskori $cart, User $user ) {
	/*
	 * Käydään läpi kaikki ostoskorin tuotteet ja tarkistetaan jokaisesta alennukset, ja oikea hinta.
	 */
	foreach ( $cart->tuotteet as $tuote ) {
		// Tarkistetaan, että tuotetta on tilattu tarpeeksi, ennen kuin lasketaan alennus.
		if ( $tuote->kpl_maara >= $tuote->minimimyyntiera ) {
			// Asetetaan aloitusarvo tuotteen alennukselle
			$tuote->alennus_prosentti = $user->yleinen_alennus; // Yrityksen yleinen alennusprosentti

			// Tarkistetaan määräalennukset (sortattu kappale-määrän mukaan)
			foreach ( $tuote->maaraalennukset as $ale ) {
				if ( $ale->maaraalennus_kpl <= $tuote->kpl_maara ) { // Onko tuotetta tilattu tarpeeksi alennukseen?
					// Onko alennus isompi kuin jo tallennettu arvo? (Ale-prosentit eivät mene järjestyksessä.)
					if ( $ale->alennus_prosentti > $tuote->alennus_prosentti ) {
						$tuote->alennus_prosentti = $ale->alennus_prosentti;
					}
				}
				else {
					break;
				}
			}
			// Asetetaan alennushuomautus, jos tuotteella on alennus.
			if ( $tuote->alennus_prosentti > 0 ) {
				$tuote->alennus_huomautus = "{$tuote->alennus_toString()}:n alennus.";
			}
		}
		else { // Jos tuotteen minimyyntierää ei ole ylitetty
			$tuote->alennus_huomautus = "<span style='color:red;'>
				Minimyyntierä: {$tuote->minimimyyntiera} kpl</span>";
		}

		$tuote->a_hinta_alennettu = $tuote->a_hinta * (1-$tuote->alennus_prosentti);
		$tuote->summa = $tuote->kpl_maara * $tuote->a_hinta_alennettu;

		$cart->summa_yhteensa += $tuote->summa;
	}

	if ( $cart->summa_yhteensa > $user->ilm_toim_sum_raja ) { // Tarkistetaan rahtimaksu
		$user->rahtimaksu = 0; // Jos ilmaisen toimituksen raja ylitetty, tallenetaan uusi rahtimaksu.
	}
}

/**
 * Tulostaa kaikki osoitteet (jo valmiiksi luodusta) osoitekirjasta, ja tulostaa ne Modaliin
 * @param stdClass[] $osoitekirja_array
 * @return string
 */
function toimitusosoitteiden_Modal_tulostus ( array $osoitekirja_array ) {
	$s = '';
	foreach ( $osoitekirja_array as $index => $osoite ) {
		$s .= "<div><br> \\";

		$osoite['Sähköposti'] = $osoite['sahkoposti']; unset($osoite['sahkoposti']); //Hienompi tulostus

		foreach ( $osoite as $key => $value ) {
			$s .= "
			<label><span>" . ucfirst($key) . "</span></label>{$value}<br> \\";
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
 * //TODO: funktio ei ole enää käytössä, korjaa pois, tai muuta paremmaksi --JJ 170305
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
	} else { return "<span style='color:red;'>Minimyyntierä: {$product->minimimyyntiera} kpl</span>"; }
}

/**
 * Tarkistaa pystyykö tilauksen tekemään, ja tulostaa tilaus-napin sen mukaan.
 * Syitä, miksi ei: <br> ostoskori tyhjä | tuotetta ei varastossa | minimimyyntierä alitettu | ei toimitusosoitetta.<br>
 * Tulostaa lisäksi selityksen napin mukana, jos disabled.
 * @param Ostoskori $cart
 * @param User $user
 * @param bool $ostoskori [optional] default = TRUE <p> onko ostoskori, vai tilauksen vahvistus
 * @return string <p> Palauttaa tilausnapin HTML-muodossa. Mukana huomautus, jos ei pysty tilaamaan.
 */
function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled (
		Ostoskori $cart, User $user, /* bool */ $ostoskori = TRUE ) {
	$enough_in_stock = TRUE;
	$enough_ordered = TRUE;
	$tuotteita_ostoskorissa = TRUE;
	$tmo_valittu = TRUE;
	$huomautus = '';
	$linkki = 'href="tilaus.php"';

	if ( !$ostoskori ) { //Tilauksen lähetys toimii hieman eri tavalla
		$linkki = 'onClick="laheta_Tilaus();"';
	}

	if ( count($user->toimitusosoitteet) < 1 ) {
		$tmo_valittu = false;
		$huomautus .= 'Tilaus vaatii toimitusosoitteen.<br>';
	}

	if ( $cart->tuotteet ) {
		foreach ( $cart->tuotteet as $tuote) {
			if ( $tuote->kpl_maara > $tuote->varastosaldo ) {
				$enough_in_stock = false;
				$huomautus .= "Tuotteita ei voi tilata, koska {$tuote->tuotekoodi}:tta ei ole tarpeeksi varastossa.<br>";
			}
			if ( $tuote->kpl_maara < $tuote->minimimyyntiera ) {
				$enough_ordered = false;
				$huomautus .= "Tuotteita ei voi tilata, koska {$tuote->tuotekoodi}:n minimimyyntierää ei ole ylitetty.<br>";
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
