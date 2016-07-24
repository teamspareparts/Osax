<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<style type="text/css">
			.class #id tag {}
			#tuote_tiedosto, #eula_tiedosto {
				border: 1px dashed;
    			border-radius: 6px;
			}
			#tuote_tiedosto:hover, #eula_tiedosto:hover {
				border-color: cadetblue;
			}
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>Tuotteet</title>
</head>
<body>
<?php 	
require 'header.php';
require 'tietokanta.php';
if (!is_admin()) {
	header("Location:tuotehaku.php");
	exit();
}
?>
<main>
	<h1>Lisää tuotteita tiedostosta</h1>
	<p>Tällä sivulla voit ladata tuotteita valikoimaan suoraan tiedostosta tai ladata palvelimelle uudet käyttöehdot.</p>
	
	<fieldset><legend>Lisää tuotteita</legend>
		<form action="#" method="post" enctype="multipart/form-data">
			Luettava tiedosto: <input id="tuote_tiedosto" type="file" name="tuotteet" accept=".csv"/>
			<input id=submit_tuote type="submit" name="submit" value="Submit" disabled/>
			<br>
			1:<select id=select0></select><br>
			2:<select id=select1></select><br>
			3:<select id=select2></select><br>
			4:<select id=select3></select><br>
			5:<select id=select4></select><br>
			6:<select id=select5></select><br>
		</form>
		HUOM: Ei vielä tarkastuksia...
	</fieldset>
	
	<br><br>
	
	<fieldset><legend>Käyttöoikeussopimus</legend>
		<form action="#" method="post" enctype="multipart/form-data">
			Uusi EULA: <input id="eula_tiedosto" type="file" name="eula" accept=".txt"/>
			<input id="submit_eula" type="submit" name="submit" value="Submit" disabled/>
			<a href="http://www.osax.fi/eula.txt" download="nykyinen_EULA" style="margin-left:100px">Lataa nykyinen EULA</a>
		</form>
	</fieldset>
</main>

<?php 
/**
 * Tuotteita sisältävän tiedoston käsittely
 */
if(isset($_FILES['tuotteet']['name'])) {
	//Jos ei virheitä...
	if(!$_FILES['tuotteet']['error']) {
		
		$handle = fopen($_FILES['tuotteet']['tmp_name'], 'r');
		//$sisalto = fgetcsv($handle);
		echo "Valmis käsittelemään tiedostoa...";
		
		global $connection;
		$query = "INSERT...;";
		
		$row=1;
		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			//hypätään ensimmäisen rivin yli
			if($row==1){ $row++; continue; }
			
			//sarakkeet lkm
			$num = count($data);
			
			echo "<p> $num saraketta rivillä $row: <br /></p>\n";
			$row++;
			for ($c=0; $c < $num; $c++) {
				echo $data[$c] . "<br />\n";
			}
		}
		fclose($handle);
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
		
	}
	//Jos virhe...
	else
	{
		echo "Error: " . $_FILES['tuotteet']['error'];
	}
}
else if(isset($_FILES['eula']['name'])) {
	//Jos ei virheitä...
	if(!$_FILES['eula']['error']) {

		$target_dir = ""; //jos ladataan johonkin kansioon kuten "eula/"
		$target_file = $target_dir . basename($_FILES["eula"]["name"]);
		/**
		 * 
		 * 
		 * 
		 * Merkataan tietokantaan, että EULA muuttunut. (tekemättä)
		 * 
		 * 
		 */
		
		if (move_uploaded_file($_FILES['eula']['tmp_name'], $target_file)) {
		    echo "<p class='success'>EULA päivitetty onnistuneesti.</p>";
		} else {
		    echo "<p class='error'>EULAn päivittäminen epäonnistui.</p>";
		}
	    	
	}
	//Jos virhe tiedoston latauksessa...
	else
	{
		echo "Error: " . $_FILES['eula']['error'];
	}
}



?>
<script type="text/javascript">

//luodaan sisältö selectoreihin
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

	$('#eula_tiedosto').on("change", function() {
	    $('#submit_eula').prop('disabled', !$(this).val()); 
	});

});
</script>

</body>
</html>
