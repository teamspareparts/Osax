<?php declare(strict_types=1);
set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();

session_start();

if ( empty( $_SESSION[ 'id' ] ) ) {
	header( 'Location: index.php?redir=4' );
	exit;
}

$db = new DByhteys();

if ( !empty( $_POST[ 'hyvaksy_eula' ] ) ) {
	$sql = "UPDATE kayttaja SET vahvista_eula = '0' WHERE id = ?";
	$result = $db->query( $sql, [ $_SESSION[ 'id' ] ] );

	if ( $result ) {
		header( 'Location: etusivu.php' );
		exit;
	}
}
elseif ( !empty( $_POST[ 'hylkaa_eula' ] ) ) {
	header( 'Location: logout.php?redir=10' ); // 10 == sinun pitää hyväksyä EULA
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
	$eula_txt = mb_convert_encoding( $eula_txt, "UTF-8", 'windows-1252' );
}
$css_version = filemtime( 'css/login_styles.css' );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Käyttöoikeussopimus</title>
	<link rel="stylesheet" href="css/login_styles.css?v=<?= $css_version ?>">
</head>
<body class="eula_body">

	<header class="eula_header">
		<div class="head_text">
			<h1>Käyttöoikeussopimus</h1>
			<h4>Sinun pitää hyväksyä käyttöoikeussopimus ennen sivuston käyttöä.</h4>
		</div>
		<img src="img/osax_logo.jpg" class="head_logo">
	</header>

	<textarea class="eula_scrollable_textbox" ReadOnly title="Käyttöoikeussopimus">
		<?php echo $eula_txt; ?>
	</textarea><br>
	<form method="post">
		<input type="submit" name="hyvaksy_eula" value="Hyväksy käyttöoikeussopimus" class="eula_hyvaksy">
		<input type="submit" name="hylkaa_eula" value="Hylkää, ja palaa kirjautumissivulle" class="eula_hylkaa">
	</form>

</body>
</html>
