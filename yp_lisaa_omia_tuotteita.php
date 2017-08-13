<?php
require '_start.php'; global $db, $user, $cart;

// Huom. upload_max_filesize = 5M

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}

/**
 * Luetaan / päivitetään valmistajan hinnasto tietokantaan.
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return array
 */
function lue_hinnasto_tietokantaan( DByhteys $db, /*int*/ $hankintapaikka_id) {
	// Asetukset
	$inserts_per_query = 4000; // Kantaan kerralla ajettavien tuotteiden määrä

	// Alustukset
	$ohita_otsikkorivi = isset($_POST['otsikkorivi']) ? true : false;
	$row = 0;
	$successful_inserts = 0;
	$failed_inserts = []; // Otetaan talteen epäonnistuneet lisäykset ja virheet
	$inserted_brands = []; // Brandit, joiden tuotteita lisättiin
	$placeholders = []; // Placeholderit sql-kyselyyn
	$handle = fopen($_FILES['tuotteet']['tmp_name'], 'r'); // Tiedostokahva

	$questionmarks = implode(',', array_fill( 0, $inserts_per_query, '( ?, ?, sisaanostohinta, ?, ?, ?, varastosaldo, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'));
	$insert_query = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, 
					    minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi, tilauskoodi,
					    valmistaja, nimi, kuva_url, infot, tecdocissa) 
					    VALUES {$questionmarks}
					  ON DUPLICATE KEY
                      	UPDATE sisaanostohinta = VALUES(sisaanostohinta), hinta_ilman_ALV = VALUES(hinta_ilman_ALV),
                            ALV_kanta = VALUES(ALV_kanta), minimimyyntiera = VALUES(minimimyyntiera),
                            varastosaldo = varastosaldo + VALUES(varastosaldo),
                            keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + VALUES(sisaanostohinta) *
                                VALUES(yhteensa_kpl) )/(yhteensa_kpl + VALUES(yhteensa_kpl) )),0),
                            yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl),
                            valmistaja = VALUES(valmistaja), kuva_url = VALUES(kuva_url),
                            infot = VALUES(infot), nimi = VALUES(nimi),
                            aktiivinen = 1";

	// Haetaan hankintapaikkaan linkitettyjen brändien käyttämät id:t
	$sql = "SELECT brandi_kaytetty_id, brandi_id FROM brandin_linkitys
			WHERE hankintapaikka_id = ?";
	$brands = $db->query($sql, [$hankintapaikka_id], FETCH_ALL, PDO::FETCH_ASSOC);

	// Etsitään missä columneissa on mitäkin
	$search_array = array($_POST["s0"], $_POST["s1"], $_POST["s2"], $_POST["s3"], $_POST["s4"],
		$_POST["s5"], $_POST["s6"], $_POST["s7"], $_POST["s8"], $_POST["s9"], $_POST["s10"]);

	$brandId_index = array_search('0', $search_array);
	$brandName_index = array_search('1', $search_array);
	$articleNo_index = array_search('2', $search_array);
	$ostohinta_index = array_search('3', $search_array);
	$myyntihinta_index = array_search('4', $search_array);
	$vero_id_index = array_search('5', $search_array);
	$minimimyyntiera_index = array_search('6', $search_array);
	$kpl_index = array_search('7', $search_array);
	$nimi_index = array_search('8', $search_array);
	$kuva_url_index = array_search('9', $search_array);
	$infot_index = array_search('10', $search_array);

	// Käydään läpi csv tiedosto rivi kerrallaan
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		$row++;
		// Jos tiedostossa otsikkorivi, hypätään sen yli
		if ( $ohita_otsikkorivi ) {
			$ohita_otsikkorivi = false;
			continue;
		}

		$num = count($data); // rivin sarakkeiden lkm
		// Tarkistetaan sarakkeiden lukumäärän oikeellisuus
		if (($num != 12 && ($_POST["tilauskoodin_tyyppi"] == "liite_eri")) || ($num != 11 && ($_POST["tilauskoodin_tyyppi"]) != "liite_eri") ) {
			$failed_inserts[] = "$row - Sarakkeiden lukumäärä ei täsmää.";
			continue;
		}

		$hankintapaikka_id = (int)$hankintapaikka_id;
		$brandId = $data[$brandId_index];
		$brandName = utf8_encode($data[$brandName_index]);
		$articleNo = utf8_encode(str_replace(" ", "", $data[$articleNo_index]));
		$ostohinta = (double)str_replace(",", ".", $data[$ostohinta_index]);
		$myyntihinta = (double)str_replace(",", ".", $data[$myyntihinta_index]);
		$vero_id = (int)$data[$vero_id_index];
		$minimimyyntiera = (int)$data[$minimimyyntiera_index];
		$kappaleet = (int)$data[$kpl_index];
		$nimi = utf8_encode($data[$nimi_index]);
		$kuva_url = $data[$kuva_url_index];
		$infot = utf8_encode($data[$infot_index]);
		$tilauskoodi = "";

		switch ($_POST["tilauskoodin_tyyppi"]) {
			case "liite_sama":
				$tilauskoodi = $articleNo;
				break;
			case "liite_plus":
				$tilauskoodi = $_POST["etuliite_plus"] . $articleNo . $_POST["takaliite_plus"];
				break;
			case "liite_miinus":
				$tilauskoodi = $articleNo;
				if (substr($articleNo, 0, strlen($_POST["etuliite_miinus"])) == $_POST["etuliite_miinus"]) {
					$articleNo = substr($articleNo, strlen($_POST["etuliite_miinus"]));
				}
				if (substr($articleNo, -strlen($_POST["takaliite_miinus"])) == $_POST["takaliite_miinus"]) {
					$articleNo = substr($articleNo, 0, -strlen($_POST["takaliite_miinus"]));
				}
				break;
			case "liite_eri":
				$tilauskoodi = $data[11];
				break;
		}
		$tuotekoodi = str_pad($hankintapaikka_id, 3, "0", STR_PAD_LEFT) . "-" . $articleNo; //esim: 100-QTB249

		// Tarkastetaan csv:n solujen oikeellisuus
		if ( !($brandId && $brandName && $hankintapaikka_id && $ostohinta &&
			$myyntihinta && $minimimyyntiera && $articleNo && $nimi) ) {
			$failed_inserts[] = "$row - Jokin soluista on tyhjä tai 0.";
			continue;
		}

		// Tarkastetaan brändi id:n oikeellisuus
		if ( ($key = array_search($brandId, array_column($brands, 'brandi_kaytetty_id'))) === false ) {
			$failed_inserts[] = "$row - Brändin id:tä ei löydy linkitetyistä brändeistä.";
			continue;
		} else {
			$brandId = $brands[$key]['brandi_id'];
		}

		// Placeholderit
		$placeholders[] = $articleNo;
		$placeholders[] = $ostohinta;
		$placeholders[] = $myyntihinta;
		$placeholders[] = $vero_id;
		$placeholders[] = $minimimyyntiera;
		$placeholders[] = $kappaleet;
		$placeholders[] = $brandId;
		$placeholders[] = $hankintapaikka_id;
		$placeholders[] = $tuotekoodi;
		$placeholders[] = $tilauskoodi;
		$placeholders[] = $brandName;
		$placeholders[] = $nimi;
		$placeholders[] = $kuva_url;
		$placeholders[] = $infot;

		$successful_inserts++;

		$inserted_brands[] = $brandId;

		// Tuotteiden lisäys
		if ( $successful_inserts % $inserts_per_query == 0 ) {
			$db->query($insert_query, $placeholders);
			$placeholders = [];
		}
	}
	// Jäljellä olevat tuotteet kantaan
	if ( $successful_inserts % $inserts_per_query != 0 ) {
		$questionmarks = implode(',', array_fill(0, ($successful_inserts % $inserts_per_query), '( ?, ?, sisaanostohinta, ?, ?, ?, varastosaldo, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'));
		$insert_query = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, 
					    minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi, tilauskoodi,
					    valmistaja, nimi, kuva_url, infot, tecdocissa) 
					    VALUES {$questionmarks}
					  ON DUPLICATE KEY
                      	UPDATE sisaanostohinta = VALUES(sisaanostohinta), hinta_ilman_ALV = VALUES(hinta_ilman_ALV),
                            ALV_kanta = VALUES(ALV_kanta), minimimyyntiera = VALUES(minimimyyntiera),
                            varastosaldo = varastosaldo + VALUES(varastosaldo),
                            keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + VALUES(sisaanostohinta) *
                                VALUES(yhteensa_kpl) )/(yhteensa_kpl + VALUES(yhteensa_kpl) )),0),
                            yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl),
                            tilauskoodi = VALUES(tilauskoodi),
                            valmistaja = VALUES(valmistaja), kuva_url = VALUES(kuva_url),
                            infot = VALUES(infot), nimi = VALUES(nimi),
                            aktiivinen = 1";
		$db->query($insert_query, $placeholders);
	}

	fclose($handle);

	// Päivitetään hinnaston sisäänluku päivämäärä.
	$inserted_brands = array_unique($inserted_brands);
	foreach ($inserted_brands as $brand_id) {
		$db->query("UPDATE brandin_linkitys SET hinnaston_sisaanajo_pvm = NOW()
					WHERE hankintapaikka_id = ? AND brandi_id = ?",
			[$hankintapaikka_id, $brand_id]);
	}

	return array($successful_inserts, $failed_inserts); // kaikki rivit , array epäonnistuneet syötöt
}

/**
 * @param DByhteys $db
 * @param $hankintapaikka_id
 * @return array
 */
function lue_linkitykset_tietokantaan( DByhteys $db, /*int*/$hankintapaikka_id ) {
	//TODO indev
	// Alustukset
	$otsikkorivi = isset($_POST['otsikkorivi']) ? true : false;
	$row = 0;
	$failed_inserts = [];
	$handle = fopen($_FILES['linkitykset']['tmp_name'], 'r'); // Tiedostokahva

	while (($data = fgetcsv($handle, 1000, ";")) !== false) {
		$row++;
		// Ohitetaan otsikkorivi
		if ( $otsikkorivi ) {
			$otsikkorivi = false;
			continue;
		}
		// Tarkastukset
		if ( count($data) !== 4 ) {
			$failed_inserts[] = "{$row} - Väärä määrä rivejä.";
			continue;
		}
		if ( !$data[0] && !$data[1] && !$data[2] && !$data[3] ) {
			$failed_inserts[] = "{$row} - Jokin soluista on tyhjä.";
			continue;
		}

		// Tietokantakysely
		$sql = "INSERT INTO tuote_linkitys (tuote_id, brandNo, articleNo, hae_tecdoc_vertailut) 
				SELECT tuote.id, ?, ?, 1
				FROM tuote
				WHERE tuote.tecdocissa = 0 
					AND tuote.brandNo = ?
					AND tuote.articleNo = ?
					AND tuote.hankintapaikka_id = ?
				ON DUPLICATE KEY UPDATE tuote_linkitys.tuote_id = tuote_linkitys.tuote_id";
		$result = $db->query($sql, [$data[2], $data[3], $data[0], $data[1], $hankintapaikka_id]);
		if ( !$result ) {
			$failed_inserts[] = "{$row} - Itse perustettua tuotetta ei löytynyt kannasta.";
			continue;
		}
	}
	return $failed_inserts;
}

/**
 * Perustetaan oma tuote.
 * @param DByhteys $db
 * @param $values
 * @return bool
 */
function perusta_oma_tuote( DByhteys $db, array $values ) {
	$sql = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, 
				minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi, tilauskoodi,
			  	valmistaja, nimi, kuva_url, infot, tecdocissa) 
			  	VALUES ( ? , ?, sisaanostohinta, ?, ?, ?, ?, varastosaldo, ?, ?, ?, ?, ?, ?, ?, ?, 0)
			ON DUPLICATE KEY
            	UPDATE sisaanostohinta = VALUES(sisaanostohinta), hinta_ilman_ALV = VALUES(hinta_ilman_ALV),
                	ALV_kanta = VALUES(ALV_kanta), minimimyyntiera = VALUES(minimimyyntiera),
                    varastosaldo = varastosaldo + VALUES(varastosaldo),
                    keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + VALUES(sisaanostohinta) *
                    	VALUES(yhteensa_kpl) )/(yhteensa_kpl + VALUES(yhteensa_kpl) )), sisaanostohinta),
                    yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl),
                    tilauskoodi = VALUES(tilauskoodi),
                    valmistaja = VALUES(valmistaja), kuva_url = VALUES(kuva_url),
                    infot = VALUES(infot), nimi = VALUES(nimi),
                    aktiivinen = 1";
	$result = $db->query($sql, $values);
	if ( !$result ) {
		return false;
	}
	return true;
}

// Alustetaan POST ja GET -parametrit
$hankintapaikka_id = isset($_GET['hankintapaikka']) ? $_GET['hankintapaikka'] : '';

// Alustetaan muuttujat
$sql = "SELECT *, LPAD(`id`,3,'0') AS id FROM hankintapaikka WHERE id = ? LIMIT 1";
$hankintapaikka = $db->query($sql, [$hankintapaikka_id]);
$alv_kannat = $db->query("SELECT * FROM ALV_kanta", [], FETCH_ALL);
$sql = "SELECT brandin_linkitys.brandi_id, brandin_linkitys.brandi_kaytetty_id,
			brandi.nimi
		FROM brandin_linkitys
		LEFT JOIN brandi
			ON brandin_linkitys.brandi_id = brandi.id
 		WHERE hankintapaikka_id = ? AND aktiivinen = 1 AND oma_brandi = 1";
$brands = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);

if ( !$brands ) {
	header("Location:toimittajat.php");
	exit();
}
if ( isset($_FILES['tuotteet']['name']) ) {
	if ( !$_FILES['tuotteet']['error'] ) { // Jos ei virheitä
		$result = lue_hinnasto_tietokantaan( $db, $hankintapaikka->id );
		$onnistuneet = $result[0];
		$epaonnistuneet = $result[1];
		$kaikki = $onnistuneet + count($epaonnistuneet);

		$_SESSION['feedback'] = "<p class='success'>Tietokantaan vietiin {$onnistuneet} / {$kaikki} tuotetta.";
		if ( $epaonnistuneet ) {
			$_SESSION['feedback'] .= "<br>Hylättyjen rivien virheet:<br>" .rtrim(implode('<br>',$epaonnistuneet), ',') ."</p>";
		}
	}
	else { // Jos virhe
		$_SESSION['feedback'] = "Error: " . $_FILES['tuotteet']['error'];
	}
}
elseif ( isset($_POST['lisaa_tuote']) ){
	$articleNo = str_replace(" ", "", $_POST['tuotenumero']);
	$brand_nimi = $brands[array_search($_POST['brand'], $brands)]->nimi;
	$tuotekoodi = str_pad($hankintapaikka_id, 3, "0", STR_PAD_LEFT) . "-" . mb_strtoupper($articleNo); //esim: 100-QTB249
	$values = [];
	$values[] = $articleNo;
	$values[] = $_POST['ostohinta'];
	$values[] = $_POST['myyntihinta'];
	$values[] = $_POST['alv'];
	$values[] = $_POST['minimimyyntiera'];
	$values[] = $_POST['kpl'];
	$values[] = $_POST['brand'];
	$values[] = $hankintapaikka_id;
	$values[] = $tuotekoodi;
	$values[] = $_POST['tilauskoodi'];
	$values[] = $brand_nimi;
	$values[] = $_POST['nimi'];
	$values[] = $_POST['kuva_url'];
	$values[] = $_POST['infot'];
	$result = perusta_oma_tuote($db, $values);
	if ( $result ) {
		$_SESSION['feedback'] = "<p class='success'>Tuote perustettu.</p>";
	} else {
		$_SESSION['feedback'] = "<p class='success'>Tuotteen perustaminen epäonnistui.</p>";
	}
}
elseif ( isset($_FILES['linkitykset']['name']) ) {
	/**
	if (!$_FILES['linkitykset']['error']) { // Jos ei virheitä
		$result = lue_linkitykset_tietokantaan($db, $hankintapaikka->id);
		$_SESSION['feedback'] = "<p class='success'></p>";
	} else { // Jos virhe
		$_SESSION['feedback'] = "Error: " . $_FILES['linkitykset']['error'];
	}*/
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
unset($_SESSION["feedback"]);

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

	<!-- Otsikko ja painikkeet  -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>">Takaisin</a>		</section>
		<section class="otsikko">
			<span>Perusta omia tuotteita</span>
			<h1><?=$hankintapaikka->id?> - <?=$hankintapaikka->nimi ?></h1>
		</section>
		<section class="napit">
			<button class="nappi" id="info_button">Info</button>
		</section>
	</div>

	<!-- Info -->
	<fieldset id="info_box" hidden><legend>INFO</legend>
		<p>Tällä sivulla voit sisäänlukea omia tuotteita tietokantaan.<p>
		<ul>
			<li>Kaikki valinnaiset infot, jotka tavallisesti saadaan TecDocista, tulee syöttää käsin.</li>
			<li>Tiedostomuodon oltava .csv</li>
			<li>Tiedostossa on oltava 11 saraketta.</li>
			<li>Jos jotakin brändiä ei ole linkitetty hankintapaikkaan, kaikki brändin tuotteet hylätään.</li>
			<li>Tarvittaessa luo uusia brändejä, jotta voit lisätä niille tuotteita.</li>
			<li>Erottimena käytettävä merkkiä ;</li>
			<li>Infojen erottimena käytä merkkiä |</li>
		</ul>
	</fieldset>
	<br><br>

	<div class="flex_row">
		<!-- Tuotteen lisäys tiedostosta. -->
		<fieldset><legend>Tiedostosta</legend>
				<form action="" method="post" enctype="multipart/form-data" id="lisaa_tuotteet">
					<label for="tuote_tiedosto">Luettava tiedosto:</label>
					<input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv">
					<input id=submit_tuote type="submit" name="submit" value="Submit">
					<br>
					<label for="otsikkorivi">Otsikkorivi: </label><input type="checkbox" name="otsikkorivi" id="otsikkorivi"><br>
					<label for="select0">1:</label><select name="s0" id="select0"></select><br>
					<label for="select1">2:</label><select name="s1" id="select1"></select><br>
					<label for="select2">3:</label><select name="s2" id="select2"></select><br>
					<label for="select3">4:</label><select name="s3" id="select3"></select><br>
					<label for="select4">5:</label><select name="s4" id="select4"></select><br>
					<label for="select5">6:</label><select name="s5" id="select5"></select><br>
					<label for="select6">7:</label><select name="s6" id="select6"></select><br>
					<label for="select7">8:</label><select name="s7" id="select7"></select><br>
					<label for="select8">9:</label><select name="s8" id="select8"></select><br>
					<label for="select9">10:</label><select name="s9" id="select9"></select><br>
					<label for="select10">11:</label><select name="s10" id="select10"></select><br>
					<div id="tilauskoodi_sarake" class="tilauskoodi_action" hidden>
						<label for="select11">12:</label>
						<select name="tilauskoodi" id="select11">
							<option>Tilauskoodi</option>
						</select>
					</div>
					<br><br><br>
					<div id="tilauskoodin_liitteet">
						<label for="tilauskoodin_tyyppi">Tuotteen tilauskoodi</label><br>
						<select name="tilauskoodin_tyyppi" id="tilauskoodin_tyyppi">
							<option value="liite_sama" selected>Tilauskoodi on sama kuin tuotenumero.</option>
							<option value="liite_plus">Luo tilauskoodi lisäämällä tuotenumeroon etu- tai takaliite. </option>
							<option value="liite_miinus">Luo tilauskoodi vähentämällä etu- tai takaliite.</option>
							<option value="liite_eri">Tilauskoodi ei vastaa tuotenumeroa.</option>
						</select><br><br>
					</div>


					<!-- Tilauskoodin luominen lisäämällä liitteet -->
					<div id="liite_plus" class="tilauskoodi_action" hidden>
						<p>Luo tilauskoodi lisäämällä tuotenumeroon etu- ja takaliite.</p>
						<label for="etuliite_plus">Etuliite:</label>
						<input type="text" name="etuliite_plus" id="etuliite_plus" pattern="[a-zA-Z0-9-]+" maxlength="6">
						<label for="takaliite_plus">Takaliite:</label>
						<input type="text" name="takaliite_plus" id="takaliite_plus" pattern="[a-zA-Z0-9-]+" maxlength="6">
					</div>

					<!-- Tilauskoodin luominen poistamalla liitteet -->
					<div id="liite_miinus" class="tilauskoodi_action" hidden>
						<p>Tiedostossa oleva tuotenumero on hankintapaikan käyttämä tilauskoodi.<br>
							Luo tuotenumero poistamalla tilauskoodista etu- tai takaliite.</p>
						<label for="etuliite_miinus">Etuliite:</label>
						<input type="text" name="etuliite_miinus" id="etuliite_miinus" pattern="[a-zA-Z0-9-]+" maxlength="6">
						<label for="takaliite_miinus">Takaliite:</label>
						<input type="text" name="takaliite_miinus" id="takaliite_miinus" pattern="[a-zA-Z0-9-]+" maxlength="6">
					</div>

					<!-- Tilauskoodin lukeminen tiedostosta -->
					<div id="liite_eri" class="tilauskoodi_action" hidden>
						<p>Tuotenumero ei vastaa lainkaan tilauskoodia.<br>
							Tiedostossa on oltava seitsämäs sarake tilauskoodia varten!</p>
					</div>

				</form>
		</fieldset>

		<!-- Tuotteen lisäys yksittäin -->
		<fieldset><legend>Yksittäin</legend>
			<form action="" method="post">
				<!-- Brändi -->
				<label for="brand">Brändi</label>
				<select name="brand" id="brand">
					<?php foreach ( $brands as $brand ) : ?>
						<option value="<?=$brand->brandi_id?>"><?="{$brand->brandi_kaytetty_id} - {$brand->nimi}"?></option>
					<?php endforeach; ?>
				</select><br><br>
				<!-- Tuotenumero -->
				<label for="tuotenumero">Tuotenumero:</label>
				<input type="text" name="tuotenumero" id="tuotenumero" required>
				<br><br>
				<!-- Ostohinta -->
				<label for="ostohinta">Ostohinta:</label>
				<input type="number" name="ostohinta" id="ostohinta" min="0" step="0.01"
				       placeholder="0,00" required>
				<br><br>
				<!-- Myyntihinta -->
				<label for="myyntihinta">Myyntihinta:</label>
				<input type="number" name="myyntihinta" id="myyntihinta" min="0" step="0.01"
				       placeholder="0,00" required>
				<br><br>
				<!-- Alv-kanta -->
				<label for="alv_kanta">Verokanta:</label>
				<select name="alv" id="alv_kanta">
					<?php foreach ( $alv_kannat as $i=>$kanta ) : ?>
						<option value="<?=$kanta->kanta?>"><?="{$i}: {$kanta->prosentti}"?></option>
					<?php endforeach; ?>
				</select><br><br>
				<!-- Minimimyyntierä -->
				<label for="minimimyyntiera">Minimimyyntierä:</label>
				<input type="number" name="minimimyyntiera" id="minimimyyntiera" min="1" value="1" required>
				<br><br>
				<!-- Kappalemäärä -->
				<label for="kpl">Kpl:</label>
				<input type="number" name="kpl" id="kpl" min="0" value="0" required>
				<br><br>
				<!-- Tuotteen nimi -->
				<label for="nimi">Tuotteen nimi:</label>
				<input type="text" name="nimi" id="nimi" placeholder="Hammashihna" required>
				<br><br>
				<!-- Tuotteen kuvan url -->
				<label for="kuva_url">Kuvan url:</label>
				<input type="text" name="kuva_url" id="kuva_url" placeholder="http://www.kuva.org/1234">
				<br><br>
				<!-- Infot -->
				<label for="infot">Infot:</label>
				<input type="text" name="infot" id="infot" placeholder="Hampaita: 100|Leveys: 17mm"
				       title="Käytä erottimena | -merkkiä">
				<br><br>
				<!-- Tilauskoodi -->
				<label for="tilauskoodi">Tilauskoodi:</label>
				<input type="text" name="tilauskoodi" id="tilauskoodi"
				       title="Hankintapaikan käyttämä tuotekoodi." required>
				<br><br>

				<input type="submit" name="lisaa_tuote" value="Lisää">
			</form>
		</fieldset>

		<!-- Tuotteiden linkitys tecdoctuotteisiin -->
		<fieldset><legend>Linkitä tecdoctuotteisiin</legend>
			<form action="" method="post" enctype="multipart/form-data">
				<!-- Tiesosto -->
				<label for="linkitys_tiedosto">Luettava tiedosto:</label>
				<input type="file" name="linkitykset" id="linkitys_tiedosto" accept=".csv"><br>
				<!-- Otsikkorivi -->
				<label for="linkitys_otsikkorivi">Otsikkorivi: </label>
				<input type="checkbox" name="otsikkorivi" id="linkitys_otsikkorivi"><br>
				<br>
				1: Brändin id<br>
				2: Tuotenumero<br>
				3: Tecdoctuotteen brändin id<br>
				4: Tecdoctuotteen tuotenumero<br>
				<input type="submit" name="linkitys" value="Submit">
			</form>
		</fieldset>
	</div>

	<?= $feedback ?>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
    //Täytetään dynaamisesti select-option valinnat.
    let sarake;
    for (let i = 0; i <= 10; i++) {
        sarake = document.getElementById("select" + i);
        sarake.options.add(new Option("Brändin id", 0));
        sarake.options.add(new Option("Brändin nimi", 1));
        sarake.options.add(new Option("Tuotenumero", 2));
        sarake.options.add(new Option("Ostohinta", 3));
        sarake.options.add(new Option("Myyntihinta", 4));
        sarake.options.add(new Option("Verokanta", 5));
        sarake.options.add(new Option("Minimimyyntierä", 6));
        sarake.options.add(new Option("Kpl", 7));
        sarake.options.add(new Option("Tuotteen nimi", 8));
        sarake.options.add(new Option("Kuvan URL", 9));
        sarake.options.add(new Option("Infot", 10));
        $("#select" + i + " option[value=" + i + "]").attr('selected', 'selected');
    }

    $(document).ready(function(){
        // Submit -napin toiminta
        let tiedosto = $('#tuote_tiedosto');
        if (tiedosto.get(0).files.length === 0) { $('#submit_tuote').prop('disabled', 'disabled'); }
        tiedosto.on("change", function() {
            $('#submit_tuote').prop('disabled', !$(this).val());
        });

        // Tarkastetaan ettei sarakkeissa dublikaatteja
        $('#lisaa_tuotteet').submit(function(e) {
            let i, valinta;
            let valinnat = [];
            for ( i=0; i<6; i++ ) {
                valinta = $("#select" + i +" option:selected").val();
                if($.inArray(valinta, valinnat) !== -1) {
                    e.preventDefault();
                    alert("Tarkasta sarakkeiden valinnat.");
                    return false;
                }
                valinnat.push(valinta);
            }
            return true;
        });

        // Tilauskoodin tyyppi -valikko
        $('#tilauskoodin_tyyppi').change(function() {
            $('.tilauskoodi_action').hide();
            let tyyppi = $(this).val();
            $('#' + tyyppi).show();
            if (tyyppi === "liite_eri") $('#tilauskoodi_sarake').show();
        });

        // Info-napin toiminta
        $('#info_button').click(function () {
            info_box = $('#info_box');
            if ( info_box.is(":visible") ) {
                info_box.hide();
            } else {
                info_box.show();
            }
        });
    });
</script>
</body>
</html>
