<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header( "Location:etusivu.php" );
	exit();
}

$sql = "SELECT articleNo, valmistaja, tuotteen_nimi, kayttaja_id, pvm, korvaava_okey, selitys 
		FROM tuote_hankintapyynto ORDER BY pvm ASC";
$hankintapyynnot = $db->query( $sql, null, true );

$sql = "SELECT tuote_id, kayttaja_id, pvm 
		FROM tuote_ostopyynto ORDER BY pvm ASC";
$ostopyynnot = $db->query( $sql, null, true );

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty( $_POST ) ) { //Estetään formin uudelleenlähetyksen
	header( "Location: " . $_SERVER[ 'REQUEST_URI' ] );
	exit();
}
else {
	$feedback = isset( $_SESSION[ 'feedback' ] ) ? $_SESSION[ 'feedback' ] : '';
	unset( $_SESSION[ "feedback" ] );
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Hankintapyynnöt</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<?php if ( !$ostopyynnot && !$hankintapyynnot ) : ?>
		<p class="center">Ei jätettyjä ostopyyntöjä tai hankintapyyntöjä.</p>
	<?php endif;
	if ( $ostopyynnot ) : ?>
		<table style="min-width:80%;">
			<thead>
			<tr><th colspan="5" class="center" style="background-color:#1d7ae2;"> Ostopyynnöt </th></tr>
			<tr><th>#</th>
				<th>Tuote ID</th>
				<th>Käyttäjä</th>
				<th>Pvm.</th>
				<th>Käsittely:</th>
			</thead>
			<tbody>
			<?php //TODO väritys pielessä randomisti ekassa kentässä. Välillä valkoinen ?>
			<?php $i = 0; foreach ( $ostopyynnot as $op ) : ?>
				<tr><td rowspan="2" style="border-bottom:solid black 1px;"><?= ++$i ?></td>
					<td><?= $op->tuote_id ?></td>
					<td><?= $op->kayttaja_id ?></td>
					<td><?= $op->pvm ?></td>
					<td><form>
							<select name="toiminto" title="Valitse toiminto">
								<option value="0">0: Tarkistettu, ei toimenpiteitä</option>
								<option value="1">1: Lisätty valikoimaan, tiedotetaan asiakasta</option>
								<option value="2">2: Tarkistettu, säädetty parametreja</option>
							</select>
							<input type="submit" value="OK" class="nappi">
						</form>
					</td>
				</tr>
				<tr><td colspan="5"></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<br>
	<hr>
	<br>
	<?php if ( $hankintapyynnot ) : ?>
		<table style="min-width:80%;">
			<thead>
			<tr><th colspan="8" class="center" style="background-color:#1d7ae2;"> Hankintapyynnöt </th></tr>
			<tr><th></th>
				<th>Tuote</th>
				<th>Valmistaja</th>
				<th>Tuotteen nimi</th>
				<th>Käyttäjä</th>
				<th>Pvm.</th>
				<th>Korvaava okey?</th>
				<th>Käsittely:</th>
			</tr>
			</thead>
			<tbody>
			<?php $i = 0; foreach ( $hankintapyynnot as $hkp ) : ?>
				<tr><td rowspan="2" style="border-bottom:solid black 1px;"><?= ++$i ?></td>
					<td><?= $hkp->articleNo ?></td>
					<td><?= $hkp->valmistaja ?></td>
					<td><?= $hkp->tuotteen_nimi ?></td>
					<td><?= $hkp->kayttaja_id ?></td>
					<td><?= $hkp->pvm ?></td>
					<td><?= $hkp->korvaava_okey ?></td>
					<td><form>
							<select name="toiminto" title="Valitse toiminto">
								<option value="0">0: Tarkistettu, ei toimenpiteitä</option>
								<option value="1">1: Lisätty valikoimaan, tiedotetaan asiakasta</option>
								<option value="2">2: Tarkistettu, säädetty parametreja</option>
							</select>
							<input type="submit" value="OK" class="nappi">
						</form>
					</td>
				</tr>
				<tr><td colspan="6"><b>Selitys:</b> <?= !empty($hkp->selitys) ? $hkp->selitys : '[Tyhjä]' ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</main>
<script>
</script>
</body>
</html>
