<?php
session_start();
if ( empty($_SESSION['id']) ) { header('Location: index.php?redir=4'); exit; }

$txt_tiedosto = __DIR__.'/eula.txt';
$eula_txt = @file_get_contents( $txt_tiedosto, false, NULL, 0 );

if ( !$eula_txt ) {
	$eula_txt = "Oikeaa käyttöoikeussopimusta ei löytynyt. Ole hyvä ja ilmoita ylläpitäjälle.\n
		Jos olet ylläpitäjä, niin sinun varmaan kannattaisi päivittää uusi käyttöoikeussopimus serverille.";
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/login_styles.css">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Käyttöoikeussopimus</title>
</head>
<body class="eula">

<header id="eula_header">
	<div id="head_text">
		<h1>Käyttöoikeussopimus</h1>
		<h4>Ole hyvä ja hyväksy käyttöoikeussopimus ennen sivuston käyttöä.</h4>
	</div>
	<div id="head_logo">
		<img src="img/osax_logo.jpg">
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

<script>
	function hyvaksy_eula () {
		$.post("ajax_requests.php",
			{	eula_vahvista: true,
				user_id: <?= $_SESSION['id'] ?> }
		);
		window.location="./etusivu.php";
	}

	function hylkaa_eula () {
		window.location="./logout.php?redir=10";
	}
</script>

</body>
</html>
