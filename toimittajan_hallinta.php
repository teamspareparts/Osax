<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}
//tarkastetaan onko tultu toimittajat sivulta
$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : null;
$brandName = tarkasta_onko_oikea_brand($brandId);
if (!($brandName)) {
	header("Location:toimittajat.php");
	exit();
}


/** Tarkastetaan onko brändi aktivoituna tecdocissa ja samalla haetaan brandin nimi.
 * @param $brandId
 * @return bool
 */
function tarkasta_onko_oikea_brand(/*int*/ $brandId){
	$brands = getAmBrands();
	foreach ( $brands as $brand ) {
		if( $brand->brandId == $brandId ) {
		    return $brand->brandName;
        }
	}
	return false;
}



/**
 * Hakee kaikki hankintapaikat.
 * @param DByhteys $db
 * @return array|bool|stdClass	Palauttaa hankintapaikkojen nimet, jos löytyi. Muuten false.
 */
function hae_kaikki_hankintapaikat( DByhteys $db ) {
	$query = "SELECT id, nimi, LPAD(`id`,3,'0') AS hankintapaikka_id FROM hankintapaikka";
	return $db->query($query, [], FETCH_ALL);
}


/**
 * Tallentaa uuden hankintapaikan tietokantaan.
 * @param DByhteys $db
 * @param $id
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
 * @param $tilaustapa
 */
function tallenna_uusi_hankintapaikka(DByhteys $db, $id, $nimi, $katuosoite, $postinumero,
									  $kaupunki, $maa, $puhelin, $fax, $URL, $yhteyshenkilo,
									  $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti, $tilaustapa){
	$query = "	INSERT IGNORE INTO hankintapaikka (id, nimi, katuosoite, postinumero, 
										  kaupunki, maa, puhelin, yhteyshenkilo_nimi, yhteyshenkilo_puhelin,
										  yhteyshenkilo_email, fax, www_url, tilaustapa)
				VALUES ( ?, ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ?, ? )";
	$db->query($query, [$id, $nimi, $katuosoite, $postinumero, $kaupunki, $maa,
		$puhelin, $yhteyshenkilo, $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti, $fax, $URL, $tilaustapa]);
}

/**
 * Muokkaa hankintapaikan tietoja.
 * @param DByhteys $db
 * @param $yritys
 * @param $hankintapaikka_id
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
 * @param $tilaustapa
 */
function muokkaa_hankintapaikkaa(DByhteys $db, $yritys, $hankintapaikka_id, $katuosoite, $postinumero,
								 $kaupunki, $maa, $puhelin, $fax, $URL, $yhteyshenkilo,
								 $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti, $tilaustapa){
	$query = "	UPDATE IGNORE hankintapaikka 
				SET 	nimi = ?, katuosoite = ?, postinumero = ?, 
			  			kaupunki = ? , maa = ?, puhelin = ?, yhteyshenkilo_nimi = ?,
			  			yhteyshenkilo_puhelin = ?, yhteyshenkilo_email = ?, fax = ?,
			  			www_url = ?, tilaustapa = ?
				WHERE 	id = ?";
	$db->query($query, [$yritys, $katuosoite, $postinumero, $kaupunki, $maa,
		$puhelin, $yhteyshenkilo, $yhteyshenkilo_puhelin, $yhteyshenkilo_sahkoposti, $fax, $URL, $tilaustapa, $hankintapaikka_id,]);
}


/**
 * Poistaa hankintapaikan, jos ei linkityksiä valmistajiin.
 * @param DByhteys $db
 * @param $hankintapaikka_id
 * @return array|bool
 */
function poista_hankintapaikka( DByhteys $db, $hankintapaikka_id){
	//Tarkastetaan onko linkityksiä tuotteisiin...
	$query = "SELECT * FROM tuote where hankintapaikka_id = ? ";
	$linkitykset = $db->query($query, [$hankintapaikka_id], FETCH_ALL);
	if ( count($linkitykset) > 0 ) { return false; }

	//Poistetaan linkitykset hankintapaikkaan
	$query = "DELETE FROM valmistajan_hankintapaikka WHERE hankintapaikka_id = ? ";
	$db->query($query, [$hankintapaikka_id]);
	//Poistetaan hankintapaikka
	$query = "DELETE FROM hankintapaikka WHERE id = ? ";
	$db->query($query, [$hankintapaikka_id]);
	return true;
}

/** Poistaa linkityksen valmistajan ja hankintapaikan väliltä.
 * @param DByhteys $db
 * @param $hankintapaikka_id
 * @param $brandId
 * @return bool
 */
function poista_hankintapaikka_linkitys( DByhteys $db, /*int*/ $hankintapaikka_id, /*int*/ $brandId){
	//Poistetaan linkitykset hankintapaikan ja yrityksen välillä.
	$query = "DELETE FROM valmistajan_hankintapaikka WHERE hankintapaikka_id = ? AND brandId = ? ";
	return $db->query($query, [$hankintapaikka_id, $brandId]);
}


/**
 * Linkitetään valmistaja hankintapaikkaan
 * @param DByhteys $db
 * @param $brandId
 * @param $hankintapaikkaId
 * @param $brandName
 * @return array|bool|stdClass
 */
function linkita_valmistaja_hankintapaikkaan( DByhteys $db, /*int*/ $brandId, /*int*/ $hankintapaikkaId, /*String*/ $brandName) {
	$query = "	INSERT IGNORE INTO valmistajan_hankintapaikka
				(brandId, hankintapaikka_id, brandName)
				VALUES ( ?, ?, ?)";
	return $db->query($query, [$brandId, $hankintapaikkaId, $brandName]);
}

/**
 * Tulostetaan kaikki valmistajan hankintapaikat HTML:nä
 * @param DByhteys $db
 * @param $brandId
 */
function tulosta_hankintapaikat( DByhteys $db, /* int */ $brandId) {

	//tarkastetaan onko valmistajaan linkitetty hankintapaikka
	$query = "	SELECT *, LPAD(`id`,3,'0') AS id FROM valmistajan_hankintapaikka
 				JOIN hankintapaikka
 					ON valmistajan_hankintapaikka.hankintapaikka_id = hankintapaikka.id
 				WHERE valmistajan_hankintapaikka.brandId = ? ";
	$hankintapaikat = $db->query($query, [$brandId], FETCH_ALL);
	$i = 1;
	if (isset($hankintapaikat)) {
		foreach( $hankintapaikat as $hankintapaikka ) : ?>

            <form action="" method="post" class="poista_hankintapaikka_linkitys">
                <table style="float:left; padding-right: 30pt;">
                    <tr><th colspan='2' class='text-center'>Hankintapaikka <?=$i++?></th></tr>
                    <tr><td>ID</td><td><?= $hankintapaikka->id?></td></tr>
                    <tr><td>Yritys</td><td><?= $hankintapaikka->nimi?></td></tr>
                    <tr><td>Osoite</td><td><?= $hankintapaikka->katuosoite?><br><?= $hankintapaikka->postinumero, " ", $hankintapaikka->kaupunki?></td></tr>
                    <tr><td>Maa</td><td><?= $hankintapaikka->maa?></td></tr>
                    <tr><td>Puh</td><td><?= $hankintapaikka->puhelin?></td></tr>
                    <tr><td>Fax</td><td><?= $hankintapaikka->fax?></td></tr>
                    <tr><td>URL</td><td><?= $hankintapaikka->www_url?></td></tr>
                    <tr><td>Tilaustapa</td><td><?= $hankintapaikka->tilaustapa?></td></tr>
                    <tr><th colspan='2' class='text-center'>Yhteyshenkilö</th></tr>
                    <tr><td>Nimi</td><td><?= $hankintapaikka->yhteyshenkilo_nimi?></td></tr>
                    <tr><td>Puh</td><td><?= $hankintapaikka->yhteyshenkilo_puhelin?></td></tr>
                    <tr><td>Email</td><td><?= $hankintapaikka->yhteyshenkilo_email?></td></tr>
                    <tr>
                        <td colspan="2">
                            <input name="hankintapaikka_id" type="hidden" value="<?=$hankintapaikka->id?>">
                            <input name="poista_linkitys" class="nappi" type="submit" value="Poista" style="background:#d20006; border-color:#b70004;">
                            <span onclick="avaa_modal_muokkaa_hankintapaikka('<?=$hankintapaikka->id?>', '<?=$hankintapaikka->nimi?>',
                                '<?=$hankintapaikka->katuosoite?>','<?=$hankintapaikka->postinumero?>','<?=$hankintapaikka->kaupunki?>',
                                '<?=$hankintapaikka->maa?>','<?=$hankintapaikka->puhelin?>','<?=$hankintapaikka->fax?>',
                                '<?=$hankintapaikka->www_url?>', '<?=$hankintapaikka->yhteyshenkilo_nimi?>', '<?=$hankintapaikka->yhteyshenkilo_puhelin?>',
                                '<?=$hankintapaikka->yhteyshenkilo_email?>', '<?=$hankintapaikka->tilaustapa?>')" class="nappi">Muokkaa</span>
                        </td>
                    </tr>
					<tr>
						<td colspan="2">
							<a href="yp_lisaa_tuotteita.php?brandId=<?=$brandId?>&hankintapaikka=<?=intval($hankintapaikka->id)?>" class="nappi">Lisää tuotteita</a>
							<a href="yp_valikoima.php?brand=<?=$brandId?>&hankintapaikka=<?=intval($hankintapaikka->id)?>" class="nappi">Valikoima</a>
						</td>
					</tr>
				</table>

            </form>
        <?php endforeach;
	}
}


if ( isset($_POST['lisaa']) ) {
	tallenna_uusi_hankintapaikka($db, $_POST['hankintapaikka_id'], $_POST['nimi'], $_POST['katuosoite'], $_POST['postinumero'],
		$_POST['kaupunki'], $_POST['maa'],
		$_POST['puh'], $_POST['fax'], $_POST['url'], $_POST['yhteyshenkilo_nimi'], $_POST['yhteyshenkilo_puhelin'],
		$_POST['yhteyshenkilo_email'], $_POST['tilaustapa']);
	linkita_valmistaja_hankintapaikkaan($db, $brandId, $_POST['hankintapaikka_id'], $brandName);
}

elseif ( isset($_POST['poista_linkitys']) ) {
	poista_hankintapaikka_linkitys($db, $_POST['hankintapaikka_id'], $brandId);
}

elseif( isset($_POST['valitse']) ) {
	linkita_valmistaja_hankintapaikkaan($db, $brandId, $_POST['hankintapaikka'], $brandName);
}
elseif( isset($_POST['muokkaa']) ) {
	muokkaa_hankintapaikkaa($db, $_POST['yritys'], $_POST['hankintapaikka_id'], $_POST['katuosoite'],
        $_POST['postinumero'], $_POST['kaupunki'], $_POST['maa'],
		$_POST['puh'], $_POST['fax'], $_POST['url'], $_POST['yhteyshenkilo_nimi'],
        $_POST['yhteyshenkilo_puhelin'], $_POST['yhteyshenkilo_email'], $_POST['tilaustapa']);
}
elseif( isset($_POST['poista'])){
	if ( !poista_hankintapaikka($db, $_POST['hankintapaikka']) ) {
		$_SESSION["feedback"] = "<p class='error'>Hankintapaikkaa ei voitu poistaa, koska siihen on linkitetty tuotteita!</p>";
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if (!empty($_POST)) {
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}


//Haetaan brändin yhteystiedot ja logon URL
$brandAddress = getAmBrandAddress($brandId)[0];
$logo_src = TECDOC_THUMB_URL . $brandAddress->logoDocId . "/";
//Haetaan kaikki hankintapaikat valmiiksi hankintapaikka -modalia varten varten
$hankintapaikat = hae_kaikki_hankintapaikat( $db );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="js/jsmodal-1.0d.min.js"></script>
    <title>Toimittajat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
    <?=$feedback?>
    <div class="otsikko"><img src="<?= $logo_src?>" style="vertical-align: middle; padding-right: 20px; display:inline-block;"><h2 style="display:inline-block; vertical-align:middle;"><?= $brandName?></h2></div>
    <div id="painikkeet">
	    <input class="nappi" type="button" value="Uusi hankintapaikka" onClick="avaa_modal_uusi_hankintapaikka('<?=$brandId?>')">
    </div>
    <br><br>
    <!-- Hankintapaikan yhteystiedot -->
    <table style="float:left; padding-right: 150px;">
        <thead>
        <tr><th colspan='2' class='text-center'>Yhteystiedot</th></tr>
        </thead>
        <tbody>
        <tr><td>Yritys</td><td><?=$brandAddress->name?></td></tr>
        <tr><td>Osoite</td><td><?=$brandAddress->street?><br><?=$brandAddress->zip?> <?=strtoupper($brandAddress->city)?></td></tr>
        <tr><td>Puh</td><td><?=$brandAddress->phone?></td></tr>
        <?php if (isset($brandAddress->fax)) : ?>
            <tr><td>Fax</td><td><?$brandAddress->fax?></td></tr>
        <?php endif; if(isset($brandAddress->email)) : ?>
            <tr><td>Email</td><td><?=$brandAddress->email?></td></tr>
        <?php endif; ?>
        <tr><td>URL</td><td><?=$brandAddress->wwwURL?></td></tr>
        </tbody>
    </table>

    <?php tulosta_hankintapaikat($db, $brandId); ?>

</main>

<script>
	//
	// Avataan modal, jossa voi täyttää uuden toimittajan yhteystiedot
	// tai valita jo olemassa olevista.
	//
	function avaa_modal_uusi_hankintapaikka(brandId){
		Modal.open( {
			content:  '\
				<div>\
				<h4>Anna uuden hankintapaikan tiedot tai valitse listasta.</h4>\
				<br>\
				<form action="" method="post" id="valitse_hankintapaikka">\
				<label><span>Hankintapaikat</span></label>\
					<select name="hankintapaikka" id="hankintapaikka">\
						<option value="0">-- Hankintapaikka --</option>\
					</select>\
				<br>\
				<input class="nappi" type="submit" name="valitse" value="Valitse"> \
				<input class="nappi" type="submit" name="poista" value="Poista" style="background:#d20006; border-color:#b70004;"> \
				<input type="hidden" name="brandId" value="'+brandId+'">\
				</form>\
				<hr>\
				<form action="" method="post" name="uusi_hankintapaikka" id="uusi_hankintapaikka">\
					\
					<label><span>ID</span></label>\
					<input name="hankintapaikka_id" type="text" placeholder="000" title="Numero väliltä 001-999" pattern="00[1-9]|0[1-9][0-9]|[1-9][0-9]{2}" required>\
					<br><br>\
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
					<input name="puh" type="text" pattern=".{5,15}" placeholder="040 123 4567">\
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
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_hankintapaikka"> \
					</form>\
				</div>\
				',
			draggable: true
		} );

        let hankintapaikka_lista, hankintapaikka, i;
        let hankintapaikat = [];
        hankintapaikat = <?php echo json_encode($hankintapaikat);?>;
        //Täytetään Select-Option
        hankintapaikka_lista = document.getElementById("hankintapaikka");
        for (i = 0; i < hankintapaikat.length; i++) {
            hankintapaikka = new Option(hankintapaikat[i].hankintapaikka_id+" - "+hankintapaikat[i].nimi, hankintapaikat[i].id);
            hankintapaikka_lista.options.add(hankintapaikka);
        }

	}

	//
    //Modal, jossa voi muokata hankintapaikan tietoja.
    //
	function avaa_modal_muokkaa_hankintapaikka(hankintapaikka_id, yritys, katuosoite, postinumero, postitoimipaikka,
	maa, puhelin, fax, www_url, yhteyshenkilo_nimi, yhteyshenkilo_puhelin, yhteyshenkilo_email, tilaustapa){
		Modal.open( {
			content:  '\
				<div>\
				<h4>Muokkaaminen muuttaa hankintapaikan tietoja <br> myös muilta brändeiltä!</h4>\
				<br>\
				<hr>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					\
					<label><span>ID</span></label>\
					<h5 style="display: inline">'+hankintapaikka_id+'</h5>\
					<br><br>\
					<label><span>Hankintapaikka</span></label>\
					<input name="yritys" type="text" placeholder="Nimi" value="'+yritys+'">\
					<br><br>\
					<label><span>Katuosoite</span></label>\
					<input name="katuosoite" type="text" placeholder="Katu" value="'+katuosoite+'">\
					<br><br>\
					<label><span>Postiumero</span></label>\
					<input name="postinumero" type="text" pattern="[0-9]{1,20}" placeholder="00000" value="'+postinumero+'">\
					<br><br>\
					<label><span>Kaupunki</span></label>\
					<input name="kaupunki" type="text" pattern=".{1,50}" placeholder="KAUPUNKI" value="'+postitoimipaikka+'">\
					<br><br>\
					<label><span>Maa</span></label>\
					<input name="maa" type="text" pattern=".{1,50}" placeholder="Maa" value="'+maa+'">\
					<br><br>\
					<label><span>Puh</span></label>\
					<input name="puh" type="text" pattern=".{5,15}" placeholder="040 123 4567" value="'+puhelin+'">\
					<br><br>\
					<label><span>Fax</span></label>\
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01234567" value="'+fax+'">\
					<br><br>\
					<label><span>URL</span></label>\
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi" value="'+www_url+'">\
					<br><br>\
					<label><span>Yhteyshenkilö</span></label>\
					<input name="yhteyshenkilo_nimi" type="text" pattern=".{1,50}" placeholder="Etunimi Sukunimi" value="'+yhteyshenkilo_nimi+'">\
					<br><br>\
					<label><span>Yhteyshenk. puh.</span></label>\
					<input name="yhteyshenkilo_puhelin" type="text" pattern=".{1,50}" placeholder="040 123 4567" value="'+yhteyshenkilo_puhelin+'">\
					<br><br>\
					<label><span>Yhteyshenk. email</span></label>\
					<input name="yhteyshenkilo_email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi" value="'+yhteyshenkilo_email+'">\
					<br><br>\
					<label><span>Tilaustapa</span></label>\
					<input name="tilaustapa" type="text" pattern=".{1,50}" value="'+tilaustapa+'">\
					<br><br>\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
					<input name="hankintapaikka_id" type="hidden" value="'+hankintapaikka_id+'">\
				</form>\
				</div>\
				',
			draggable: true
		});
	}

    $(document).ready(function() {
        $(document.body)


            .on('submit', '#valitse_hankintapaikka', function(e){
				//Estetään valitsemasta hankintapaikaksi labelia
                let hankintapaikka = document.getElementById("hankintapaikka");
                let id = parseInt(hankintapaikka.options[hankintapaikka.selectedIndex].value);
                if (id == 0) {
                    e.preventDefault();
                    return false;
                }
            })
			//Estetään valitsemasta jo olemassa olevaa hankintapikka ID:tä ja nimeä
			.on('submit', '#uusi_hankintapaikka', function(e) {
			    let id, nimi, i;
			    let hankintapaikat = [];
				hankintapaikat = <?php echo json_encode($hankintapaikat); ?>;
				//Tarkastetaan onko ID tai nimi varattu
				id = document.getElementById("uusi_hankintapaikka").elements["hankintapaikka_id"].value;
				nimi = document.getElementById("uusi_hankintapaikka").elements["nimi"].value;
				if (hankintapaikat.length > 0) {
					for (i = 0; i < hankintapaikat.length; i++) {
						if (hankintapaikat[i].id == id) {
							alert("ID on varattu.");
							e.preventDefault();
							return false;
						}
						if (hankintapaikat[i].nimi == nimi) {
							alert("Nimi on varattu.");
							e.preventDefault();
							return false;
						}
					}
				}
			});
		$('.poista_hankintapaikka_linkitys').submit(function (e) {
			let c = confirm("Haluatko varmasti poistaa hankintapaikan kyseiseltä brändiltä?");
			if (c == false) {
				e.preventDefault();
				return false;
			}
		});
    });
	
</script>
</body>
</html>






