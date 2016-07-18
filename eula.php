<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<style type="text/css">
		.class #id tag {}
		
		#eula_scrollable_textbox {
		    height:30em;
		    width:100%;
		    overflow:scroll;
	    }
	    
	    #eula_napit {
	    	display: flex;
	    }
	    
	    #hyvaksy_eula, #hylkaa_eula {
	    	flex-grow: 1;
	    	text-align: center;
	    }
	</style>
	<title>Tuotteet</title>
</head>
<body>

<?php include 'header.php';
require 'tietokanta.php';

function tulosta_admin_hallinta () {
	if ( is_admin() ) {
		echo 'ADMIN: Haluatko mieluummin päivittää EULA:n tällä sivulla, vai <br>
			Lataa uusi EULA serverille -> <a href="lue_tiedostosta.php">Tiedoston luku -sivulla</a><br><br>';
	} else return false;
}

$user_id = $_SESSION['id'];

if ( !empty($_POST['vahvista_eula']) ) {
	$sql_query = "	UPDATE	kayttaja
					SET		vahvista_eula = '0'
					WHERE	id = '$user_id';";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
}

$txt_tiedosto = __DIR__.'/eula.txt';
$eula_txt = file_get_contents( $txt_tiedosto, false, NULL, 0 );

?>

<h1>Käyttöoikeussopimus</h1>
<h4>Ole hyvä ja hyväksy käyttöoikeussopimus ennen sivuston käyttöä.</h4>

<?php tulosta_admin_hallinta();?>

<div id="eula_body">
	<div id="eula_textbox">
		<textarea id="eula_scrollable_textbox" ReadOnly >
			<?php echo $eula_txt; ?>
		</textarea><br>
	</div>
	<div id="eula_napit" style>
	
		<span id="hyvaksy_eula"><button class="nappi" onClick="hyvaksy_eula();">Hyväksy</button></span>
		<span id="hylkaa_eula"><button class="nappi" style="background:#d20006; border-color:#b70004;" onClick="hylkaa_eula();">Hylkää</button><br></span>
	</div>
</div>


<form style="display:none;" id="vahvista_eula_form" action="#" method=post>
	<input type=hidden name="vahvista_eula" value="<?= $user_id ?>">
</form>

<script>
function hyvaksy_eula () {
	var form_ID = "vahvista_eula_form";
	var vahvistus = confirm( "Oletko varma, että haluat vahvistaa EULA:n?\n"
							+ "Tätä toimintoa ei voi perua jälkeenpäin!\n"
							+ "Jos painat OK, yhtikäs mitään ei muutu, ja maailma jatkuu pyörimistään.\n"
							+ "(Huom. Hylkää-napilla olisi ollut aivan sama lopputulos.)");
	if ( vahvistus == true ) {
		document.getElementById(form_ID).submit();
	} else {
		return false;
	}
}

function hylkaa_eula () {
	var vahvistus = alert( "Mitä oikein odotit tapahtuvan?\n"
							+ "Ole hyvä, ja lopeta sivuston käyttö välittömästi.\n"
							+ "Sen jälkeen, ilmoita ylläpitäjälle halustasi sulkea tilisi.\n"
							+ "Kiitos yhteistyöstäsi.");
}
</script>

</body>
</html>