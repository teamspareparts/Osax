<?php
require '_start.php'; //global $db, $user, $cart, $yritys;
require 'tecdoc.php';


/**
 * Tehdään pakolliset tarkastukset
 */

//Tarkastetaan onko admin.
if (!is_admin()) {
	header("Location:etusivu.php"); exit();
}

$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : NULL;
$hankintapaikkaId = isset($_GET['hankintapaikka']) ? $_GET['hankintapaikka'] : NULL;

//Tarkastetaan onko get -parametrit laillisia.
if(!$linkki = tarkasta_get_parametrit($db, $brandId, $hankintapaikkaId)){
    header("Location:toimittajat.php"); exit();
}

//Alustetaan valmistajan nimi ja hankintapaikka
$brandName = $linkki[0]->brandName;
$hankintapaikka = hae_hankintapaikan_tiedot($db, $hankintapaikkaId);
$message = null; //viestiä tai erroria varten

//Tarkastetaan vielä onnistuiko tietokannan päivitys
if (isset($_SESSION['success']) && $_SESSION['success'] == true) {
    $onnistuneet = $_SESSION['rivit'] - $_SESSION['virheet'];
    $kaikki = $_SESSION['rivit'];
    $message = "Tietokantaan vietiin $onnistuneet / $kaikki tuotetta.";
    unset($_SESSION['success']);
    unset($_SESSION['virheet']);
    unset($_SESSION['rivit']);
}


/**
 * Tarkastetaan, että GET parametrit ovat laillisia.
 * @param DByhteys $db
 * @param $brandId
 * @param $hankintapaikkaId
 * @return array|bool|stdClass  Palauttaa linityksen valmistajan ja hankintapaikan välillä, jos olemassa, muuten false
 */
function tarkasta_get_parametrit(DByhteys $db, /*int*/ $brandId, /*int*/ $hankintapaikkaId ){
    $query = "SELECT * FROM valmistajan_hankintapaikka WHERE hankintapaikka_id=? AND brandId=?";
    $linkitys = $db->query($query, [$hankintapaikkaId, $brandId], FETCH_ALL, PDO::FETCH_OBJ);
    if (count($linkitys) == 1) return $linkitys;
    return false;
}

function hae_hankintapaikan_tiedot(DByhteys $db, /*int*/ $hankintapaikka_id){
    $query = "SELECT LPAD(`id`,3,'0') AS id, nimi FROM hankintapaikka WHERE id= ? ";
    return $db->query($query, [$hankintapaikka_id], FETCH_ALL, PDO::FETCH_OBJ)[0];
}

/**
 * Päivitetään hinnaston sisäänluku päivämäärä.
 * @param DByhteys $db
 * @param $brandId
 */
function paivita_hinnaston_sisaanajo_pvm( DByhteys $db, /*int*/ $brandId, /*int*/ $hankintapaikka_id ){
    $query = "	UPDATE valmistajan_hankintapaikka
				SET hinnaston_sisaanajo_pvm = NOW()
				WHERE brandId = ? AND hankintapaikka_id = ? ";
    $db->query( $query, [$brandId, $hankintapaikka_id] );
}


/**
 * Luetaan / päivitetään valmistajan hinnasto tietokantaan.
 * @param DByhteys $db
 * @param $brandId
 * @param $hankintapaikka_id
 * @return array
 */
function lue_hinnasto_tietokantaan( DByhteys $db, /*int*/ $brandId, /*int*/ $hankintapaikka_id) {
    $handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');

    set_time_limit(60);
    //Hypätään ensimmäisen rivin yli jos otsikkorivi
    if (isset($_POST['otsikkorivi'])) {
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
        //rivin sarakkeiden lkm
        $num = count($data);
        if ($num != 6) {
            $failed_inserts++;
            continue;
        }


        $articleNo = strval($data[$_POST["s0"]]);
        $ostohinta = doubleval(str_replace(",", ".", $data[$_POST["s1"]]));
        $myyntihinta = doubleval(str_replace(",", ".", $data[$_POST["s2"]]));
        $vero_id = intval($data[$_POST["s3"]]);
        $minimimyyntiera = intval($data[$_POST["s4"]]);
        $kappaleet = intval($data[$_POST["s5"]]);
        $tuotekoodi = $hankintapaikka_id ."-". $articleNo; //esim: 100-QTB249

        $query = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo, hankintapaikka_id, tuotekoodi) 
	  			  VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				  ON DUPLICATE KEY
			   	  UPDATE sisaanostohinta= ? , hinta_ilman_ALV= ? , ALV_kanta= ? , minimimyyntiera= ? , varastosaldo = varastosaldo+ ? , keskiostohinta=IFNULL(((keskiostohinta*yhteensa_kpl+ ? * ? )/(yhteensa_kpl+ ? )),0), yhteensa_kpl=yhteensa_kpl+ ? ;";
        $db->query($query, [$articleNo, $ostohinta, $ostohinta, $myyntihinta, $vero_id, $minimimyyntiera, $kappaleet, $kappaleet, $brandId, $hankintapaikka_id, $tuotekoodi, $ostohinta, $myyntihinta, $vero_id, $minimimyyntiera, $kappaleet, $ostohinta, $kappaleet, $kappaleet, $kappaleet]);

    }

    //echo "Tietokantaan vietiin ". ($row-$failed_inserts) ."/" . $row ." tuotetta.";
    fclose($handle);
    return array($row, $failed_inserts);    //kaikki rivit , epäonnistuneet syötöt

}


if(isset($_FILES['tuotteet']['name'])) {
	//Jos ei virheitä...
	if(!$_FILES['tuotteet']['error']) {
        $result = lue_hinnasto_tietokantaan( $db, $brandId, $hankintapaikka->id);
        $_SESSION['rivit'] = $result[0];
        $_SESSION['virheet'] = $result[1];
        paivita_hinnaston_sisaanajo_pvm($db, $brandId, $hankintapaikkaId);

        $_SESSION['success'] = true;
        header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
        exit();
    }

	// Jos virhe...
	else {
		echo "Error: " . $_FILES['tuotteet']['error'];
	}
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
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
			Luettava tiedosto: <input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv"/>
			<input id=submit_tuote type="submit" name="submit" value="Submit" disabled/>
			<br>
			<label for="otsikkorivi">Otsikkorivi: </label><input type="checkbox" name="otsikkorivi" id="otsikkorivi" /><br>
			<label for="select0">1:</label><select name=s0 id=select0></select><br>
            <label for="select1">2:</label><select name=s1 id=select1></select><br>
            <label for="select2">3:</label><select name=s2 id=select2></select><br>
            <label for="select3">4:</label><select name=s3 id=select3></select><br>
            <label for="select4">5:</label><select name=s4 id=select4></select><br>
            <label for="select5">6:</label><select name=s5 id=select5></select><br>
		</form>
	</fieldset>

    <?php if ($message) : ?>
    <p><span class="success"><?= $message?></span></p>
    <?php endif;?>
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
