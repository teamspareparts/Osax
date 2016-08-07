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
	<title>EULA</title>
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
<main class="main_body_container">
	<h1>EULA</h1>
	<p>Tällä sivulla voit ladata palvelimelle uudet käyttöehdot.</p>

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
 * Tiedoston käsittely
 */

if(isset($_FILES['eula']['name'])) {
	// Jos ei virheitä...
	if(!$_FILES['eula']['error']) {

		$target_dir = ""; //jos ladataan johonkin kansioon kuten "eula/"
		$target_file = $target_dir . basename($_FILES["eula"]["name"]);
		
		// Uusien asiakkaiden on vahvistettava uusi eula.
		global $connection;
		$query = "UPDATE kayttaja SET vahvista_eula=1";
		mysqli_query($connection, $query);
		
		// Onnistuiko tiedoston siirtäminen serverille
		if (move_uploaded_file($_FILES['eula']['tmp_name'], $target_file)) {
		    echo "<p class='success'>EULA päivitetty onnistuneesti.</p>";
		} else {
		    echo "<p class='error'>EULAn päivittäminen epäonnistui.</p>";
		}
	    	
	}
	// Jos virhe tiedoston latauksessa...
	else
	{
		echo "Error: " . $_FILES['eula']['error'];
	}
}



?>
<script type="text/javascript">

$(document).ready(function(){
	$('#eula_tiedosto').on("change", function() {
	    $('#submit_eula').prop('disabled', !$(this).val()); 
	});

});
</script>

</body>
</html>
