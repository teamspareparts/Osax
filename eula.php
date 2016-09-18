<?php
require '_start.php'; global $db, $user, $cart, $yritys;

if ( isset($_POST['vahvista_eula']) ) {
	$db->query( "UPDATE kayttaja SET vahvista_eula = '0' WHERE id = ?",
		[$user->id] );
	header('Location:etusivu.php'); exit;
}

$txt_tiedosto = __DIR__.'/eula.txt';
$eula_txt = file_get_contents( $txt_tiedosto, false, NULL, 0 );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/login_styles.css">
	<title>Käyttöoikeussopimus</title>
</head>
<body class="eula">

<header id="eula_header">
	<div id="head_text">
		<h1>Käyttöoikeussopimus</h1>
		<h4>Ole hyvä ja hyväksy käyttöoikeussopimus ennen sivuston käyttöä.</h4>
	</div>
	<div id="head_logo">
		<h1>LOGO</h1>
	</div>
</header>
	
<main id="eula_body">
	<textarea id="eula_scrollable_textbox" ReadOnly title="Käyttöoikeussopimus">
		<?php echo $eula_txt; ?>
	</textarea><br>
	<div id="eula_napit_container">
		<span id="hyvaksy_eula"><button class="nappi" id="hyvaksy_nappi" onClick="hyvaksy_eula();">Hyväksy</button></span>
		<span id="hylkaa_eula"><button class="nappi" id="hylkaa_nappi" onClick="hylkaa_eula();">Hylkää</button><br></span>
	</div>
</main>

<form class="hidden" id="vahvista_eula_form" action="#" method=post>
	<input type=hidden name="vahvista_eula">
</form>

<script>
function hyvaksy_eula () {
	document.getElementById("vahvista_eula_form").submit();
}

function hylkaa_eula () {
	window.location="./logout.php?redir=10";
}
</script>

</body>
</html>
