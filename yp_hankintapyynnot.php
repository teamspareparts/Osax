<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header( "Location:etusivu.php" );
	exit();
}

$sql = "SELECT articleNo, valmistaja, tuotteen_nimi, kayttaja_id, DATE_FORMAT(pvm,'%Y-%m-%d') AS pvm, korvaava_okey, selitys, 
			yritys.nimi AS yritys_nimi, kayttaja.sukunimi
		FROM tuote_hankintapyynto
		JOIN kayttaja ON kayttaja.id = kayttaja_id
		JOIN yritys ON yritys.id = yritys_id
		ORDER BY pvm ASC";
$hankintapyynnot = $db->query( $sql, null, true );

$sql = "SELECT tuote_id, kayttaja_id, DATE_FORMAT(pvm,'%Y-%m-%d') AS pvm,
			tuote.tuotekoodi, tuote.nimi AS tuote_nimi, tuote.valmistaja, tuote.varastosaldo,
			yritys.nimi AS yritys_nimi, kayttaja.sukunimi
		FROM tuote_ostopyynto
		JOIN kayttaja ON kayttaja.id = kayttaja_id
		JOIN yritys ON yritys.id = yritys_id
		JOIN tuote ON tuote.id = tuote_id
		ORDER BY pvm ASC";
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
			<tr><th colspan="7" class="center" style="background-color:#1d7ae2;"> Ostopyynnöt </th></tr>
			<tr><th>#</th>
				<th>Tuote</th>
				<th></th>
				<th>Varastosaldo</th>
				<th>Käyttäjä</th>
				<th>Pvm.</th>
				<th>Käsittely:</th>
			</thead>
			<tbody>
			<?php $i = 0; foreach ( $ostopyynnot as $op ) : ?>
				<tr><td><?= ++$i ?></td>
					<td><?= $op->tuotekoodi ?></td>
					<td><?= $op->valmistaja ?><br><?= $op->tuote_nimi ?></td>
					<td><?= $op->varastosaldo ?></td>
					<td><?= $op->sukunimi ?>,<br><?= $op->yritys_nimi ?></td>
					<td><?= $op->pvm ?></td>
					<td><form>
							<select name="toiminto" title="Valitse toiminto">
								<option disabled selected>Valitse vaihtoehto:</option>
								<option value="0">0: Tarkistettu, ei toimenpiteitä</option>
								<option value="1">1: Tarkistettu, säädetty parametreja</option>
								<option value="2">2: Lisätty valikoimaan, tiedotetaan asiakasta</option>
							</select>
							<input type="submit" value="OK" class="nappi" id="op_submit">
						</form>
					</td>
				</tr>
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
			<tr><th>#</th>
				<th>Tuote</th>
				<th></th>
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
					<td><?= $hkp->valmistaja ?><br><?= $hkp->tuotteen_nimi ?></td>
					<td><?= $hkp->sukunimi ?>,<br><?= $hkp->yritys_nimi ?></td>
					<td><?= $hkp->pvm ?></td>
					<td><?= ($hkp->korvaava_okey) ? 'Kyllä' : 'Ei' ?></td>
					<td><form>
							<select name="toiminto" title="Valitse toiminto">
								<option disabled selected>Valitse vaihtoehto:</option>
								<option value="0">0: Tarkistettu, ei toimenpiteitä</option>
								<option value="1">1: Tarkistettu, säädetty parametreja</option>
								<option value="2">2: Lisätty valikoimaan, tiedotetaan asiakasta</option>
							</select>
							<input type="submit" value="OK" class="nappi" id="hkp_submit">
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

	document.addEventListener('submit',function(e){
		console.log(e);
		console.log(e.id);
		console.log(this.id);
		if(e.target) {
			e.preventDefault();
			if (e.target.id === 'op_submit') {
				console.log(e);
				console.debug(e);
				console.log('asdas');
				console.debug('asdas');
				return false;
			} else if (e.target.id === 'hkp_submit') {
				console.log(e);
				console.debug(e);
				return false;
			}
			}
		});

	/*let ajax = new XMLHttpRequest();
	ajax.open('POST', 'ajax_requests.php', true);
	ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
	ajax.send(data);*/

</script>

</body>
</html>
