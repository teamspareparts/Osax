<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}

/**
 * Luetaan / päivitetään valmistajan hinnasto tietokantaan.
 * @param DByhteys $db
 * @param int $brandId
 * @param int $hankintapaikka_id
 * @return array
 */
function lue_hinnasto_tietokantaan( DByhteys $db, /*int*/ $brandId, /*String*/ $brandName, /*int*/ $hankintapaikka_id) {
	// Tiedostokahva
    $handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');

	$ohita_otsikkorivi = isset($_POST['otsikkorivi']) ? true : false;
    $row = 0;
    $successful_inserts = 0;
    $failed_inserts = []; //Otetaan talteen epäonnistuneiden lisäysten rivinumerot

    //Placeholderit sql-kyselyyn
	$placeholders = [];

	//Käydään läpi csv tiedosto rivi kerrallaan
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $row++;
        // Jos tiedostossa otsikkorivi, hypätään sen yli
		if ( $ohita_otsikkorivi ) {
			$ohita_otsikkorivi = false;
			continue;
		}

		$num = count($data); // rivin sarakkeiden lkm
        // Tarkistetaan sarakkeiden lukumäärän oikeellisuus
		if (($num != 7 && ($_POST["tilauskoodin_tyyppi"] == "liite_eri")) || ($num != 6 && ($_POST["tilauskoodin_tyyppi"]) != "liite_eri") ) {
			$failed_inserts[] = $row;
			continue;
		}

        $hankintapaikka_id = (int)$hankintapaikka_id;
		$articleNo = str_replace(" ", "", $data[$_POST["s0"]]);
		$ostohinta = (double)str_replace(",", ".", $data[$_POST["s1"]]);
		$myyntihinta = (double)str_replace(",", ".", $data[$_POST["s2"]]);
		$vero_id = (int)$data[$_POST["s3"]];
		$minimimyyntiera = (int)$data[$_POST["s4"]];
		$kappaleet = (int)$data[$_POST["s5"]];
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
				$tilauskoodi = $data[6];
				break;
		}
		$tuotekoodi = $hankintapaikka_id . "-" . $articleNo; //esim: 100-QTB249

        //Tarkastetaan csv:n solujen oikeellisuus
        if ( !($hankintapaikka_id && $ostohinta && $myyntihinta && $minimimyyntiera && $articleNo) ) {
            $failed_inserts[] = $row;
            continue;
        }

        //Placeholderit
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

        $successful_inserts++;
	}

	if ( $successful_inserts ) {
	    $inserted_items_count = 0;
	    $inserts_per_query = 4000; // Kantaan kerralla ajettavien tuotteiden määrä

		// Ajetaan tuotteet kantaan
		while ( $inserted_items_count < $successful_inserts ) {
		    // Viimeisellä lisäyskerralla lasketaan uudelleen sisään ajettavien tuotteiden määrä
		    if ( $inserts_per_query + $inserted_items_count > $successful_inserts ) {
		        $inserts_per_query = $successful_inserts - $inserted_items_count;
            }

            $temp_placeholders = array_slice($placeholders, $inserted_items_count*11, $inserts_per_query*11);
            $questionmarks = implode(',', array_fill( 0, $inserts_per_query, '( ?, ?, sisaanostohinta, ?, ?, ?, varastosaldo, ?, ?, ?, ?, ?, ?)'));
			$insert_query = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, 
					    minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi, tilauskoodi, valmistaja) 
					    VALUES {$questionmarks}
					    ON DUPLICATE KEY
                        UPDATE sisaanostohinta = VALUES(sisaanostohinta), hinta_ilman_ALV = VALUES(hinta_ilman_ALV),
                            ALV_kanta = VALUES(ALV_kanta), minimimyyntiera = VALUES(minimimyyntiera),
                            varastosaldo = varastosaldo + VALUES(varastosaldo),
                            keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + VALUES(sisaanostohinta) *
                                VALUES(yhteensa_kpl) )/(yhteensa_kpl + VALUES(yhteensa_kpl) )),0),
                            yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl),
                            aktiivinen = 1";
			$response = $db->query($insert_query, $temp_placeholders);

			$inserted_items_count += $inserts_per_query;
		}
	}
	fclose($handle);

	return array($successful_inserts, $failed_inserts); // kaikki rivit , array epäonnistuneet syötöt
}

$brand_id = isset($_GET['brandId']) ? $_GET['brandId'] : '';
$hankintapaikka_id = isset($_GET['hankintapaikka']) ? $_GET['hankintapaikka'] : '';

// Varmistetaan, että GET-parametrit ovat oikeita
if ( !$valmistajanHankintapaikka = $db->query(
		"SELECT * FROM brandin_linkitys WHERE hankintapaikka_id = ? AND brandi_id = ? LIMIT 1",
		[$hankintapaikka_id, $brand_id]) ) {
	header("Location:toimittajat.php");
	exit();
}

$brand = $db->query("SELECT * FROM brandi WHERE id = ?", [$brand_id]);
$hankintapaikka = $db->query("SELECT *, LPAD(`id`,3,'0') AS id FROM hankintapaikka WHERE id = ?", [$hankintapaikka_id]);


if ( isset($_FILES['tuotteet']['name']) ) {
	//Jos ei virheitä...
	if ( !$_FILES['tuotteet']['error'] ) {
        $result = lue_hinnasto_tietokantaan( $db, $brand->tecdoc_id, $brand->nimi, $hankintapaikka->id );
        $onnistuneet = $result[0];
        $epaonnistuneet = $result[1];
        $kaikki = $onnistuneet + count($epaonnistuneet);

		// Päivitetään hinnaston sisäänluku päivämäärä.
		$db->query("UPDATE valmistajan_hankintapaikka SET hinnaston_sisaanajo_pvm = NOW() 
					WHERE brandId = ? AND hankintapaikka_id = ?",
			[$brand->id, $hankintapaikka->d] );

		$_SESSION['feedback'] = "<p class='success'>Tietokantaan vietiin {$onnistuneet} / {$kaikki} tuotetta.";
		if ($epaonnistuneet) {
            $_SESSION['feedback'] .= "<br>Hylättyjen rivien numerot: " .rtrim(implode(', ',$epaonnistuneet), ',') ."</p>";
        }

    }
	else { // Jos virhe...
		$_SESSION['feedback'] = "Error: " . $_FILES['tuotteet']['error'];
	}
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
    <section>
        <h1 class="otsikko"><?= $brand->nimi?><br>Hankintapaikka: <?=$hankintapaikka->id?> - <?=$hankintapaikka->nimi ?></h1>
        <div id="painikkeet">
            <a class="nappi grey" href="toimittajan_hallinta.php?brandId=<?=$brand->id?>">Takaisin</a>
        </div>
    </section>
	<p>Tällä sivulla voit sisäänlukea valmistajan hinnaston.<span class="question" id="info_tiedostomuoto">?</span></p>

	<fieldset><legend>Lisää tuotteita</legend>
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
            <div id="tilauskoodi_sarake" class="tilauskoodi_action" hidden>
                <label for="select6">7:</label>
                <select name="tilauskoodi" id="select6">
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

    <?= $feedback ?>
</main>

<script type="text/javascript">
    //Täytetään dynaamisesti select-option valinnat.
	let sarake;
	for (let i = 0; i < 6; i++) {
		sarake = document.getElementById("select" + i);
		sarake.options.add(new Option("Tuotenumero", 0));
		sarake.options.add(new Option("Ostohinta", 1));
		sarake.options.add(new Option("Myyntihinta", 2));
		sarake.options.add(new Option("Verokanta", 3));
		sarake.options.add(new Option("Minimimyyntierä", 4));
		sarake.options.add(new Option("Kpl", 5));
		$("#select" + i + " option[value=" + i + "]").attr('selected', 'selected');
	}

	$(document).ready(function(){
		//Submit -napin toiminta
		let tiedosto = $('#tuote_tiedosto');
		if (tiedosto.get(0).files.length === 0) { $('#submit_tuote').prop('disabled', 'disabled'); }
		tiedosto.on("change", function() {
			$('#submit_tuote').prop('disabled', !$(this).val());
		});

		//Tarkastetaan ettei sarakkeissa dublikaatteja
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

		//Tilauskoodin tyyppi -valikko
		$('#tilauskoodin_tyyppi').change(function() {
			$('.tilauskoodi_action').hide();
			let tyyppi = $(this).val();
			$('#' + tyyppi).show();
            if (tyyppi === "liite_eri") $('#tilauskoodi_sarake').show();
		});


		//Näytetään ohjeet kun hiiri viedään kysymysmerkin päälle.
		$("#info_tiedostomuoto").hover(function () {
			$(this).append('<div class="tooltip">' +
				'<p>Tiedostossa oltava 6 saraketta & erottimena oltava ";".</p>' +
				'<p>Jos tiedostossa on otsikkorivi merkkaa valintaruutu.</p>' +
				'</div>');
		}, function () {
			$("div.tooltip").remove();
		});
	});
</script>
</body>
</html>
