<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';

if ( $user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

/**
 * Luetaan / päivitetään valmistajan hinnasto tietokantaan.
 * @param DByhteys $db
 * @param int $brandId
 * @param int $hankintapaikka_id
 * @return array
 */
function lue_hinnasto_tietokantaan( DByhteys $db, /*int*/ $brandId, /*int*/ $hankintapaikka_id) {
	$handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');
	set_time_limit(60); // Ylläolevaan tiedoston avaamiseen.

	if ( isset($_POST['otsikkorivi']) ) { // Hypätään ensimmäisen rivin yli, jos otsikkorivi
		$row = -1;
	} else {
		$row = 0;
	}
	$failed_inserts = 0;
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		if ($row == -1) {
			$row++;
			continue;
		}
		$row++;

		//TODO: Tarkasta myös $datan sisältö, väärien syötteiden varalta.
		$num = count($data); // rivin sarakkeiden lkm
		if ($num != 6) {
			$failed_inserts++;
			continue;
		}


        $articleNo = str_replace(" ", "", $data[$_POST["s0"]]);
		$ostohinta = (double)str_replace(",", ".", $data[$_POST["s1"]]);
		$myyntihinta = (double)str_replace(",", ".", $data[$_POST["s2"]]);
		$vero_id = (int)$data[$_POST["s3"]];
		$minimimyyntiera = (int)$data[$_POST["s4"]];
		$kappaleet = (int)$data[$_POST["s5"]];
        $tuotekoodi = $hankintapaikka_id ."-". $articleNo; //esim: 100-QTB249

		$sql = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, 
					minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi) 
				VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY
					UPDATE sisaanostohinta = VALUES(sisaanostohinta), hinta_ilman_ALV = VALUES(hinta_ilman_ALV),
						ALV_kanta = VALUES(ALV_kanta), minimimyyntiera = VALUES(minimimyyntiera),
						varastosaldo = varastosaldo + VALUES(varastosaldo), 
						keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + VALUES(sisaanostohinta) * 
							VALUES(yhteensa_kpl) )/(yhteensa_kpl + VALUES(yhteensa_kpl) )),0),
						yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl)";
		$db->query($sql, //TODO: $ostohinta, $ostohinta? Lyhennä "VALUES(ostohinta)"
			[$articleNo, $ostohinta, $ostohinta, $myyntihinta, $vero_id, $minimimyyntiera, $kappaleet, $kappaleet,
				$brandId, $hankintapaikka_id, $tuotekoodi]);

	}

	fclose($handle);
	return array($row, $failed_inserts);    //kaikki rivit , epäonnistuneet syötöt
}

$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : '';
$hankintapaikkaId = isset($_GET['hankintapaikka']) ? $_GET['hankintapaikka'] : '';

// Varmistetaan, että GET-parametrit ovat oikeita
if ( !$valmistajanHankintapaikka = $db->query(
		"SELECT brandName FROM valmistajan_hankintapaikka WHERE hankintapaikka_id = ? AND brandId = ? LIMIT 1",
		[$hankintapaikkaId, $brandId]) ) {
	header("Location:toimittajat.php"); exit(); }

$brandName = $valmistajanHankintapaikka->brandName; // Alustetaan valmistajan nimi ja hankintapaikka
$hankintapaikka = $db->query(
	"SELECT LPAD(`id`,3,'0') AS id, nimi FROM hankintapaikka WHERE id = ? LIMIT 1", [$hankintapaikkaId]);

if ( isset($_FILES['tuotteet']['name']) ) {
	//Jos ei virheitä...
	if ( !$_FILES['tuotteet']['error'] ) {
        $result = lue_hinnasto_tietokantaan( $db, $brandId, $hankintapaikka->id);

		// Päivitetään hinnaston sisäänluku päivämäärä.
		$db->query("UPDATE valmistajan_hankintapaikka SET hinnaston_sisaanajo_pvm = NOW() 
					WHERE brandId = ? AND hankintapaikka_id = ?",
			[$brandId, $hankintapaikkaId] );

		$onnistuneet = $result[0] - $result[1];
		$kaikki = $result[0];
		$_SESSION['feedback'] = "<p class='success'>Tietokantaan vietiin {$onnistuneet} / {$kaikki} tuotetta.</p>";
    }

	else { // Jos virhe...
		$_SESSION['feedback'] = "Error: " . $_FILES['tuotteet']['error'];
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
	unset($_SESSION["feedback"]);
}
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
	<h1><?= $brandName?><br>Hankintapaikka: <?=$hankintapaikka->id?> - <?=$hankintapaikka->nimi ?></h1>
	<p>Tällä sivulla voit sisäänlukea valmistajan hinnaston.<span class="question">?</span></p>

	<fieldset><legend>Lisää tuotteita</legend>
		<form action="" method="post" enctype="multipart/form-data" id="lisaa_tuotteet">
			Luettava tiedosto: <input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv">
			<input id=submit_tuote type="submit" name="submit" value="Submit" disabled>
			<br>
			<label for="otsikkorivi">Otsikkorivi: </label><input type="checkbox" name="otsikkorivi" id="otsikkorivi"><br>
			<label for="select0">1:</label><select name=s0 id=select0></select><br>
            <label for="select1">2:</label><select name=s1 id=select1></select><br>
            <label for="select2">3:</label><select name=s2 id=select2></select><br>
            <label for="select3">4:</label><select name=s3 id=select3></select><br>
            <label for="select4">5:</label><select name=s4 id=select4></select><br>
            <label for="select5">6:</label><select name=s5 id=select5></select><br>
		</form>
	</fieldset>

    <?= $feedback ?>
</main>

<script type="text/javascript">
    //Täytetään dynaamisesti select-option valinnat.
	var sarake;
	for (var i = 0; i < 6; i++) {
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
		$('#tuote_tiedosto').on("change", function() {
			$('#submit_tuote').prop('disabled', !$(this).val());
		});

		//Tarkastetaan ettei sarakkeissa dublikaatteja
		$('#lisaa_tuotteet').submit(function(e) {
			var i, valinta;
			var valinnat = [];
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

		//Näytetään ohjeet kun hiiri viedään kysymysmerkin päälle.
		$("span.question").hover(function () {
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
