<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/** Tiedoston käsittely */
if ( isset($_FILES['eula']['name']) ) {

    if ( !$_FILES['eula']['error'] ) { // Jos ei virheitä
		if ( !file_exists('./eula') ) { // Tarkistetaan, että kansio on olemassa.
			mkdir( './eula' ); // Jos ei, luodaan se
		}
		$target_file = "./eula/eula.txt";

		// Käyttäjien on vahvistettava uusi eula
		$db->query( "UPDATE kayttaja SET vahvista_eula = 1 WHERE yllapitaja = 0" );

		// Onnistuiko tiedoston siirtäminen serverille
		if ( move_uploaded_file( $_FILES['eula']['tmp_name'], $target_file ) ) {
			$_SESSION['feedback'] = "<p class='success'>EULA päivitetty onnistuneesti.</p>";
		} else {
			$_SESSION['feedback'] = "<p class='error'>EULAn päivittäminen epäonnistui.</p>";
		}

	} else {// Jos virhe tiedoston latauksessa
		$_SESSION['feedback'] = "<p class='error'>Error: {$_FILES['eula']['error']}</p>";
	}
}

if ( !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
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
    <title>EULA</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
    <style type="text/css">
        #eula_tiedosto {
            border: 1px dashed;
            border-radius: 6px;
	        padding: 15px;
        }
        #eula_tiedosto:hover {
            border-color: #1d7ae2;
        }
    </style>
</head>
<body>

<!-- Tiedoston latausta varten -->
<form id="download_form" method="post" action="download.php">
    <input type="hidden" name="filepath" value="eula/eula.txt">
</form>

<?php require 'header.php'; ?>
<main class="main_body_container lomake">

	<div class="otsikko_container">
		<section class="otsikko">
			<h1>EULA</h1>
		</section>
		<section class="napit">
			<form method="post" action="download.php" id="download_form">
				<input type="hidden" name="filepath" value="eula/eula.txt">
				<button type="submit" class="nappi">Lataa nykyinen EULA <i class="material-icons">file_download</i></button>
			</form>
		</section>
	</div>

    <?= $feedback ?>

	<div class="white-bg" style="border:1px solid;border-radius:3px;width:450px;margin:auto;">
	    <p>Tällä sivulla voit ladata palvelimelle uudet käyttöehdot.</p>
		<p>Käytäthän uudessa EULA:ssa <strong>windows-1252 (ANSI) -koodausta</strong>, jotta skandit näkyvät oikein.</p>
		<p> <a href="#" onclick="document.getElementById('download_form').submit();">
				Lataa nykyinen EULA<i class="material-icons">file_download</i>
			</a></p>
	</div>

    <br><br>
    <fieldset style="width:475px;"><legend>Käyttöoikeussopimus</legend>
        <form action="#" method="post" enctype="multipart/form-data">
	        <label>Uusi EULA:
		        <input type="file" name="eula" accept=".txt" id="eula_tiedosto">
	        </label>
	        <input type="submit" name="submit" value="Submit" class="nappi" id="submit_eula" disabled>

	        <p class="small_note">Drag&Drop toimii myös.</p>
        </form>
    </fieldset>

</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
	document.getElementById('eula_tiedosto').addEventListener('change', function (el) {
		document.getElementById('submit_eula').disabled = !el.target.value;
	})
</script>

</body>
</html>
