<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

$sql = "SELECT articleNo, valmistaja, tuotteen_nimi, kayttaja_id, pvm, korvaava_okey, selitys 
		FROM tuote_hankintapyynto ORDER BY pvm ASC";
$hankintapyynnot = $db->query( $sql, NULL, TRUE );

$sql = "SELECT tuote_id, kayttaja_id, pvm 
		FROM tuote_ostopyynto ORDER BY pvm ASC";
$ostopyynnot = $db->query( $sql, NULL, TRUE );

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
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
	<title>Hankintapyynnöt</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<?php if(!$ostopyynnot && !$hankintapyynnot) : ?>
		<p class="center">Ei jätettyjä ostopyyntöjä tai hankintapyyntöjä.</p>
	<?php endif;
	if ( $ostopyynnot ) : ?>
	<table style="min-width:80%;">
		<thead>
		<tr><th colspan="4" class="center" style="background-color:#1d7ae2;"> Ostopyynnöt </th></tr>
		<tr><th></th>
			<th>Tuote ID</th>
			<th>Käyttäjä</th>
			<th>Pvm.</th>
		</thead>
		<tbody>
		<?php $i = 1; foreach ( $hankintapyynnot as $hkp ) : ?>
			<tr><td rowspan="2" style="border-bottom:solid black 1px;"><?= $i++ ?></td>
				<td><?= $hkp->tuote_id ?></td>
				<td><?= $hkp->kayttaja_id ?></td>
				<td><?= $hkp->pvm ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif;
	if ( $hankintapyynnot ) : ?>
	<table style="min-width:80%;">
		<thead>
		<tr><th colspan="7" class="center" style="background-color:#1d7ae2;"> Hankintapyynnöt </th></tr>
		<tr><th></th>
			<th>Tuote</th>
			<th>Valmistaja</th>
			<th>Tuotteen nimi</th>
			<th>Käyttäjä</th>
			<th>Pvm.</th>
			<th>Korvaava okey?</th></tr>
		</thead>
		<tbody>
		<?php $i = 1; foreach ( $hankintapyynnot as $hkp ) : ?>
			<tr><td rowspan="2" style="border-bottom:solid black 1px;"><?= $i++ ?></td>
				<td><?= $hkp->articleNo ?></td>
				<td><?= $hkp->valmistaja ?></td>
				<td><?= $hkp->tuotteen_nimi ?></td>
				<td><?= $hkp->kayttaja_id ?></td>
				<td><?= $hkp->pvm ?></td>
				<td><?= $hkp->korvaava_okey ?></td>
			</tr>
			<tr><td colspan="6">Selitys: <?= !empty($hkp->selitys) ? $hkp->selitys : '[Tyhjä]' ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</main>
<script>
	$(document).ready(function(){
	});
</script>
</body>
</html>
