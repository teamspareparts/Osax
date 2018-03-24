<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

tarkista_admin( $user );

//tarkastetaan GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$sql = "SELECT otk_a.id, rahti, hankintapaikka_id, tunniste, hp.nimi AS hankintapaikka, lahetetty
		FROM ostotilauskirja_arkisto otk_a
		LEFT JOIN hankintapaikka hp ON otk_a.hankintapaikka_id = hp.id
		WHERE otk_a.id = ? LIMIT 1";
/** @var \Ostotilauskirja $otk */
$otk = $db->query($sql, [ isset($_GET['id']) ? (int)$_GET['id'] : 0 ],
                  false, null, "Ostotilauskirja");
if ( !$otk ) {
	header("Location: yp_ostotilauskirja_historia.php");
	exit();
}


// Haetaan ostotilauskirjalla olevat tuotteet
$sql = "SELECT t1.tilauskoodi, t1.tuotekoodi, t1.valmistaja, t1.nimi, t1.sisaanostohinta,
			otk_t_a.kpl, otk_t_a.selite, (t1.sisaanostohinta * otk_t_a.kpl) AS kokonaishinta,
			SUM(t_hylly.varastosaldo) AS hyllyssa_vastaavia_tuotteita,
			
			(SELECT sum(kpl)
				FROM tilaus_tuote
				INNER JOIN tilaus 
					ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB( ?, INTERVAL 1 YEAR )
				WHERE tuote_id = t1.id AND tilaus.maksettu = 1)
			AS vuosimyynti_kpl,
				
			(SELECT sum(tt.kpl)
				FROM tuote t3
				INNER JOIN tilaus_tuote tt 
					ON tt.tuote_id = t3.id
				INNER JOIN tilaus 
					ON tt.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB( ?, INTERVAL 1 YEAR )
				WHERE t3.hyllypaikka = t1.hyllypaikka AND tilaus.maksettu = 1)
			AS vuosimyynti_hylly_kpl
				
        FROM ostotilauskirja_tuote_arkisto otk_t_a
        
        INNER JOIN tuote t1
        	ON otk_t_a.tuote_id = t1.id
        	
        LEFT JOIN tuote t_hylly
        	ON t1.hyllypaikka = t_hylly.hyllypaikka AND t_hylly.hyllypaikka <> ''
        		AND t1.id != t_hylly.id AND t_hylly.aktiivinen = 1
        		
        WHERE ostotilauskirja_id = ?
        GROUP BY tuote_id, automaatti, tilaustuote
        ORDER BY t1.articleNo";
/** @var \OtkTuote[] $tuotteet */
$tuotteet = $db->query( $sql, [$otk->lahetetty,$otk->lahetetty,$otk->id], FETCH_ALL, null, "OtkTuote");

// Haetaan tuotteiden yhteishinta ja kappalemäärä
$sql = "SELECT SUM(tuote.sisaanostohinta * otk_t_a.kpl) AS tuotteet_hinta, SUM(otk_t_a.kpl) AS tuotteet_kpl
        FROM ostotilauskirja_tuote_arkisto otk_t_a
        LEFT JOIN tuote ON otk_t_a.tuote_id = tuote.id 
        WHERE ostotilauskirja_id = ?";
$yht = $db->query($sql, [$otk->id]);
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
			<button id="takaisin_nappi" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</button>
		</section>
		<section class="otsikko">
			<span>OTK Arkisto</span>
			<h1><?=$otk->tunniste?></h1>
			<span><?=$otk->hankintapaikka?></span>
		</section>
	</div>

	<table style="min-width: 90%; margin: auto;">
		<thead>
			<tr><th>Tilauskoodi</th>
				<th>Tuotenumero</th>
				<th>Tuote</th>
				<th class="number">KPL</th>
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
				<td colspan="3"></td>
				<td class="number"><?=format_number($otk->rahti)?></td>
				<td class="number"><?=format_number($otk->rahti)?></td>
				<td></td>
			</tr>
			<!-- Tuotteet -->
			<?php foreach ( $tuotteet as $tuote ) : ?>
				<tr data-id="<?=$tuote->id?>"><td><?=$tuote->tilauskoodi?></td>
					<td><?=$tuote->tuotekoodi?></td>
					<td><?=$tuote->valmistaja?><br><?=$tuote->nimi?></td>
					<td class="number"><?=format_number( $tuote->kpl, 0)?></td>
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
			<tr class="border_top">
				<td>YHTEENSÄ</td>
				<td colspan="2"></td>
				<td class="number"><?= format_number($yht_kpl,0)?></td>
				<td colspan="3"></td>
				<td class="number"><?=format_number($yht_hinta)?></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</main>

<?php require 'footer.php'; ?>

<script>
	document.getElementById('takaisin_nappi').addEventListener('click', function() {
		window.history.back();
	});
</script>
</body>
</html>
