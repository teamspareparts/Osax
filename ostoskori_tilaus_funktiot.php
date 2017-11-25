<?php declare(strict_types=1);
/** Tämä tiedosto sisältää funktioita ostoskorin ja tilaus-sivun toimintaa varten. */

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

		if ( $tuote->kpl_maara > $tuote->varastosaldo ) {
			$tuote->alennus_huomautus = "<span style='color:red;'>Ei varastossa</span>";
		}

		// Alennuksien lasku
		else if ( $tuote->kpl_maara >= $tuote->minimimyyntiera ) {
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
			<input type="button" value="Valitse" class="nappi" onClick="valitse_toimitusosoite(' . $index . ');"> \
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
function tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled ( int $osoitekirja_pituus ) {
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
 * Tarkistaa pystyykö tilauksen tekemään, ja tulostaa tilaus-napin sen mukaan.
 * Syitä, miksi ei: <br> ostoskori tyhjä | tuotetta ei varastossa | minimimyyntierä alitettu | ei toimitusosoitetta.<br>
 * Tulostaa lisäksi selityksen napin mukana, jos disabled.
 * @param Ostoskori $cart
 * @param User      $user
 * @param bool      $ostoskori [optional] default = TRUE <p> onko ostoskori, vai tilauksen vahvistus
 * @return string <p> Palauttaa tilausnapin HTML-muodossa. Mukana huomautus, jos ei pysty tilaamaan.
 */
function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled (
		Ostoskori $cart, User $user, bool $ostoskori = true ) {
	$tilaaminen_mahdollista = true;
	$huomautus = '';

	if ( count($user->toimitusosoitteet) < 1 ) {
		$tilaaminen_mahdollista = false;
		$huomautus .= 'Tilaus vaatii toimitusosoitteen.<br>';
	}

	if ( $cart->tuotteet ) {
		foreach ( $cart->tuotteet as $tuote) {
			if ( $tuote->kpl_maara > $tuote->varastosaldo ) {
				$tilaaminen_mahdollista = false;
				$huomautus .= "Tuotteita ei voi tilata, koska {$tuote->tuotekoodi}:tta ei ole tarpeeksi varastossa.<br>";
			}
			if ( $tuote->kpl_maara < $tuote->minimimyyntiera ) {
				$tilaaminen_mahdollista = false;
				$huomautus .= "Tuotteita ei voi tilata, koska {$tuote->tuotekoodi}:n minimimyyntierää ei ole ylitetty.<br>";
			}
		}
	} else {
		$tilaaminen_mahdollista = false;
		$huomautus .= "Ostoskori tyhjä.<br>";
	}

	if ( $tilaaminen_mahdollista ) {
		if ( !$ostoskori ) {
			return "
				<form action='#' method=post>
					<input type=hidden name='toimitusosoite_id' value='' id='toimitusosoite_form_input'>
					<input type=hidden name='rahtimaksu' value='{$user->rahtimaksu}'>
					<input type=hidden name='vahvista_tilaus' value='true'>
					<input type='submit' value='Siirry maksamaan' class='nappi'>
				</form>";
		}
		else {
			return "<p><a class='nappi' href='tilaus.php'>Tilaa tuotteet</a></p>";
		}
	} else {
		return "<p><a class='nappi disabled'>Tilaa tuotteet</a> {$huomautus} </p>";
	}
}
