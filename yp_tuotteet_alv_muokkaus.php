<?php
/*
Hake kaikki tietokannassa olevat ALV-tasot, ja tulostaa ne näkyville.
Param: ---
Return: ---
*/
function hae_kaikki_ALV_tasot_ja_tulosta() {
	global $connection;
	$sql_query = "
			SELECT	*
			FROM	ALV_taso;";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	$result->fetch_assoc();   //En halua ensimmaista tulosta, joka on (0, 0.00).
	
	while ($row = $result->fetch_assoc()) {
        printf (
			"<label>ALV-taso %s:</label><input type=\"text\" name=\"alv[]\" value=\"%s\"><br>\\", 
			$row['taso'], $row['prosentti']);
    }
}

/*
Laskee ALV-tasojen maaran tietokannassa, ja palauttaa kokonaislukuna indeksin.
 Kaytossa lomakkeen numerointia varten js.lisaa_uusi_ALV()-funktiossa. 
 Kyseinen funktio pitaa ylla indeksista jotta ALV-tasot voidaan numeroida kayttajalle,
 ja sita varten tarvitaan jo olemassa olevien kokonaismaara.
Param: ---
Return: kokonaisluku, SQL-haun rivien maara (ALV-tasojen maara)
*/
function hae_ALV_indeksi(){
	global $connection;
	$sql_query = "
			SELECT	*
			FROM	ALV_taso;";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$row_count = mysqli_num_rows($result);   //Tarvitaan lomakkeen numerointia varten.
	return $row_count;
}

/* tallenna_uudet_ALV_tiedot()
Param: lista uusista ALV-arvoista
Return:
*/
function tallenna_uudet_ALV_tiedot($alv_array) {
	$index = 1;
	$poistettavat = 0;
	foreach ( $alv_array as $alv ) {
		if ( !empty($alv) ) {
			$alv = str_replace(',', '.', $alv);
			paivita_alv_tietokantaan($index, $alv);
			$index++;
		} else {
			//Jatan taman elsen tahan, jos sille tulee tarvetta
			//Tarkoitus oli turhien poistamista varten, mutta koska tuotteiden hintojen
			// paivitysta ei olla viela mietitty...
			$poistettavat++;
		}
	}
	header("Refresh:0");
}

/* paivita_alv_tietokantaan()
Param:
Return:
*/
function paivita_alv_tietokantaan($key, $alv) {
	global $connection;
	
	$sql_query = "
			UPDATE	ALV_taso
			SET		prosentti = '$alv'
			WHERE	taso = '$key';";
				
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	return $result;
}
?>

<script>
function avaaModal() {
  Modal.open( {
    content:  '\
		<form action=yp_tuotteet.php.php name=testilomake method=post>\
			<div id=alv_form_container>\
				<?php hae_kaikki_ALV_tasot_ja_tulosta() ?> \
			</div>\
			<input type=button name=add_New_ALV_button value="+ uusi ALV-taso" onclick=lisaa_uusi_ALV()><br>\
			<br>\
			<input type=submit name=muokkaa_ALV value="Tallenna muutokset">\
		</form>\
		',
    draggable: true
  } );
}

var alv_i = <?= hae_ALV_indeksi() ?>;
/*
Lisaa uuden ALV-tason ALV-lomakkeeseen, joka on modal-ikkunassa (avaaModal()-funktio suoraan ylapuolella).
Param: ---
Return: ---
*/
function lisaa_uusi_ALV() {
	var newdiv = document.createElement('div');
	newdiv.innerHTML = "<label>ALV-taso " + alv_i + ":</label>" 
		+ "<input type=\"text\" name=\"alv[]\" placeholder=\"0,00\"><br>\ ";
	document.getElementById('alv_form_container').appendChild(newdiv);
	alv_i++;
}
</script>

<!-- HTML -->
<input class="button" type="button" value="Muokkaa ALV-tasoja" onClick=avaaModal()>
<!-- HTML END -->

<?php
if ( !empty($_POST["muokkaa_ALV"]) ) {
	tallenna_uudet_ALV_tiedot($_POST["alv"]);
}
?>