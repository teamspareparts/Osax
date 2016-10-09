<?php
require '_start.php'; global $db, $user, $cart, $yritys;
require 'apufunktiot.php';

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");exit();
}

/**
 * Hakee tilausket TODO ??
 * @param DByhteys $db
 * @return stdClass[]
 */
function hae_tilaukset( DByhteys $db ) {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, kayttaja.etunimi, kayttaja.sukunimi, 
				SUM(tilaus_tuote.kpl * (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv))) AS summa
			FROM tilaus
			LEFT JOIN kayttaja
				ON kayttaja.id = tilaus.kayttaja_id
			LEFT JOIN tilaus_tuote
				ON tilaus_tuote.tilaus_id = tilaus.id
			WHERE tilaus.kasitelty = 0
			GROUP BY tilaus.id";
	return $db->query($sql, NULL, FETCH_ALL);
}

if ( !empty($_POST['ids']) ) {
	$db->prepare_stmt( "UPDATE tilaus SET kasitelty = 1 WHERE id = ?" );

	foreach ($_POST['ids'] as $id) {
		$db->run_prepared_stmt( [$id] );
	}
}

$tilaukset = hae_tilaukset( $db );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaukset</title>
</head>
<body>
<?php require 'header.php'; ?>
<div id=tilaukset>
	<h1 class="otsikko">Tilaukset</h1>
	<div id="painikkeet">
		<a href="yp_tilaushistoria.php"><span class="nappi">Tilaushistoria</span></a>
	</div>
	<br><br>
</div>

<div id="tilaukset">
	<div id="lista">
		<form action="yp_tilaukset.php" method="post">
			<fieldset class="lista_info">
				<p><span class="tilausnumero">Tilausnro.</span><span class="pvm">Päivämäärä</span><span class="tilaaja">Tilaaja</span><span class="sum">Summa</span>Käsitelty</p>
			</fieldset>

			<?php foreach ($tilaukset as $tilaus) : ?>
				<fieldset>
					<a href="tilaus_info.php?id=<?= $tilaus->id?>"><span class="tilausnumero"><?= $tilaus->id?>
					</span><span class="pvm"><?= date("d.m.Y", strtotime($tilaus->paivamaara))?>
					</span><span class="tilaaja"><?= $tilaus->etunimi . " " . $tilaus->sukunimi?>
					</span><span class="sum"><?= format_euros($tilaus->summa)?>
					</span></a><input type="checkbox" name="ids[]" value="<?= $tilaus->id?>">
				</fieldset>
			<?php endforeach; ?>
			<br>
			<div id=submit>
				<input type="submit" value="Merkitse käsitellyksi">
			</div>
		</form>
	</div>
</div>

</body>
</html>
