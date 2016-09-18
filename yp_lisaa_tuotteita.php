<?php
require '_start.php'; global $db, $user, $cart, $yritys;
require 'tecdoc.php';

/**
 * @param DByhteys $db
 * @param $brandId
 */
function paivita_hinnaston_sisaanluku_pvm( DByhteys $db, $brandId ){
	$query = "	UPDATE valmistaja
				SET hinnaston_sisaanajo_pvm = NOW()
				WHERE brandId = ? ";
	$db->query( $query, [$brandId] );
}

if (!is_admin()) {
	header("Location:etusivu.php"); exit();
}
if ( !empty($_GET['brandId']) ) {
	header("Location:toimittajat.php"); exit();
}

$brandId = $_GET['brandId'];
$brandName = $_GET['brandName'];
?>
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
<?php require 'header.php'; ?>

<main class="main_body_container">
	<h1 class="otsikko"><?= $brandName?></h1>
	<p>T‰ll‰ sivulla voit sis‰‰nlukea valmistajan hinnaston.<span class="question">?</span></p>

	<fieldset><legend>Lis‰‰ tuotteita</legend>
		<form action="" method="post" enctype="multipart/form-data" id="lisaa_tuotteet">
			Luettava tiedosto: <input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv"/>
			<input id=submit_tuote type="submit" name="submit" value="Submit" disabled/>
			<br>
			Otsikkorivi: <input type="checkbox" name="otsikkorivi" /><br>
			1:<select name=s0 id=select0></select><br>
			2:<select name=s1 id=select1></select><br>
			3:<select name=s2 id=select2></select><br>
			4:<select name=s3 id=select3></select><br>
			5:<select name=s4 id=select4></select><br>
			6:<select name=s5 id=select5></select><br>
		</form>
	</fieldset>
</main>
<?php



/**
 * Tuotteita sis‰lt‰v‰n tiedoston k‰sittely
 */
if(isset($_FILES['tuotteet']['name'])) {
	//Jos ei virheit‰...
	if(!$_FILES['tuotteet']['error']) {
		
		$handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');


		global $connection;
		set_time_limit(60);
		//Hyp‰t‰‰n ensimm‰isen rivin yli jos otsikkorivi
		if (isset($_POST['otsikkorivi'])){
			$row = -1;
		}
		else{
			$row=0;
		}
		$failed_inserts = 0;
		echo "<table>";
		echo "<th>Tuote</th><th>Ostohinta</th><th>Myyntihinta</th><th>Vero ID</th><th>Myyntier‰</th><th>KPL</th>";
		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			if ($row == -1){$row++;continue;}
			$row++;
			//rivin sarakkeiden lkm
			$num = count($data);
			if ($num != 6) {
				echo "<tr><td><span style='color: red;'>Tuotetta rivill‰ " . $row . " ei voitu lis‰t‰.</span></td></tr>";
				$failed_inserts++;
				continue;
			}
			//J‰rjestet‰‰n tuotteet aina j‰rjestykseen
			//1:tuote	2:ostohinta	3:myyntihinta
			//4:vero(%)	5:minimimyyntier‰	6:kpl
			$row_order = array();
			$row_order[0] = strval($data[$_POST["s0"]]);
			$row_order[1] = doubleval(str_replace(",", ".", $data[$_POST["s1"]]));
			$row_order[2] = doubleval(str_replace(",", ".", $data[$_POST["s2"]]));
			$row_order[3] = intval($data[$_POST["s3"]]);
			$row_order[4] = intval($data[$_POST["s4"]]);
			$row_order[5] = intval($data[$_POST["s5"]]);
			//var_dump($row_order);
			$query = "INSERT INTO tuote (articleNo, sisaanostohinta, keskiostohinta, hinta_ilman_ALV, ALV_kanta, minimimyyntiera, varastosaldo, yhteensa_kpl, brandNo) 
					  VALUES ('$row_order[0]', '$row_order[1]', '$row_order[1]', '$row_order[2]', '$row_order[3]', '$row_order[4]', '$row_order[5]', '$row_order[5]', '$brandId')
					  ON DUPLICATE KEY
					  	UPDATE sisaanostohinta=$row_order[1], hinta_ilman_ALV=$row_order[2], ALV_kanta=$row_order[3], minimimyyntiera=$row_order[4], varastosaldo = varastosaldo+$row_order[5], keskiostohinta=IFNULL(((keskiostohinta*yhteensa_kpl+$row_order[1]*$row_order[5])/(yhteensa_kpl+$row_order[5])),0), yhteensa_kpl=yhteensa_kpl+$row_order[5];";
			$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
			echo "<tr>";
			for ($c=0; $c < $num; $c++) {
				echo "<td>" . $row_order[$c] . "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "Tietokantaan vietiin ". ($row-$failed_inserts) ."/" . $row ." tuotetta.";
		
		
		fclose($handle);
		paivita_hinnaston_sisaanluku_pvm( $db, $brandId );
		
	}
	// Jos virhe...
	else
	{
		echo "Error: " . $_FILES['tuotteet']['error'];
	}
}
?>

<script type="text/javascript">

	//TODO: jQuerylla on oma jQuery.inArray()-funktio. Onko t‰ll‰ jokin tarkoitus?
	function in_array(needle, haystack) {
		var i;
		for ( i in haystack ) {
			if ( haystack[i] === needle ) {
				return true;
			}
		}
		return false;
	}


	var sarake;
	for (var i = 0; i < 6; i++) {
		sarake = document.getElementById("select" + i);
		sarake.options.add(new Option("Tuotenumero", 0));
		sarake.options.add(new Option("Ostohinta", 1));
		sarake.options.add(new Option("Myyntihinta", 2));
		sarake.options.add(new Option("Verokanta", 3));
		sarake.options.add(new Option("Minimimyyntier‰", 4));
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
				if(in_array(valinta, valinnat)) {
					e.preventDefault();
					alert("Tarkasta sarakkeiden valinnat.");
					return false;
				}
				valinnat.push(valinta);
			}
			return true;
		});

		//N‰ytet‰‰n ohjeet kun hiiri vied‰‰n kysymysmerkin p‰‰lle.
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
