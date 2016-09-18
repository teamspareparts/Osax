
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/jsmodal-light.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script src="js/jsmodal-1.0d.min.js"></script>
<title>Toimittajat</title>
</head>
<body>
<?php
require 'header.php';
require 'tietokanta.php';
require 'tecdoc.php';
if (!is_admin()) {
	header("Location:etusivu.php");
	exit();
}
//tarkastetaan onko tultu toimittajat sivulta
$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : null;
$valmistaja = hae_valmistajan_tiedot( $db, $brandId );
if(!$valmistaja){
	header("Location:toimittajat.php");
	exit();
}
//Tulostetaan logo, nimi ja painikkeet
$brandAddress = getAmBrandAddress($brandId)[0];
$logo_src = TECDOC_THUMB_URL . $brandAddress->logoDocId . "/";
?>
<br>
<div class="otsikko"><img src="<?= $logo_src?>" style="vertical-align: middle; padding-right: 20px; display:inline-block;" /><h2 style="display:inline-block; vertical-align:middle;"><?= $valmistaja->brandName?></h2></div>
<div id="painikkeet">
	<a href="lisaa_tuotteita.php?brandId=<?= $brandId?>&brandName=<?= $valmistaja->brandName?>"><span class="nappi">Lisää tuotteita</span></a>
	<a href="yp_valikoima.php?brand=<?=$brandId?>"><span class="nappi">Valikoima</span></a>
</div>

<br>
<p style="margin-left: 5%;">ID: <?= $valmistaja->valmistajan_id?></p>
<br>
<div style="text-align: center; display:inline-block; margin-left: 5%;">
<?php

/**
 * Hakee valmistajan tiedot.
 * @param DByhteys $db
 * @param int $brandId,	Tecdocista saatava ID
 * @return array|bool|stdClass	Palauttaa valmistajan tiedot, jos löytyi. Muuten false.
 */
function hae_valmistajan_tiedot( DByhteys $db, $brandId ){
	$query = "SELECT brandName, LPAD(`valmistajan_id`,3,'0') AS valmistajan_id FROM valmistaja WHERE brandId = ? ";
	return $db->query($query, [$brandId], NULL, PDO::FETCH_OBJ);
}

/**
 * @param DByhteys $db
 * @return array|bool|stdClass	Palauttaa hankintapaikkojen nimet, jos löytyi. Muuten false.
 */
function hae_kaikki_hankintapaikat( DByhteys $db ) {
    $query = "SELECT id, nimi FROM hankintapaikka";
    return $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);
}

/**
 * @param $brandAddress
 */
function tulosta_yhteystiedot($brandAddress){

	echo '<div style="float:left; padding-right: 150px;">';
	echo '<table>';
	echo "<th colspan='2' class='text-center'>Yhteystiedot</th>";
	echo '<tr><td>Yritys</td><td>'. $brandAddress->name .'</td></tr>';
	echo '<tr><td>Osoite</td><td>'. $brandAddress->street . '<br>' . $brandAddress->zip . " " . strtoupper($brandAddress->city) .'</td></tr>';
	echo '<tr><td>Puh</td><td>'. $brandAddress->phone .'</td></tr>';
	if(isset($brandAddress->fax)) echo '<tr><td>Fax</td><td>'. $brandAddress->fax .'</td></tr>';
	if(isset($brandAddress->email)) echo '<tr><td>Email</td><td>'. $brandAddress->email .'</td></tr>';
	echo '<tr><td>URL</td><td>'. $brandAddress->wwwURL .'</td></tr>';
	echo '</table>';
	echo '</div>';


}

/**
 * Tallentaa uuden hankintapaikan tietokantaan.
 * @param $nimi
 * @param $katuosoite
 * @param $postinumero
 * @param $kaupunki
 * @param $maa
 * @param $puhelin
 * @param $fax
 * @param $URL
 * @param $yhteyshenkilo
 * @param $yhteyshenkilo_puhelin
 * @param $yhteyshenkilo_sahkoposti
 * @return mixed
 */
function tallenna_uusi_hankintapaikka($nimi, $katuosoite, $postinumero,
                                      $kaupunki, $maa, $puhelin, $fax, $URL, $yhteyshenkilo,
                                      $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti){
	global $db;
	$table_name = "hankintapaikka";
	$query = "	INSERT IGNORE INTO $table_name (nimi, katuosoite, postinumero, 
										  kaupunki, maa, puhelin, yhteyshenkilo_nimi, yhteyshenkilo_puhelin,
										  yhteyshenkilo_email, fax, www_url)
				VALUES ( ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? )";
	$db->query($query, [$nimi, $katuosoite, $postinumero, $kaupunki, $maa,
									$puhelin, $yhteyshenkilo, $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti, $fax, $URL]);

	$result = $db->query("SELECT LAST_INSERT_ID() AS id", [], NULL, PDO::FETCH_OBJ);

	//palauttaa id, jos lisätty. Jos jo olemassa, palauttaa 0.
	return $result->id;
}

/**
 * Positaa hankintapaikan tietokannasta ja poistaa linkitykset
 * hankintapaikan ja valmistajien väliltä.
 * @param $hankintapaikka_id int, Hankintapaikan Id
 */
function poista_hankintapaikka( /* int */ $hankintapaikka_id){
    global $db;
    //Poistetaan hankintapaikka.
    $query = "DELETE FROM hankintapaikka WHERE id = ? ";
    $db->query($query, [$hankintapaikka_id]);

    //Poistetaan linkitykset hankintapaikan ja yrityksen välillä.
    $query = "UPDATE valmistaja SET hankintapaikka_id = 0 WHERE hankintapaikka_id = ? ";
    $db->query($query, [$hankintapaikka_id]);
	return;
}


/**
 * Linkittää hankintapaikan valmistajaan.
 * @param int $brandId, valmistajan ID.
 * @param int $hankintapaikkaId, hankintapaikan ID.
 */
function linkita_valmistaja_hankintapaikkaan( /* int */ $brandId, /* int */ $hankintapaikkaId) {
	global $db;
	$query = "	UPDATE valmistaja
				SET hankintapaikka_id = ? 
				WHERE brandId = ? ";
	$db->query($query, [$hankintapaikkaId, $brandId]);
	return;
}

/**
 * @param $brandId
 */
function tulosta_hankintapaikka( DByhteys $db, /* int */ $brandId) {

	//tarkastetaan onko valmistajaan linkitetty hankintapaikka
	$query = "	SELECT * FROM valmistaja
 				JOIN hankintapaikka
 					ON valmistaja.hankintapaikka_id = hankintapaikka.id
 				WHERE valmistaja.brandId = ? ";
	$hankintapaikka = $db->query($query, [$brandId], NULL, PDO::FETCH_OBJ);
	if ( $hankintapaikka ) : ?>

		<div style="float:left; padding-right: 100px;">
			<table>
				<tr><th colspan='2' class='text-center'>Hankintapaikka</th></tr>
				<tr><td>Yritys</td><td><?= $hankintapaikka->nimi?></td></tr>
				<tr><td>Osoite</td><td><?= $hankintapaikka->katuosoite?><br><?= $hankintapaikka->postinumero, " ", $hankintapaikka->kaupunki?></td></tr>
                <tr><td>Maa</td><td><?= $hankintapaikka->maa?></td></tr>
                <tr><td>Puh</td><td><?= $hankintapaikka->puhelin?></td></tr>
				<tr><td>Fax</td><td><?= $hankintapaikka->fax?></td></tr>
				<tr><td>URL</td><td><?= $hankintapaikka->www_url?></td></tr>
				<tr><th colspan='2' class='text-center'>Yhteyshenkilö</th></tr>
				<tr><td>Nimi</td><td><?= $hankintapaikka->yhteyshenkilo_nimi?></td></tr>
				<tr><td>Puh</td><td><?= $hankintapaikka->yhteyshenkilo_puhelin?></td></tr>
				<tr><td>Email</td><td><?= $hankintapaikka->yhteyshenkilo_email?></td></tr>

			</table>
			<br>
			<input class="nappi" type="button" value="Vaihda hankintapaikka" onClick="avaa_Modal_toimittaja_yhteystiedot('.$brandId.')">
		</div>

	<?php else : ?>
		<div style="float:left;">
			<p>Valitse hankintapaikka!</p>
			<input class="nappi" type="button" value="Vaihda hankintapaikka" onClick="avaa_Modal_toimittaja_yhteystiedot('.$brandId.')">
		</div>
	<?php endif;
}



if ( isset($_POST['lisaa']) ) {
	$id = tallenna_uusi_hankintapaikka($_POST['nimi'], $_POST['katuosoite'], $_POST['postinumero'],
								$_POST['kaupunki'], $_POST['maa'],
								$_POST['puh'], $_POST['fax'], $_POST['url'], $_POST['yhteyshenkilo_nimi'], $_POST['yhteyshenkilo_puhelin'],
								$_POST['yhteyshenkilo_email']);
	if ( $id ){
		linkita_valmistaja_hankintapaikkaan($brandId, $id);
	}
	else echo "<div class='error'>Hankintapaikka on jo olemassa!</div><br>";
}

elseif ( isset($_POST['poista']) ) {
    poista_hankintapaikka($_POST['hankintapaikka']);
}

elseif( isset($_POST['valitse']) ) {
    linkita_valmistaja_hankintapaikkaan($brandId, $_POST['hankintapaikka']);
}


tulosta_yhteystiedot($brandAddress);
tulosta_hankintapaikka($db, $brandId);

//Haetaan kaikki hankintapaikat valmiiksi hankintapaikka -modalia varten varten
$hankintapaikat = hae_kaikki_hankintapaikat( $db );


?>
</div>

<script>
	//
	// Avataan modal, jossa voi täyttää uuden toimittajan yhteystiedot
	// tai valita jo olemassa olevista
	//
	function avaa_Modal_toimittaja_yhteystiedot(brandId){
		Modal.open( {
			content:  '\
				<div>\
				<h4>Anna uuden hankintapaikan tiedot tai valitse listasta.</h4>\
				<br>\
				<form action="" method="post" id="valitse_hankintapaikka">\
				<label><span>Hankintapaikat</span></label>\
					<select name="hankintapaikka" id="hankintapaikka">\
						<option value="0">-- Hankintapaikka --</option>\
						<?php foreach ($hankintapaikat as $hankintapaikka) : ?> \
                                <option value="<?= $hankintapaikka->id?>"><?= $hankintapaikka->nimi?></option> \
                        <?php endforeach; ?> \
					</select>\
				<br>\
				<input class="nappi" type="submit" name="valitse" value="Valitse"> \
				<input class="nappi" type="submit" name="poista" value="Poista" style="background:#d20006; border-color:#b70004;"> \
				<input type="hidden" name="brandId" value="'+brandId+'">\
				</form>\
				<hr>\
				<form action="" method="post">\
					\
					<label><span>Yritys</span></label>\
					<input name="nimi" type="text" placeholder="Yritys Oy" title="" required>\
					<br><br>\
					<label><span>Katuosoite</span></label>\
					<input name="katuosoite" type="text" placeholder="Katu" title="">\
					<br><br>\
					<label><span>Postiumero</span></label>\
					<input name="postinumero" type="text" pattern="[0-9]{1,20}" placeholder="00000">\
					<br><br>\
					<label><span>Kaupunki</span></label>\
					<input name="kaupunki" type="text" pattern=".{1,50}" placeholder="KAUPUNKI">\
					<br><br>\
					<label><span>Maa</span></label>\
					<input name="maa" type="text" pattern=".{1,50}" placeholder="Maa">\
					<br><br>\
					<label><span>Puh</span></label>\
					<input name="puh" type="text" pattern=".{8,10}" placeholder="040 123 4567">\
					<br><br>\
					<label><span>Fax</span></label>\
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01 234567">\
					<br><br>\
					<label><span>URL</span></label>\
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi">\
					<br><br>\
					<label><span>Yhteyshenkilö</span></label>\
					<input name="yhteyshenkilo_nimi" type="text" pattern=".{1,50}" placeholder="Etunimi Sukunimi">\
					<br><br>\
					<label><span>Yhteyshenk. puh.</span></label>\
					<input name="yhteyshenkilo_puhelin" type="text" pattern=".{1,50}" placeholder="040 123 4567">\
					<br><br>\
					<label><span>Yhteyshenk. email</span></label>\
					<input name="yhteyshenkilo_email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi">\
					<br><br>\
					<label><span>Tilaustapa</span></label>\
					<input name="tilaustapa" type="text" pattern=".{1,50}">\
					<br><br>\
					<input class="nappi" type="submit" name="lisaa" value="Tallenna"> \
					<input type="hidden" name="brandId" value="'+brandId+'">\
				</form>\
				</div>\
				',
			draggable: true
		} );
	}

    $(document).ready(function() {
        $(document.body)

            //Estetään valitsemasta hankintapaikaksi labelia
            .on('submit', '#valitse_hankintapaikka', function(e){
                var hankintapaikka = document.getElementById("hankintapaikka");
                var id = parseInt(hankintapaikka.options[hankintapaikka.selectedIndex].value);
                if (id == 0) {
                    e.preventDefault();
                    return false;
                }
            });
    });
	
</script>
</body>
</html>






