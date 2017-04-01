<?php
session_start();
if ( empty( $_SESSION[ 'id' ] ) ) {
	header( 'Location: index.php?redir=4' );
	exit;
}

require "luokat/dbyhteys.class.php";
$db = new DByhteys();

if ( !empty( $_POST[ 'hyvaksy_eula' ] ) ) {
	$sql = "UPDATE kayttaja SET vahvista_eula = '0' WHERE id = ?";
	$result = $db->query( $sql, [$_SESSION[ 'id' ]] );

	if ($result) {
		header( 'Location: etusivu.php' );
		exit;
	}
}
elseif ( !empty( $_POST[ 'hylkaa_eula' ] ) ) {
	header( 'Location: index.php?redir=10' ); // 10 == sinun pitää hyväksyä EULA
	exit;
}

$txt_tiedosto = __DIR__ . '/eula/eula.txt';
$eula_txt = @file_get_contents( $txt_tiedosto, false, null, 0 );

if ( !$eula_txt ) {
	$eula_txt = "Oikeaa käyttöoikeussopimusta ei löytynyt. Ole hyvä ja ilmoita ylläpitäjälle.\n
		Jos olet ylläpitäjä, niin sinun varmaan kannattaisi päivittää uusi käyttöoikeussopimus serverille.";
}
else {
	// Ota käyttöön, jos encoding eulassa on ANSI.
	$eula_txt = mb_convert_encoding($eula_txt, "UTF-8", 'windows-1252');
}
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
		<img src="img/osax_logo.jpg">
	</div>
</header>
	
<main id="eula_body">

	<textarea id="eula_scrollable_textbox" ReadOnly title="Käyttöoikeussopimus">
		<?php echo $eula_txt; ?>
	</textarea><br>
	<form method="post">
		<input type="submit" name="hyvaksy_eula" value="Hyväksy">
		<input type="submit" name="hylkaa_eula" value="Hylkää">
	</form>

</main>

</body>
</html>
