<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/**
 * Tallentaa uuden hankintapaikan tietokantaan.
 * @param DByhteys $db
 * @param array $arr
 */
function tallenna_uusi_hankintapaikka(DByhteys $db, array $arr){
	$query = "  INSERT IGNORE INTO hankintapaikka (id, nimi, katuosoite, postinumero, 
				                          kaupunki, maa, puhelin, fax, www_url, yhteyshenkilo_nimi, 
										  yhteyshenkilo_puhelin, yhteyshenkilo_email, tilaustapa)
				VALUES ( ?, ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ?, ? )";
	return $db->query( $query, $arr );
}

/**
 * Muokkaa hankintapaikan tietoja.
 * @param DByhteys $db
 * @param array $arr
 */
function muokkaa_hankintapaikkaa(DByhteys $db, array $arr){
	$query = "	UPDATE  IGNORE hankintapaikka 
				SET 	nimi = ?, katuosoite = ?, postinumero = ?, 
			  			kaupunki = ? , maa = ?, puhelin = ?, fax = ?, www_url = ?,
			  			yhteyshenkilo_nimi = ?, yhteyshenkilo_puhelin = ?,
			  			yhteyshenkilo_email = ?, tilaustapa = ?
				WHERE 	id = ?";
	$db->query( $query, $arr );
}


/**
 * Poistaa hankintapaikan ja linkitykset brändeihin.
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return array|bool
 */
function poista_hankintapaikka( DByhteys $db, /*int*/ $hankintapaikka_id){
	//Tarkastetaan onko linkityksiä tuotteisiin...
	$query = "SELECT id FROM tuote where hankintapaikka_id = ? ";
	$linkitykset = $db->query($query, [$hankintapaikka_id]);
	if ( $linkitykset ) {
		return false;
	}
	//Poistetaan brändien linkitykset hankintapaikkaan
	$query = "DELETE FROM brandin_linkitys WHERE hankintapaikka_id = ? ";
	$db->query($query, [$hankintapaikka_id]);

	//Poistetaan hankintapaikka
	$query = "DELETE FROM hankintapaikka WHERE id = ? ";
	$db->query($query, [$hankintapaikka_id]);
	return true;
}


//Haetaan kaikki hankintapaikat, joihin linkitetty valmistaja

$sql = "SELECT hankintapaikka.*, LPAD(hankintapaikka.id,3,'0') AS hankintapaikka_id,
			brandin_linkitys.brandi_id, GROUP_CONCAT(brandi.nimi) AS brandit
        FROM hankintapaikka
        LEFT JOIN brandin_linkitys
          ON hankintapaikka.id = brandin_linkitys.hankintapaikka_id
        LEFT JOIN brandi
          ON brandin_linkitys.brandi_id = brandi.id
        GROUP BY hankintapaikka.id";
$hankintapaikat = $db->query($sql, [], FETCH_ALL);

if ( isset($_POST['lisaa']) ) {
	$arr = [
		$_POST['hankintapaikka_id'],
		$_POST['nimi'],
		$_POST['katuosoite'],
		$_POST['postinumero'],
		$_POST['kaupunki'],
		$_POST['maa'],
		$_POST['puh'],
		$_POST['fax'],
		$_POST['url'],
		$_POST['yhteyshenkilo_nimi'],
		$_POST['yhteyshenkilo_puhelin'],
		$_POST['yhteyshenkilo_email'],
		$_POST['tilaustapa'] ];
	if ( tallenna_uusi_hankintapaikka($db, $arr) ) {
		$_SESSION['feedback'] = "<p class='success'>Hankintapaikka lisätty.</p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>ID tai tunniste varattu.</p>";

	}
}
elseif( isset($_POST['muokkaa']) ) {
	$arr = [
		$_POST['yritys'],
		$_POST['katuosoite'],
		$_POST['postinumero'],
		$_POST['kaupunki'],
		$_POST['maa'],
		$_POST['puh'],
		$_POST['fax'],
		$_POST['url'],
		$_POST['yhteyshenkilo_nimi'],
		$_POST['yhteyshenkilo_puhelin'],
		$_POST['yhteyshenkilo_email'],
		$_POST['tilaustapa'],
		$_POST['hankintapaikka_id'],
	];
	muokkaa_hankintapaikkaa($db, $arr);

}
elseif( isset($_POST['poista'])){
	if ( poista_hankintapaikka($db, $_POST['hankintapaikka_id']) ) {
		$_SESSION["feedback"] = "<p class='success'>Hankintapaikka poistettu.</p>";
	} else {
		$_SESSION["feedback"] = "<p class='error'>Hankintapaikkaa ei voitu poistaa, koska siihen on linkitetty tuotteita!</p>";
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION["feedback"]);


?>


<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko">Hankintapaikat</h1>
		<div id="painikkeet">
			<button class="nappi" onClick="avaa_modal_uusi_hankintapaikka()">Uusi hankintapaikka</button>
		</div>
	</section>
	<?=$feedback?>
	<?php if ( $hankintapaikat ) : ?>
		<table>
			<thead>
			<tr><th>ID</th>
				<th>Nimi</th>
				<th>Brändit</th>
				<th>Osoite</th>
				<th>Yhteystiedot</th>
				<th>Tilaustapa</th>
				<th>Yhteyshenkilö</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( isset($hankintapaikat) ) : ?>
			<?php foreach( $hankintapaikat as $hankintapaikka ) :
					$hankintapaikka->brandit = explode(',', $hankintapaikka->brandit)?>
				<tr>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">
						<?= $hankintapaikka->hankintapaikka_id?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">
						<?= $hankintapaikka->nimi?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>" class="nowrap">
						<?php foreach ($hankintapaikka->brandit as $brand) : ?>
							<?=$brand?><br>
						<?php endforeach;?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>" class="nowrap">
						<?= $hankintapaikka->katuosoite?><br>
						<?= $hankintapaikka->postinumero, " ", $hankintapaikka->kaupunki?><br>
						<?= $hankintapaikka->maa?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">
						p. <?= $hankintapaikka->puhelin?><br>
						fax. <?= $hankintapaikka->fax?><br>
						<?= $hankintapaikka->www_url?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">
						<?= $hankintapaikka->tilaustapa?></td>
					<td data-href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">
						<?= $hankintapaikka->yhteyshenkilo_nimi?><br>
						<?= $hankintapaikka->yhteyshenkilo_puhelin?><br>
						<?= $hankintapaikka->yhteyshenkilo_email?></td>
					<td>
						<button onclick="poista_hankintapaikka(<?=$hankintapaikka->id?>)" class="nappi red">Poista</button>
						<br>
						<button onclick="avaa_modal_muokkaa_hankintapaikka('<?=$hankintapaikka->id?>', '<?=$hankintapaikka->nimi?>','<?=$hankintapaikka->katuosoite?>','<?=$hankintapaikka->postinumero?>','<?=$hankintapaikka->kaupunki?>',
							'<?=$hankintapaikka->maa?>','<?=$hankintapaikka->puhelin?>','<?=$hankintapaikka->fax?>',
							'<?=$hankintapaikka->www_url?>', '<?=$hankintapaikka->yhteyshenkilo_nimi?>', '<?=$hankintapaikka->yhteyshenkilo_puhelin?>',
							'<?=$hankintapaikka->yhteyshenkilo_email?>', '<?=$hankintapaikka->tilaustapa?>')" class="nappi">Muokkaa</button></td>
				</tr>
			<?php endforeach; else:?>
				<h4>Ei hankintapaikkoja</h4>
			<?php endif;?>
			</tbody>
		</table>
	<?php endif; ?>
</main>



<script type="text/javascript">

    /**
     * Avaa modalin, jossa voi syöttää uuden hankintapaikan tiedot.
     */
    function avaa_modal_uusi_hankintapaikka(){
        Modal.open( {
            content:  `
				<h4>Anna uuden hankintapaikan tiedot.</h4>
				<hr>
				<form action="" method="post" name="uusi_hankintapaikka" id="uusi_hankintapaikka">
					<label class="required">ID</label>
					<input name="hankintapaikka_id" type="text" placeholder="000" title="Numero väliltä 001-999" pattern="00[1-9]|0[1-9][0-9]|[1-9][0-9]{2}" required>
					<br><br>
					<label class="required">Yritys</label>
					<input name="nimi" type="text" placeholder="Yritys Oy" title="" required>
					<br><br>
					<label>Katuosoite</label>
					<input name="katuosoite" type="text" placeholder="Katu" title="">
					<br><br>
					<label>Postiumero</label>
					<input name="postinumero" type="text" pattern="[0-9]{1,20}" placeholder="00000">
					<br><br>
					<label>Kaupunki</label>
					<input name="kaupunki" type="text" pattern=".{1,50}" placeholder="KAUPUNKI">
					<br><br>
					<label>Maa</label>
					<input name="maa" type="text" pattern=".{1,50}" placeholder="Maa">
					<br><br>
					<label>Puh</label>
					<input name="puh" type="text" placeholder="040 123 4567"
						   pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" >
					<br><br>
					<label>Fax</label>
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01 234567">
					<br><br>
					<label>URL</label>
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi">
					<br><br>
					<label>Yhteyshenkilö</label>
					<input name="yhteyshenkilo_nimi" type="text" pattern=".{1,50}" placeholder="Etunimi Sukunimi">
					<br><br>
					<label>Yhteyshenk. puh.</label>
					<input name="yhteyshenkilo_puhelin" type="text" pattern=".{1,50}" placeholder="040 123 4567">
					<br><br>
					<label>Yhteyshenk. email</label>
					<input name="yhteyshenkilo_email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi">
					<br><br>
					<label>Tilaustapa</label>
					<input name="tilaustapa" type="text" pattern=".{1,50}">
					<br><br>
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_hankintapaikka">
				</form>
				`,
            draggable: true
        } );
    }

    function avaa_modal_muokkaa_hankintapaikka(hankintapaikka_id, yritys, katuosoite, postinumero, postitoimipaikka,
                                               maa, puhelin, fax, www_url, yhteyshenkilo_nimi, yhteyshenkilo_puhelin, yhteyshenkilo_email, tilaustapa){
        Modal.open( {
            content:  `
				<div style="width: 320px">
				<h4>Muokkaa hankintapaikan tietoja.</h4>
				<hr><br>
				<form action="" method="post" name="muokkaa_hankintapaikka">
					<label>ID</label>
					<h5 style="display: inline">`+hankintapaikka_id+`</h5>
					<br><br>
					<label class="required">Hankintapaikka</label>
					<input name="yritys" type="text" placeholder="Nimi" value="`+yritys+`" required>
					<br><br>
					<label>Katuosoite</label>
					<input name="katuosoite" type="text" placeholder="Katu" value="`+katuosoite+`">
					<br><br>
					<label>Postiumero</label>
					<input name="postinumero" type="text" pattern="[0-9]{1,20}" placeholder="00000" value="`+postinumero+`">
					<br><br>
					<label>Kaupunki</label>
					<input name="kaupunki" type="text" pattern=".{1,50}" placeholder="KAUPUNKI" value="`+postitoimipaikka+`">
					<br><br>
					<label>Maa</label>
					<input name="maa" type="text" pattern=".{1,50}" placeholder="Maa" value="`+maa+`">
					<br><br>
					<label>Puh</label>
					<input name="puh" type="text" placeholder="040 123 4567" value="`+puhelin+`"
						   pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" >
					<br><br>
					<label>Fax</label>
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01234567" value="`+fax+`">
					<br><br>
					<label>URL</label>
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi" value="`+www_url+`">
					<br><br>
					<label>Yhteyshenkilö</label>
					<input name="yhteyshenkilo_nimi" type="text" pattern=".{1,50}" placeholder="Etunimi Sukunimi" value="`+yhteyshenkilo_nimi+`">
					<br><br>
					<label>Yhteyshenk. puh.</label>
					<input name="yhteyshenkilo_puhelin" type="text" pattern=".{1,50}" placeholder="040 123 4567" value="`+yhteyshenkilo_puhelin+`">
					<br><br>
					<label>Yhteyshenk. email</label>
					<input name="yhteyshenkilo_email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi" value="`+yhteyshenkilo_email+`">
					<br><br>
					<label>Tilaustapa</label>
					<input name="tilaustapa" type="text" pattern=".{1,50}" value="`+tilaustapa+`">
					<br><br>
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa">
					<input name="hankintapaikka_id" type="hidden" value="`+hankintapaikka_id+`">
				</form>
				</div>
				`,
            draggable: true
        });
    }

    /**
     * Hankintapaikan poistaminen. Luodaan form ja lähetetään se.
     * @param hankintapaikka_id
     * @returns {boolean}
     */
    function poista_hankintapaikka (hankintapaikka_id) {
        let c = confirm("Haluatko varmasti poistaa hankintapaikan?");
        if (c === false) {
            e.preventDefault();
            return false;
        }
        let form = document.createElement("form");
        form.setAttribute("method", "POST");
        form.setAttribute("action", "");
        form.setAttribute("name", "poista_hankintapaikka");


        //POST["poista"]
        let field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "poista");
        field.setAttribute("value", "true");
        form.appendChild(field);

        //POST["hankintapaikka_id"]
        field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "hankintapaikka_id");
        field.setAttribute("value", hankintapaikka_id);
        form.appendChild(field);

        document.body.appendChild(form);
        form.submit();
    }

    $(document).ready(function(){

        $('*[data-href]')
            .css('cursor', 'pointer')
            .click(function(){
                window.location = $(this).data('href');
                return false;
            });

	    $(document.body)
        //Estetään valitsemasta jo olemassa olevaa hankintapikka ID:tä ja nimeä
            .on('submit', '#uusi_hankintapaikka', function(e) {
                let id, nimi, i, hankintapaikat;
                hankintapaikat = <?php echo json_encode($hankintapaikat); ?>
                //Tarkastetaan onko ID tai nimi varattu
                id = +document.getElementById("uusi_hankintapaikka").elements["hankintapaikka_id"].value;
                nimi = document.getElementById("uusi_hankintapaikka").elements["nimi"].value;
                if (hankintapaikat.length > 0) {
                    for (i = 0; i < hankintapaikat.length; i++) {
                        if ( hankintapaikat[i].id === id ) {
                            alert("ID on varattu.");
                            e.preventDefault();
                            return false;
                        }
                        if ( hankintapaikat[i].nimi.toUpperCase() === nimi.toUpperCase()) {
                            alert("Nimi on varattu.");
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            });
    });

</script>
</body>
</html>