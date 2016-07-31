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
	header("Location:tuotehaku.php");
	exit();
}
$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : null;
if(!$brandId) {
	header("Location:toimittajat.php");
	exit();
}
$brandName = $_GET['brandName'];
?>

	<h1 class="otsikko"><?= $brandName?></h1>
	<p>Tällä sivulla voit lisätä valmistajalle tuotteita.</p>
	<p>Anteeksi näin hirveä ulkonäkö. Yritän ensin saada toiminnallisuuden kuntoon...</p>

	<fieldset><legend>Lisää tuotteita</legend>
		<form action="" method="post" enctype="multipart/form-data" id="lisaa_tuotteet">
			Luettava tiedosto: <input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv"/>
			<input id=submit_tuote type="submit" name="submit" value="Submit" disabled/>
			<br>
			1:<select name=s0 id=select0></select><br>
			2:<select name=s1 id=select1></select><br>
			3:<select name=s2 id=select2></select><br>
			4:<select name=s3 id=select3></select><br>
			5:<select name=s4 id=select4></select><br>
			6:<select name=s5 id=select5></select><br>
		</form>
	</fieldset>
<?php 
/**
 * Tuotteita sisältävän tiedoston käsittely
 */
if(isset($_FILES['tuotteet']['name'])) {
	//Jos ei virheitä...
	if(!$_FILES['tuotteet']['error']) {
		
		$handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');
		//$sisalto = fgetcsv($handle);
		
		
		global $connection;
		$query = "INSERT...;";
		
		$row=1;
		echo "<table>";
		echo "<th>tuote</th><th>ostohinta</th><th>myynti</th><th>vero</th><th>min</th><th>kpl</th>";
		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			//hypätään ensimmäisen rivin yli
			if($row==1){ $row++; continue; }
			$row_order = array();
			$row_order[0] = $data[$_POST["s0"]];
			$row_order[1] = $data[$_POST["s1"]];
			$row_order[2] = $data[$_POST["s2"]];
			$row_order[3] = $data[$_POST["s3"]];
			$row_order[4] = $data[$_POST["s4"]];
			$row_order[5] = $data[$_POST["s5"]];
			//var_dump($row_order);
			//sarakkeet lkm
			$num = count($data);
			echo "<tr>";
			//echo "<p> $num saraketta rivillä $row: <br /></p>\n";
			$row++;
			for ($c=0; $c < $num; $c++) {
				echo "<td>" . $row_order[$c] . "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		
		echo "Tietokantaan vietiin $row tuotetta.";

		
		/************************
		 * 
		 * 
		 * 
		 * Tähän tiedostosta luku
		 * 
		 * 
		 * 
		 * 
		 **************************/
		
		
		fclose($handle);
		
	}
	// Jos virhe...
	else
	{
		echo "Error: " . $_FILES['tuotteet']['error'];
	}
}
?>

<script type="text/javascript">

function in_array(needle, haystack) {
    for(var i in haystack) {
        if(haystack[i] == needle) return true;
    }
    return false;
}


for(var i = 0; i < 6; i++){
	sarake = document.getElementById("select" + i);
	sarake.options.add(new Option("Tuotenumero", 0));
	sarake.options.add(new Option("Ostohinta", 1));
	sarake.options.add(new Option("Myyntihinta", 2));
	sarake.options.add(new Option("Verokanta", 3));
	sarake.options.add(new Option("Minimimyyntierä", 4));
	sarake.options.add(new Option("Kpl", 5));
	$("#select"+i+" option[value="+i+"]").attr('selected', 'selected');
}

$(document).ready(function(){
	$('#tuote_tiedosto').on("change", function() {
	    $('#submit_tuote').prop('disabled', !$(this).val()); 
	});


	//Tarkastetaan ettei sarakkeissa dublikaatteja
	$('#lisaa_tuotteet').submit(function(e) {
		var valinnat = [];
		for(var i=0; i<6; i++){
			var valinta = $("#select" + i +" option:selected").val();
			if(in_array(valinta, valinnat)) {
				e.preventDefault();
				alert("Tarkasta sarakkeiden valinnat.");
		        return false;
			}
			valinnat.push(valinta);
		}
        return true;
	});


});
</script>
</body>
</html>
