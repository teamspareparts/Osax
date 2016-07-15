<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tuotteet</title>
</head>
<body>

<?php include 'header.php';
require 'tietokanta.php';

$user_id = $_SESSION['id'];

if ( !empty($_POST['vahvista_eula']) ) {
	$sql_query = "	UPDATE	kayttaja
					SET		vahvista_eula = '0'
					WHERE	id = '$user_id';";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
}
?>

<h1>Käyttöoikeussopimus</h1>
<p>Tällä sivulla on käyttöoikeussopimus. Tälle sivulle pääsee kuka vain.<br>
Tälle sivulle ei ole juuri nyt linkkiä. Tälle sivulle johdatetaan ne, jotka eivät ole vielä vahvistaneet Käyttöoikeussopimusta.<br>
Adminin kohdalla sopimus on vahvistettu automaattisesti. Admin voi varmaan tällä sivulla ladata uuden sopimuksen serverille.</p>
<h4>WIP. Ulkonäkö ei lopullinen.</h4>

<div id="eula_body">
	<div id="eula_textbox">
		Tässä kohtaa on tekstiboxi, jossa on eula. Se on vain yhden sivun mittainen, jota pystyy scrollaamaan.
	</div>
	<div id="eula_napit">
		<button onClick="hyvaksy_eula();">Hyväksy</button> (huom. toiminnallisuus mukana. Jos haluat takaisin tälle sivu, muista URL.)<br>
		<button>Hylkää</button><br>
		Napeissa ei ole toiminnallisuutta. Ajattelin, näin aluksi, että hylkää-nappi heittäisi käyttäjän pihalle, <br>
		poistaisi kaikki tiedot hänestä, asentaisi pari virusta, jotka tuhoavat käyttäjän koneen, ja lopuksi tulostavat "Ha haa!" -alertin.
		<br><br>
		Actually, nyt kun mietin, olisi varmaan vielä hauskempaa, jos Hylkää-nappi olisi vain pysyvästi Disabled.
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
</script>

</body>
</html>