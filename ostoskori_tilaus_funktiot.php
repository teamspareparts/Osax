<?php
/**
 * Tämä tiedosto sisältää funktioita ostoskorin ja tilaus-sivun toimintaa varten.
 */

global $osoitekirja_array; //Helpottaa osoitekirjan käsittelyä parin funktion, ja yhden js-muuttujan välillä.
$kayttaja_id = addslashes($_SESSION['id']);

/**
 * Hakee tietokannasta kaikki ostoskorissa olevat tuotteet.
 * 
 * @param ---
 * @return Array( ostoskorin tuotteet || Empty )
 */
function get_products_in_shopping_cart () {
	global $connection;

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
 * @param Array $products
 * @return Boolean, onnistuiko tilaaminen
 */
function order_products ( $products ) {
	global $connection;
	global $kayttaja_id;

	if (empty($products)) {
		return false;
	}

	// Lisätään uusi tilaus
	$result = mysqli_query($connection, "INSERT INTO tilaus (kayttaja_id) VALUES ($kayttaja_id);");

	if (!$result) {
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
		//päivitetään varastosaldo
		$uusi_varastosaldo = $product->varastosaldo - $product_count;
		$query = "
			UPDATE	tuote
			SET		varastosaldo = '$uusi_varastosaldo'
			WHERE 	id = '$product_id'";
		$result = mysqli_query($connection, $query);
	}

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
 * Ottaa taulukon, lyhentää sen, ja pistää wordwrapin. Palauttaa merkkijonon.
 * Tarkoitettu OE-koodien tulostukseen, mutta melko yleiskäyttöinen.
 * 
 * @param Array, tulostettava taulukko.
 * @return Merkkijono, jonka voi suoraan tulostaa
 */
function tulosta_taulukko ( $array ) {
	$tulostus = "";
	foreach ($array as $data) {
		$tulostus .= $data . " | ";
	}
	$tulostus = mb_strimwidth($tulostus, 0, 35, "..."); //Lyhentää tulostuksen tiettyyn mittaan (35 tässä tapauksessa)
	$tulostus = wordwrap($tulostus, 10, " ", true); //... ja wordwrap, 10 merkkiä pisin OE sillä hetkellä korissa
	return $tulostus;
}

/**
 * Laskee sillä hetkellä sisäänkirjautuneen käyttäjän ostoskorin/tilauksen rahtimaksun.
 * Hakee tietokannasta käyttäjän tiedot (rahtimaksu, ja ilmaisen toimituksen rajan), jos ne on asetettu.
 * Asettaa uuden hinnan, ja sen jälkeen tarkistaa, onko tilauksen summa yli ilm. toim. rajan.
 * 
 * @param ---
 * @return Array(rahtimaksu, ilmaisen toimituksen raja), indekseillä 0 ja 1. Kumpikin float
 */
function laske_rahtimaksu () {
	global $connection;
	global $sum;
	global $rahtimaksu; // array( rahtimaksu, ilmaisen toimituksen raja ), default: 15, 200

	$id = $_SESSION['id']; //Haetaan asiakkaan tiedot mahdollisesta yksilöllisestä rahtimaksusta
	$result = mysqli_query($connection, "SELECT	rahtimaksu, ilmainen_toimitus_summa_raja FROM kayttaja WHERE id = '$id';");
	$row = mysqli_fetch_array( $result, MYSQLI_ASSOC );

	if ( $row["rahtimaksu"] !== 0 ) {//Asiakkaalla on asetettu rahtimaksu
		$rahtimaksu[0] = $row["rahtimaksu"]; }

	if ( $row["ilmainen_toimitus_summa_raja"] !== 0 ) {	//Asiakkaalla on asetettu yksilöllinen ilmaisen toimituksen raja.
		$rahtimaksu[1] = $row["ilmainen_toimitus_summa_raja"]; }

	if ( $sum > $rahtimaksu[1] ) { //Onko tilaus-summa ilm. toim. rajan yli?
		$rahtimaksu[0] = 0; }

	return $rahtimaksu;
}

/**
 * Tulostaa rahtimaksun tuotelistaan, hieman eri tyylillä
 * @param unknown $ostoskori
 */
function tulosta_rahtimaksu_tuotelistaan ( $ostoskori ) {
	global $rahtimaksu;

	$rahtimaksu = laske_rahtimaksu();

	if ( $rahtimaksu[0] === 0 ) { $alennus = "Ilmainen toimitus";
	} elseif ( $ostoskori ) { $alennus = "Ilmainen toimitus " . format_euros($rahtimaksu[1]) . ":n jälkeen.";
	} else { $alennus = "---"; }

	?><!-- HTML -->
	<tr style="background-color:#cecece; height: 1em;">
		<td>---</td>
		<td>RAHTIMAKSU</td>
		<td>Rahtimaksu</td>
		<td>Posti / Itella</td>
		<td>---</td>
		<td>---</td>
		<td style="text-align: right; white-space: nowrap;"><?= format_euros($rahtimaksu[0])?></td>
		<td style="text-align: right;">---</td>
		<td style="text-align: right;">---</td>
		<td style="text-align: right;">1</td>
		<td><?= $alennus?></td>
	</tr>
	<?php
}

function hae_kaikki_toimitusosoitteet_ja_luo_JSON_array () {
	global $connection;
	global $kayttaja_id;
	global $osoitekirja_array;
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
	
	if ( count($osoitekirja_array) > 0 ) {
		return true;
	} else return false;
} hae_kaikki_toimitusosoitteet_ja_luo_JSON_array();

function hae_kaikki_toimitusosoitteet_ja_tulosta_Modal () {
	global $osoitekirja_array;

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

function tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled () {
	global $osoitekirja_array;
	$nappi_html_toimiva = '<a class="nappi" type="button" onClick="avaa_Modal_valitse_toimitusosoite();">Valitse<br>toimitusosoite</a>';
	$nappi_html_disabled = '
					<a class="nappi disabled" type="button" onClick="avaa_Modal_valitse_toimitusosoite();">Valitse<br>toimitusosoite</a>
					<p>Sinulla ei ole yhtään toimitusosoitetta profiilissa!</p>';
	
	if ( count($osoitekirja_array) > 0 ) {
		return $nappi_html_toimiva;
	} else return $nappi_html_disabled;
}

function tarkista_hinta_era_alennus ( $product ) {
	$jakotulos =  $product->cartCount / $product->alennusera_kpl;
	
	if ( $jakotulos >= 1 ) {
		$alennus_prosentti = 1 - (float)$product->alennusera_prosentti;
		$product->hinta = ($product->hinta * $alennus_prosentti);
		return $product->hinta;
	
	} else { return $product->hinta; }
}

function laske_era_alennus_tulosta_huomautus ( $product, $ostoskori ) {
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

function tulosta_product_infos_part ( $infos ) {
	foreach ( $infos as $info ) {
		if ( !empty($info->attrName) ) {
			echo $info->attrName . " "; }

		if ( !empty($info->attrValue) ) {
			echo $info->attrValue . " "; }
				
		if ( !empty($info->attrUnit) ) {
			echo $info->attrUnit . " "; }
				
		echo "<br>";
	}
}

function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled ( $products, $ostoskori ) {
	$enough_in_stock = true;
	$enough_ordered = true;
	foreach ($products as $product) {
		if ($product->cartCount > $product->varastosaldo) {
			$enough_in_stock = false;
		}
		if ($product->cartCount < $product->minimimyyntiera) {
			$enough_ordered = false;
		}
	}
    
	switch ( $ostoskori ) {
	    case TRUE:
	    	if ( $enough_in_stock && $enough_ordered ) {
	    		?><p><a class="nappi" href="tilaus.php">Tilaa tuotteet</a></p><?php
    	    } else {
    	        ?><p><a class="nappi disabled">Tilaa tuotteet</a> Tuotteita ei voi tilata, koska niitä ei ole tarpeeksi varastossa tai minimimyyntierää ei ole ylitetty.</p><?php 
    	    }
	        break;
	    case FALSE:
	        ?><p><a class="nappi" href="tilaus.php?vahvista">Vahvista tilaus</a></p><?php
	        break;
	    default:
	    	?><p><a class="nappi disabled">ERROR: Jotain meni vikaan</a><?php
	}
}
