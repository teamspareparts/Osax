<?php
require '_start.php'; global $db, $user, $cart, $yritys;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

$feedback = '';
/** Tiedoston käsittely */
if ( isset($_FILES['eula']['name']) ) {

    if ( !$_FILES['eula']['error'] ) { // Jos ei virheitä...
		$target_file = "eula.txt"; //TODO: Pitäisikö eula olla jossain muussa kansiossa?

		$query = "UPDATE kayttaja SET vahvista_eula = 1"; // Käyttäjien on vahvistettava uusi eula.
		$db->query( $query ); //Ditto

		// Onnistuiko tiedoston siirtäminen serverille
		if ( move_uploaded_file( $_FILES['eula']['tmp_name'], $target_file ) ) {
			$feedback = "<p class='success'>EULA päivitetty onnistuneesti.</p>";
		} else {
			$feedback = "<p class='error'>EULAn päivittäminen epäonnistui.</p>";
		}

	} else {// Jos virhe tiedoston latauksessa...
		$feedback = "Error: " . $_FILES['eula']['error'];
	}
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>EULA</title>
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <style type="text/css">
        #eula_tiedosto {
            border: 1px dashed;
            border-radius: 6px;
        }
        #eula_tiedosto:hover {
            border-color: cadetblue;
        }
    </style>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
    <h1>EULA</h1>
    <p>Tällä sivulla voit ladata palvelimelle uudet käyttöehdot.</p>

    <br><br>
    <fieldset><legend>Käyttöoikeussopimus</legend>
        <form action="#" method="post" enctype="multipart/form-data">
            Uusi EULA: <input id="eula_tiedosto" type="file" name="eula" accept=".txt"/>
            <input id="submit_eula" type="submit" name="submit" value="Submit" disabled/>
            <a href="http://www.osax.fi/eula.txt" download="eula" style="margin-left:100px">Lataa nykyinen EULA</a>
        </form>
    </fieldset>

    <?= $feedback ?>
</main>

<script type="text/javascript">
    $(document).ready(function(){
        $('#eula_tiedosto').on("change", function() {
            $('#submit_eula').prop('disabled', !$(this).val());
        });

    });
</script>

</body>
</html>
