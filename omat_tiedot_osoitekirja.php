<?php 
$kayttaja_id = $_SESSION['id'];
/*
 * Hakee kaikki toimitusosoitteet ja tulostaa, plus kaksi nappia; toinen muokkaamista
 *  ja toinen poistamista varten.
 */
function hae_kaikki_toimitusosoitteet_ja_tulosta() {
	global $connection;
	global $kayttaja_id;
	$sql_query = "	SELECT	*
					FROM	toimitusosoite
					WHERE	kayttaja_id = '$kayttaja_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	while ($row = $result->fetch_assoc()) {
		?><!--  HTML  -->
		<p> Osoite <?= $row['osoite_id'] ?><br>
		<br>
		<label><span>Sähköposti</span></label><?= $row['sahkoposti']?><br>
		<label><span>Puhelin</span></label><?= $row['puhelin']?><br>
		<label><span>Yritys</span></label><?= $row['yritys']?><br>
		<label><span>Katuosoite</span></label><?= $row['katuosoite']?><br>
		<label><span>Postinumero</span></label><?= $row['postinumero']?><br>
		<label><span>Postitoimipaikka</span></label><?= $row['postitoimipaikka']?><br><br>
		
		<input class="nappi" type="button" value="Muokkaa" 
			onClick="avaa_Modal_toimitusosoite_muokkaa(<?= $row['osoite_id'] ?>);">
		<input class="nappi" type="button" value="Poista" style="background:rgb(210, 0, 6);border-color: #b70004;"
			onClick="vahvista_Osoitteen_Poistaminen(<?= $row['osoite_id'] ?>);">
			
		<?php $form_id = "poista_Osoite_Form_" . $row['osoite_id'];?>
		<form style="display:none;" id="<?= $form_id ?>" action="#" method=post>
			<input type=hidden name=poista value="<?= $row['osoite_id'] ?>">
		</form>
		
		</p><hr>
		<!--  HTML  --><?php
	}
}
	
/* hae_toimitusosoite()
 * Hakee kirjautuneen kayttajan toimitusosoitteen, ja palauttaa sen arrayna.
 * Param:
 *		$osoite_id : int, muokattavan osoitteen ID
 * Return:	Array, jossa toimitusosoitteen tiedot
 */
function hae_toimitusosoite($osoite_id) {
	global $connection;
	global $kayttaja_id;

	$sql_query = "	SELECT	*
					FROM	toimitusosoite
					WHERE	kayttaja_id = '$kayttaja_id'
						AND osoite_id = '$osoite_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$row = $result->fetch_assoc();
	return $row;
}

/* 
 * Tallentaa uudet tiedot tietokantaan. Tallennettavat tiedot $_POST-muuttujan kautta.
 *  Funktio on tarkoitus kutsua vain lomakkeen lähetyksen jälkeen.
 *  Tarkistaa tyhjät kentät, ja päivittää vain muuttuneet tiedot
 * Param: ---
 * Return:	Boolean, true/false
 */
function tallenna_uudet_tiedot() {
	global $kayttaja_id;
	global $connection;
	$osoite_id = $_POST['osoite_id'];
	
	$possible_fields = array('sahkoposti','puhelin','yritys','katuosoite','postinumero','postitoimipaikka');
	$i = 0;
	$cleaned_array = array_filter($_POST); //Poistaa tyhjat
	$len = count($cleaned_array);
	
	if ( $len >= 3 ) {	// Onko päivitettäviä tietoja? Ei turhia sql-hakuja.
		$sql_query = "UPDATE toimitusosoite SET "; //Aloitusosa
		
		foreach ( $cleaned_array as $key => $value ) {		//Täytetään hakuun päivitettävät osat
			$k = htmlspecialchars( $key );
			$v = htmlspecialchars( $value );
			if ( in_array( $k, $possible_fields ) ) {
				$sql_query .= $k . " = '" . $value . "'";
				if ( $i < ($len-3) ) { $sql_query .= ', '; } //Jos vielä osia, lisätään erotin
				$i++;
			}
		}
		
		$sql_query .= " WHERE kayttaja_id = '" . $kayttaja_id . "' AND osoite_id = '" . $osoite_id . "';"; //Loppuosa
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
		return $result;
	}
	return false; //Jos ei yhtään päivitettävää osaa
}

/* lisaa_uusi_osoite()
 * Lisaa uuden osoitteen tietokantaan listan loppuun. Tallennettavat tiedot $_POST-muuttujan kautta.
 *  Funktio on tarkoitus kutsua vain lomakkeen vastaanottamisen jalkeen.
 * Param: ---
 * Return:	Boolean, true/false
 */
function lisaa_uusi_osoite() {
	global $kayttaja_id;
	global $connection;

	$a = $_POST['email'];		//Olin laiska kun nimesin nama muuttujat
	$b = $_POST['puhelin'];
	$c = $_POST['yritys'];
	$d = $_POST['katuosoite'];
	$e = $_POST['postinumero'];
	$f = $_POST['postitoimipaikka'];
	$osoite_id_viimeinen = hae_osoitteet_viimeinen_indeksi();
	$uusi_osoite_id = ++$osoite_id_viimeinen;
	
	$sql_query = "	INSERT 
					INTO	toimitusosoite
						(kayttaja_id, osoite_id, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka)
					VALUES 	('$kayttaja_id', '$uusi_osoite_id', '$a', '$b', '$c', '$d', '$e', '$f');";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	return $result;
}

/* poista_osoite()
 * Poistaa tietokannasta toimitusosoitteen annetulla ID:lla. Siirtaa viimeiselta paikalta 
 *  toimitusosoitteen poistetun paikalle listan eheyden sailyttamiseksi. Poistettava 
 *  ID $_POST-muuttujan kautta. Funktio on tarkoitus kutsua vain lomakkeen vastaanottamisen jalkeen.
 * Param: ---
 * Return:	Boolean, true/false
 */
function poista_osoite() {
	global $kayttaja_id;
	global $connection;
	$osoite_id = $_POST["poista"];
	$osoite_id_viimeinen = hae_osoitteet_viimeinen_indeksi();

	$sql_query = "	DELETE
					FROM 	toimitusosoite
					WHERE 	osoite_id = '$osoite_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));

	if ( mysqli_affected_rows($connection) > 0 ) {
		$sql_query = "	UPDATE	toimitusosoite
						SET		osoite_id='$osoite_id'
						WHERE	kayttaja_id = '$kayttaja_id'
							AND osoite_id = '$osoite_id_viimeinen'";
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	}
	
	return $result;
}

/* hae_osoitteet_indeksi()
 * Hakee viimeisen toimitusosoitteen indeksin + 1.
 * Param: ---
 * Return:	int, indeksi+1
 */
function hae_osoitteet_viimeinen_indeksi(){
	global $connection;
	global $kayttaja_id;
	
	$sql_query = "	SELECT	osoite_id
					FROM	toimitusosoite
					Where	kayttaja_id = '$kayttaja_id';";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$row_count = mysqli_num_rows($result);
	return $row_count;
}

if ( !empty($_POST["muokkaa_vanha"]) ) {
	tallenna_uudet_tiedot();
	
} elseif ( !empty($_POST["tallenna_uusi"]) ) {
	lisaa_uusi_osoite();
	
} elseif ( !empty($_POST["poista"]) ) {
	poista_osoite();
} else; //Do nothing
?>


<script src="js/jsmodal-1.0d.min.js"></script>
<script>
/*
 * Modal-ikkuna toimitusosoitteen muokkaamista varten
 */
function avaa_Modal_toimitusosoite_muokkaa( osoite_id ) {
	Modal.open( {
		content:  '\
			<div>Muokkaa tietoja (Osoite ' + osoite_id + ')</div>\
			<br>\
			<form action="#" method=post>\
				<label>Sähköposti</label>\
					<input name="email" type="email" pattern=".{3,50}" placeholder="Edellinen sähköposti"><br>\
				<label>Puhelin</label>\
					<input name="puhelin" type="tel" pattern=".{1,20}" placeholder="Edellinen puhelinumero"><br>\
				<label>Yritys</label>\
					<input name="yritys" type="text" pattern=".{3,50}" placeholder="Edellinen yritys"><br>\
				<label>Katuosoite</label>\
					<input name="katuosoite" type="text" pattern=".{3,50}" placeholder="Edellinen katuosoite"><br>\
				<label>Postinumero</label>\
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="Edellinen postinumero"><br>\
				<label>Postitoimipaikka</label>\
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="Edellinen postitoimipaikka">\
				<br><br>\
				<input type="hidden" name="osoite_id" value= ' + osoite_id + '>\
				<input type="submit" name="muokkaa_vanha" value="Tallenna uudet tiedot (tyhjiä kenttiä ei oteta huomioon)">\
				<br>\
			</form>\
			',
		draggable: true
	} );
}
/*
 * Modal-ikkuna uuden toimitusosoitteen lisäämistä varten
 */
function avaa_Modal_toimitusosoite_lisaa_uusi() {
	Modal.open( {
		content:  '\
			<div>Lisää uuden toimitusosoitteen tiedot</div>\
			<br>\
			<form action="#" method=post>\
				<label>Sähköposti</label>\
					<input name="email" type="email" pattern=".{3,50}" placeholder="yourname@email.com" required><br>\
				<label>Puhelin</label>\
					<input name="puhelin" type="tel" pattern=".{1,20}" placeholder="000 1234 789" required><br>\
				<label>Yritys</label>\
					<input name="yritys" type="text" pattern=".{3,50}" placeholder="Yritys Oy"><br>\
				<label>Katuosoite</label>\
					<input name="katuosoite" type="text" pattern=".{3,50}" placeholder="Katu 42" required><br>\
				<label>Postinumero</label>\
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="00001" required><br>\
				<label>Postitoimipaikka</label>\
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="KAUPUNKI" required>\
				<br><br>\
				<input type="submit" name="tallenna_uusi" value="Tallenna uusi osoite">\
				<br>\
			</form>\
			',
		draggable: true
	} );
}

function vahvista_Osoitteen_Poistaminen(osoite_id) {
	var form_ID = "poista_Osoite_Form_" + osoite_id;
	var vahvistus = confirm( "Oletko varma, että haluat poistaa osoitteen?\n"
							+ "Tätä toimintoa ei voi perua jälkeenpäin.\n"
							+ "Jos painat OK, osoite poistetaan, ja sivu latautuu uudelleen.\n"
							+ "(Huom. Osoitetietoja ei poisteta mahdollisista tilaustiedoista.)");
	if ( vahvistus == true ) {
		document.getElementById(form_ID).submit();
	} else {
		return false;
	}
}
</script>

<fieldset style="display:inline-block; text-align:left;">
	<Legend>Osoitekirja</legend>
	<?= hae_kaikki_toimitusosoitteet_ja_tulosta(); ?>
	
	<div id="submit">
		<input class="nappi" type="button" value="Lisää uusi toimitusosoite" 
			onClick="avaa_Modal_toimitusosoite_lisaa_uusi();">
	</div>
</fieldset>
