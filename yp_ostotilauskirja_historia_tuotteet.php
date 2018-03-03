<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

//tarkastetaan GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$otk_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ( !$otk = $db->query(
		"SELECT id, rahti, hankintapaikka_id, tunniste FROM ostotilauskirja_arkisto WHERE id = ? LIMIT 1", [$otk_id],
		false, null, "Ostotilauskirja") ) {
	header("Location: yp_ostotilauskirja_historia.php");
	exit();
}



/**
 * Järjestetään tuotteet artikkelinumeron mukaan.
 * @param array $products
 * @return array <p> Sama array sortattuna
 */
function sortProductsByName( array $products ) : array {
	//TODO: Sitten kun Janne on saanut päivitettyä kantaan tilauskoodit,
	//TODO: muutetaan vertailu artikkelinumerosta tilauskoodeihin.
	usort($products, function ($a, $b) {
		return ($a->articleNo > $b->articleNo);
	});
	return $products;
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);

// Haetaan ostotilauskirjalla olevat tuotteet
$sql = "SELECT t1.*, otk_t.*,
 			(t1.sisaanostohinta * otk_t.kpl) AS kokonaishinta,
			SUM(t2.varastosaldo) AS hyllyssa_vastaavia_tuotteita,
			
			(SELECT sum(kpl)
				FROM tilaus_tuote
				INNER JOIN tilaus ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
				WHERE tuote_id = t1.id AND tilaus.maksettu = 1)
			AS vuosimyynti_kpl,
				
			(SELECT sum(tt.kpl)
				FROM tuote t3
				INNER JOIN tilaus_tuote tt ON tt.tuote_id = t3.id
				INNER JOIN tilaus ON tt.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
				WHERE t3.hyllypaikka = t1.hyllypaikka AND tilaus.maksettu = 1)
			AS vuosimyynti_hylly_kpl
				
        FROM ostotilauskirja_tuote otk_t
        INNER JOIN tuote t1
        	ON otk_t.tuote_id = t1.id
        LEFT JOIN tuote t2
        	ON t1.hyllypaikka = t2.hyllypaikka AND t2.hyllypaikka <> ''
        		AND t1.id != t2.id AND t2.aktiivinen = 1
        WHERE ostotilauskirja_id = ?
        GROUP BY tuote_id, automaatti, tilaustuote
        ORDER BY t1.articleNo";
$tuotteet = $db->query( $sql, [$otk_id], FETCH_ALL, null, "OtkTuote");
//$products = sortProductsByName($products);

// Haetaan tuotteiden yhteishinta ja kappalemäärä
$sql = "SELECT SUM(tuote.sisaanostohinta * kpl) AS tuotteet_hinta, SUM(kpl) AS tuotteet_kpl
        FROM ostotilauskirja_tuote 
        LEFT JOIN tuote ON ostotilauskirja_tuote.tuote_id = tuote.id 
        WHERE ostotilauskirja_id = ?";
$yht = $db->query($sql, [$otk_id]);
$yht_hinta = $yht ? ($yht->tuotteet_hinta + $otk->rahti) : $otk->rahti;
$yht_kpl = $yht ? $yht->tuotteet_kpl : 0;
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Ostotilauskirja</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_ostotilauskirja_historia.php" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<span>OTK Arkisto</span>
			<h1><?=$otk->tunniste?></h1>
		</section>
	</div>

	<?= $feedback?>

	<table style="min-width: 90%;">
		<thead>
			<tr><th>Tilauskoodi</th>
				<th>Tuotenumero</th>
				<th>Tuote</th>
				<th class="number">KPL</th>
				<th class="number">Varastosaldo</th>
				<th class="number">Myynti kpl</th>
				<th class="number">Hyllypaikan myynti</th>
				<th class="number">Ostohinta</th>
				<th class="number">Yhteensä</th>
				<th>Selite</th>
			</tr>
		</thead>
		<tbody>
			<!-- Rahtimaksu -->
			<tr><td colspan="2"></td>
				<td>Rahtimaksu</td>
				<td colspan="4"></td>
				<td class="number"><?=format_number($otk->rahti)?></td>
				<td class="number"><?=format_number($otk->rahti)?></td>
				<td colspan="5"></td></tr>
			<!-- Tuotteet -->
			<?php foreach ( $tuotteet as $tuote ) : ?>
				<tr class="tuote"><td><?=$tuote->tilauskoodi?></td>
					<td><?=$tuote->tuotekoodi?></td>
					<td><?=$tuote->valmistaja?><br><?=$tuote->nimi?></td>
					<td class="number"><?=format_number( $tuote->kpl, 0)?></td>
					<td class="number"><?=format_number( $tuote->varastosaldo, 0)?></td>
					<td class="number"><?=format_number( $tuote->vuosimyynti_kpl, 0)?></td>
					<td class="number"><?=format_number( $tuote->vuosimyynti_hylly_kpl, 0)?></td>
					<td class="number"><?=format_number( $tuote->sisaanostohinta)?></td>
					<td class="number"><?=format_number( $tuote->kokonaishinta)?></td>
					<td>
						<?php if ( $tuote->automaatti ) : ?>
							<span style="color:red;"><?=$tuote->selite?></span>
						<?php elseif ( $tuote->tilaustuote ) : ?>
							<span style="color:green;"><?=$tuote->selite?></span>
						<?php else : ?>
							<?=$tuote->selite?>
						<?php endif;?>
					</td>
				</tr>
			<?php endforeach;?>
			<!-- Yhteensä -->
			<tr class="border_top"><td>YHTEENSÄ</td>
				<td colspan="2"></td>
				<td class="number"><?= format_number($yht_kpl,0)?></td>
				<td colspan="4"></td>
				<td class="number"><?=format_number($yht_hinta)?></td>
				<td colspan="5"></td>
			</tr>
		</tbody>
	</table>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
