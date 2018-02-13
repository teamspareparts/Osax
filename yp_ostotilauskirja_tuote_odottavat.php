<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/** Järjestetään tuotteet artikkelinumeron mukaan.
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

/**
 * Haetaan ostotilauskirja annetun id:n perusteella.
 * @param DByhteys $db
 * @param int $id
 * @return stdClass|null
 */
function getTilauskirja( DByhteys $db, int $id ) {
	$sql = "SELECT ostotilauskirja_arkisto.*, hankintapaikka.nimi AS hankintapaikka_nimi
 			FROM ostotilauskirja_arkisto
 			LEFT JOIN hankintapaikka
 				ON ostotilauskirja_arkisto.hankintapaikka_id = hankintapaikka.id
 			WHERE ostotilauskirja_arkisto.id = ? AND hyvaksytty = 0";
	$otk = $db->query($sql, [$id]);
	return $otk ? $otk : null;
}

// Haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$otk = getTilauskirja($db, $ostotilauskirja_id);
if ( !$otk ) {
	header("Location: yp_ostotilauskirja_odottavat.php");
	exit();
}

if ( isset($_POST['vastaanotettu']) ) {
	$tuotteet = isset($_POST['tuotteet']) ? $_POST['tuotteet'] : [];
	if ( $tuotteet ) {

		// Päivitetään arkistoidun tilauskirjan tietoja
		$sql = "UPDATE ostotilauskirja_arkisto
				SET saapumispaiva = NOW(), hyvaksytty = 1, vastaanottaja = ?
				WHERE id = ?";
		$db->query($sql, [$user->id, $_POST['id']]);

		//Päivitetään lopulliset kappalemäärät sekä ostotilaukseen että varastosaldoihin
		//TODO: Jaottele rahtimaksu touotteiden ostohintaan?

		$sql_values_arkisto = []; // Arkistoitavan ostotilauskirjan tuotteet
		$sql_values_tuote = []; // Päivitettävät tuotetiedot
		// Haetaan kaikki päivitettävät tiedot arrayhin
		foreach ($tuotteet as $tuote) {
			array_push($sql_values_arkisto, $tuote['id'], $otk->id, $tuote['automaatti'], $tuote['tilaustuote'], $tuote['kpl']);

			// Tilaustuotteille ei päivitetä saldoa!
			if ( $tuote['tilaustuote'] ) {
				array_push($sql_values_tuote, $tuote['id'], 0, $tuote['hyllypaikka']);
			} else {
				array_push($sql_values_tuote, $tuote['id'], $tuote['kpl'], $tuote['hyllypaikka']);
			}
		}

		// Päivitetään ostotilauskirjan tuotteet arkistoon (kaikki kerralla)
		$questionmarks = implode(',', array_fill(0, count($tuotteet), '(?, ?, ?, ?, ?)'));
		$sql = "INSERT INTO ostotilauskirja_tuote_arkisto (tuote_id, ostotilauskirja_id, automaatti, tilaustuote, kpl) 
                VALUES {$questionmarks}
                ON DUPLICATE KEY UPDATE
                	kpl = VALUES(kpl)";
		$db->query($sql, $sql_values_arkisto);

		// Päivitetään tuotteille uudet tiedot (kaikki kerralla)
		$questionmarks = implode(',', array_fill(0, count($tuotteet), '(?, ?, varastosaldo, ?)'));
		$sql = "	INSERT INTO tuote (id, varastosaldo, yhteensa_kpl, hyllypaikka)
					VALUES {$questionmarks}
                    ON DUPLICATE KEY UPDATE
                        varastosaldo = varastosaldo + VALUES(varastosaldo), 
					    keskiostohinta = IFNULL( 
					    	( (keskiostohinta*yhteensa_kpl + sisaanostohinta*VALUES(yhteensa_kpl) ) /
					    	 (yhteensa_kpl + VALUES(yhteensa_kpl)) )
					    	 , sisaanostohinta ),
					    yhteensa_kpl = yhteensa_kpl + VALUES(yhteensa_kpl),
					    hyllypaikka = VALUES(hyllypaikka),
					    ensimmaisen_kerran_varastossa =
					    	IF( ISNULL(ensimmaisen_kerran_varastossa), now(), ensimmaisen_kerran_varastossa )";
		$db->query($sql, $sql_values_tuote);

		// Päivitetään uusin/tarkin saapumispäivä alkuperäiselle ostotilauskirjalle
		$sql = "UPDATE ostotilauskirja
                SET oletettu_saapumispaiva = now() + INTERVAL toimitusjakso WEEK
                WHERE id = ?";
        $db->query($sql, [$otk->ostotilauskirja_id]);

		$_SESSION["feedback"] = "<p class='success'>Tuotteet lisätty varastoon.</p>";
		header("Location: yp_ostotilauskirja_odottavat.php");
		exit();
    } else {
		$_SESSION["feedback"] = "<p class='error'>Tilauskirja on tyhjä.</p>";
    }
}

if ( isset($_POST['muokkaa_tuote']) ) {
	// Muokataan tuotetta ostotilauskirjalla
	$sql = "UPDATE ostotilauskirja_tuote_arkisto
		  	SET kpl = ?
  	        WHERE tuote_id = ? AND ostotilauskirja_id = ?
  	        	AND automaatti = ? AND tilaustuote = ?";
	$result1 = $db->query($sql, [$_POST['kpl'], $_POST['id'], $ostotilauskirja_id, $_POST['automaatti'], $_POST['tilaustuote']]);
	// Muokataan tuotteen hyllypaikkaa
	$sql = "UPDATE tuote SET hyllypaikka = ? WHERE id = ?";
	$result2 = $db->query($sql, [$_POST['hyllypaikka'], $_POST['id']]);
    if ( !$result1 || !$result2 ) {
        $_SESSION["feedback"] = "<p class='error'>Muokkaus epäonnistui.</p>";
	}
}
if ( isset($_POST['muokkaa_rahti']) ) {
	$sql = "UPDATE ostotilauskirja_arkisto SET rahti = ? WHERE id = ?";
	$db->query($sql, [$_POST['rahti'], $ostotilauskirja_id]);
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);


$sql = "  SELECT *, tuote.sisaanostohinta*ostotilauskirja_tuote_arkisto.kpl AS kokonaishinta FROM ostotilauskirja_tuote_arkisto
          LEFT JOIN tuote
            ON ostotilauskirja_tuote_arkisto.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY tuote_id, automaatti, tilaustuote";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
$products = sortProductsByName($products);

$sql = "  SELECT SUM(ostohinta * kpl) AS tuotteet_hinta, SUM(kpl) AS tuotteet_kpl
          FROM ostotilauskirja_tuote_arkisto
          LEFT JOIN tuote
            ON ostotilauskirja_tuote_arkisto.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY ostotilauskirja_id";
$yht = $db->query($sql, [$ostotilauskirja_id]);
$yht_hinta = $yht ? ($yht->tuotteet_hinta + $otk->rahti) : $otk->rahti;
$yht_kpl = $yht ? $yht->tuotteet_kpl : 0;

?>



<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_ostotilauskirja_odottavat.php">Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Varastoon saapuminen</h1>
		</section>
		<section class="napit">
			<?php if( !isset($_GET["tarkista"]) ) : ?>
				<a href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>&tarkista" class="nappi">
					Tarkasta tiedot ja hyväsky</a>
			<?php else : ?>
				<button class="nappi" onclick="document.getElementById('submit_button').click()">
					Merkitse vastaanotetuksi</button>
			<?php endif; ?>
		</section>
	</div>

	<h3><?=$otk->tunniste?><br>
		<?=$otk->hankintapaikka_id?> - <?=$otk->hankintapaikka_nimi?><br>
		<span style="font-size: small;">Arvioitu saapumispäivä:
			<?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></span></h3>

	<?= $feedback ?>

	<?php if ( !isset($_GET['tarkista']) ) : ?>
		<table>
			<thead>
			<tr><th>Tilauskoodi</th>
	            <th>Tuotenumero</th>
				<th>Tuote</th>
				<th class="number">KPL</th>
				<th class="number">Ostohinta</th>
	            <th class="number">Yhteensä</th>
				<th>Hyllypaikka</th>
	            <th>Selite</th>
	            <th></th>
			</tr>
			</thead>
			<tbody>
			<!-- Rahtimaksu -->
			<tr><td colspan="2"></td>
				<td>Rahtimaksu</td>
				<td></td>
				<td class="number"><?=format_number($otk->rahti)?></td>
	            <td class="number"><?=format_number($otk->rahti)?></td>
	            <td class="center">---</td>
				<td></td>
				<td class="toiminnot">
					<button class="nappi" onclick="avaa_modal_muokkaa_rahtimaksu(<?=$otk->rahti?>)">
						Muokkaa</button></td></tr>
			<!-- Tuotteet -->
			<?php foreach ($products as $product) : ?>
				<tr><td><?=$product->tilauskoodi?></td>
					<td><?=$product->tuotekoodi?></td>
					<td><?=$product->valmistaja?><br><?=$product->nimi?></td>
					<td class="number"><?=format_number($product->kpl,0)?></td>
					<td class="number"><?=format_number($product->ostohinta)?></td>
	                <td class="number"><?=format_number($product->kokonaishinta)?></td>
					<td class="center"><?= $product->hyllypaikka?></td>
	                <td>
	                    <?php if ( $product->automaatti ) : ?>
	                        <span style="color: red"><?=$product->selite?></span>
	                    <?php elseif ( $product->tilaustuote ) : ?>
		                    <span style="color: green"><?=$product->selite?></span>
	                    <?php else : ?>
	                        <?=$product->selite?>
	                    <?php endif;?>
	                </td>
	                <td class="toiminnot">
	                    <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
			                    '<?=$product->tuotekoodi?>', <?=$product->kpl?>,
			                    '<?=$product->hyllypaikka?>', <?=$product->automaatti?>,
			                    <?=$product->tilaustuote?>)">
		                    Muokkaa</button>
	                </td>
				</tr>
			<?php endforeach; ?>
			<!-- Yhteensä -->
			<tr class="border_top"><td>YHTEENSÄ</td>
				<td colspan="2"></td>
				<td class="number"><?= format_number($yht_kpl,0)?></td>
	            <td></td>
				<td class="number"><?=format_number($yht_hinta)?></td>
				<td colspan="3"></td>
	        </tr>
			</tbody>
		</table>

	<?php else : ?><!-- Annetaan mahdollisuus muokata kpl ja hyllypaikkaa. -->
		<form action="" method="post">
			<table>
				<thead>
				<tr><th>Tilauskoodi</th>
					<th>Tuotenumero</th>
					<th>Tuote</th>
					<th class="number">KPL</th>
					<th class="number">Ostohinta</th>
					<th class="number">Yhteensä</th>
					<th>Hyllypaikka</th>
					<th>Selite</th>
				</tr>
				</thead>
				<tbody>
				<!-- Rahtimaksu -->
				<tr><td colspan="2"></td>
					<td>Rahtimaksu</td>
					<td></td>
					<td class="number"><?=format_number($otk->rahti)?></td>
					<td class="number"><?=format_number($otk->rahti)?></td>
					<td class="center">---</td>
					<td></td></tr>
				<!-- Tuotteet -->
				<?php foreach ($products as $index=>$product) : ?>
					<tr>
						<td><?=$product->tilauskoodi?></td>
						<td><?=$product->tuotekoodi?></td>
						<td><?=$product->valmistaja?><br><?=$product->nimi?></td>
						<td class="number">
							<?php if ( $product->tilaustuote ) : ?>
								<span style="color: red">Ei varastoon</span><br>
							<?php endif; ?>
							<input type="number" name="tuotteet[<?=$index?>][kpl]" value="<?=$product->kpl?>"
							       class="kpl" min="0" required>
						</td>
						<td class="number"><?=format_number($product->ostohinta)?></td>
						<td class="number"><?=format_number($product->kokonaishinta)?></td>
						<td class="center">
							<input type="text" name="tuotteet[<?=$index?>][hyllypaikka]" value="<?=$product->hyllypaikka?>">
						</td>
						<td>
							<?php if ( $product->automaatti ) : ?>
								<span style="color: red"><?=$product->selite?></span>
							<?php elseif ( $product->tilaustuote ) : ?>
								<span style="color: green"><?=$product->selite?></span>
							<?php else : ?>
								<?=$product->selite?>
							<?php endif;?>
						</td>
					</tr>
					<input type="hidden" name="tuotteet[<?=$index?>][id]" value="<?=$product->id?>">
					<input type="hidden" name="tuotteet[<?=$index?>][automaatti]" value="<?=$product->automaatti?>">
					<input type="hidden" name="tuotteet[<?=$index?>][tilaustuote]" value="<?=$product->tilaustuote?>">
				<?php endforeach; ?>
				<!-- Yhteensä -->
				<tr class="border_top"><td>YHTEENSÄ</td>
					<td colspan="2"></td>
					<td class="number"><?= format_number($yht_kpl,0)?></td>
					<td></td>
					<td class="number"><?=format_number($yht_hinta)?></td>
					<td colspan="2"></td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" name="id" value="<?=$otk->id?>">
			<input type="submit" name="vastaanotettu" id="submit_button" hidden>
		</form>
	<?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">

    /**
     * Modal tuotteen tietojen muokkaamiseen.
     * @param tuote_id
     * @param tuotenumero
     * @param kpl
     * @param hyllypaikka
     * @param automaatti
     * @param tilaustuote
     */
	function avaa_modal_muokkaa_tuote( tuote_id, tuotenumero, kpl, hyllypaikka, automaatti, tilaustuote ) {
		Modal.open( {
			content:  '\
				<h4>Muokkaa tilatun tuotteen tietoja mikäli saapuva<br>\
				 erä ei vastaa tilattua tai merkkaa hyllypaikka.</h4>\
				<hr>\
				<br>\
				<form action="" method="post">\
					<label>Tuote</label>\
                    <h4 class="inline-block">'+tuotenumero+'</h4>\
					<br><br>\
					<label>KPL</label>\
					<input type="number" name="kpl" value="'+kpl+'" title="Tilattavat kappaleet" min="0" required>\
					<br><br>\
					<label>Hyllypaikka</label>\
					<input type="text" name="hyllypaikka" value="'+hyllypaikka+'" title="Hyllypaikka">\
					<br><br>\
					<input type="hidden" name="id" value="'+tuote_id+'">\
					<input type="hidden" name="automaatti" value="'+automaatti+'">\
					<input type="hidden" name="tilaustuote" value="'+tilaustuote+'">\
					<input type="submit" name="muokkaa_tuote" value="Muokkaa" class="nappi"> \
				</form>\
				',
			draggable: true
		});
	}

    /**
     * Modal rahtimaksun muokkamiseen.
     * @param rahti
     */
    function avaa_modal_muokkaa_rahtimaksu( rahti ) {
        Modal.open( {
            content:  '\
				<h4>Muokkaa rahtimaksua.</h4>\
				<hr>\
				<br>\
				<form action="" method="post">\
					<label>Rahtimaksu (€)</label>\
					<input type="number" name="rahti" value="'+rahti+'" min="0" step="0.01" class="dialogi-kentta" required>\
					<br><br>\
					<input type="submit" name="muokkaa_rahti" value="Muokkaa" class="nappi"> \
				</form>\
				',
            draggable: true
        });
    }

</script>
</body>
</html>
