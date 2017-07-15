<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}
error_reporting(E_ALL);

/** Järjestetään tuotteet artikkelinumeron mukaan
 * @param $catalog_products
 * @return array <p> Sama array sortattuna
 */
function sortProductsByName( $products ){
	//TODO: Sitten kun Janne on saanut päivitettyä kantaan tilauskoodit,
	//TODO: muutetaan vertailu artikkelinumerosta tilauskoodeihin.
	usort($products, function ($a, $b) {
		return ($a->articleNo > $b->articleNo);
	});
	return $products;
}

// Haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
$sql = "SELECT ostotilauskirja_arkisto.*, hankintapaikka.nimi AS hankintapaikka_nimi
 		FROM ostotilauskirja_arkisto
 		LEFT JOIN hankintapaikka
 			ON ostotilauskirja_arkisto.hankintapaikka_id = hankintapaikka.id
 		WHERE ostotilauskirja_arkisto.id = ? AND hyvaksytty = 0";
$otk = $db->query($sql, [$ostotilauskirja_id]);
if ( !$otk ) {
	header("Location: yp_ostotilauskirja_odottavat.php");
	exit();
}

if( isset($_POST['vastaanotettu']) ) {
	unset($_POST['vastaanotettu']);
	if ( $db->query("UPDATE ostotilauskirja_arkisto SET saapumispaiva = NOW(), hyvaksytty = 1, vastaanottaja = ?
  					WHERE id = ? ", [$user->id, $_POST['id']]) ) {

		$tuotteet = isset($_POST['tuotteet']) ? $_POST['tuotteet'] : [];

		//Päivitetään lopulliset kappalemäärät sekä ostotilaukseen että varastosaldoihin
		//TODO: Jaottele rahtimaksu touotteiden ostohintaan

		// Päivitetään ostotilauskirjan tuotteet arkistoon (kaikki kerralla)
		$sql_values_arkisto = [];
		$questionmarks = implode(',', array_fill(0, count($tuotteet), '(?, ?, ?, ?)'));
		$sql = "  INSERT INTO ostotilauskirja_tuote_arkisto (tuote_id, ostotilauskirja_id, automaatti, kpl) 
                  VALUES {$questionmarks}
                  ON DUPLICATE KEY UPDATE
                  kpl = VALUES(kpl)";

		// Päivitetään tuotteille uudet tiedot (kaikki kerralla)
		$sql_values_tuote = [];
		$questionmarks = implode(',', array_fill(0, count($tuotteet), '(?, ?, varastosaldo, ?)'));
		$sql2 = "	INSERT INTO tuote (id, varastosaldo, yhteensa_kpl, hyllypaikka)
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

		// Haetaan kaikki päivitettävät tiedot arrayhin
		foreach ($tuotteet as $tuote) {
			array_push($sql_values_arkisto, $tuote['id'], $otk->id, $tuote['automaatti'], $tuote['kpl']);
			array_push($sql_values_tuote, $tuote['id'], $tuote['kpl'], $tuote['hyllypaikka']);
		}

		// Tiedot tietokantaan
		$db->query($sql, $sql_values_arkisto);
		$db->query($sql2, $sql_values_tuote);

		// Päivitetään uusin/tarkin saapumispäivä alkuperäiselle ostotilauskirjalle
		$sql = "UPDATE ostotilauskirja
                SET oletettu_saapumispaiva = now() + INTERVAL toimitusjakso WEEK
                WHERE id = ?";
        $db->query($sql, [$otk->ostotilauskirja_id]);

		$_SESSION["feedback"] = "<p class='success'>Tuotteet lisätty varastoon.</p>";
		header("Location: yp_ostotilauskirja_odottavat.php");
		exit();
    } else {
	    $_SESSION["feedback"] = "<p class='error'>ERROR.</p>";
    }
}

if ( isset($_POST['muokkaa']) ) {
	unset($_POST['muokkaa']);
	$sql = "  UPDATE ostotilauskirja_tuote_arkisto SET kpl = ?
  	          WHERE tuote_id = ? AND ostotilauskirja_id = ? AND automaatti = ?";
	$result1 = $db->query($sql, [$_POST['kpl'], $_POST['id'], $ostotilauskirja_id, $_POST['automaatti']]);
	$sql = "UPDATE tuote SET hyllypaikka = ? WHERE id = ?";
	$result2 = $db->query($sql, [$_POST['hyllypaikka'], $_POST['id']]);
    if ( !$result1 || !$result2 ) {
        $_SESSION["feedback"] = "<p class='error'>ERROR.</p>";
	}
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
          GROUP BY tuote_id, automaatti";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
$products = sortProductsByName($products);

$sql = "  SELECT SUM(ostohinta * kpl) AS tuotteet_hinta, SUM(kpl) AS tuotteet_kpl
          FROM ostotilauskirja_tuote_arkisto
          LEFT JOIN tuote
            ON ostotilauskirja_tuote_arkisto.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY ostotilauskirja_id";
$yht = $db->query($sql, [$ostotilauskirja_id]);
$yht->hinta = $yht ? ($yht->tuotteet_hinta + $otk->rahti) : $otk->rahti;
$yht->kpl = $yht ? $yht->tuotteet_kpl : 0;

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
			<span>Varastoon saapuminen</span>
			<h1><?=$otk->tunniste?></h1>
		</section>
		<section class="napit">
			<?php if( !isset($_GET["tarkista"]) ) : ?>
				<a href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>&tarkista" class="nappi">
					Tarkasta tiedot ja hyväsky</a>
			<?php else : ?>
				<button id="merkkaa_vastaanotetuksi" class="nappi"
				        onclick="document.getElementById('submit_button').click()">
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
				<td colspan="2"></td></tr>
			<!-- Tuotteet -->
			<?php foreach ($products as $product) : ?>
				<tr><td><?=$product->tilauskoodi?></td>
					<td><?=$product->tuotekoodi?></td>
					<td><?=$product->valmistaja?><br><?=$product->nimi?></td>
					<td class="number"><?=format_number($product->kpl,true)?></td>
					<td class="number"><?=format_number($product->ostohinta)?></td>
	                <td class="number"><?=format_number($product->kokonaishinta)?></td>
					<td class="center"><?= $product->hyllypaikka?></td>
	                <td>
	                    <?php if ( $product->automaatti ) : ?>
	                        <span style="color: red"><?=$product->selite?></span>
	                    <?php else : ?>
	                        <?=$product->selite?>
	                    <?php endif;?>
	                </td>
	                <td class="toiminnot">
	                    <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
			                    '<?=$product->tuotekoodi?>', <?=$product->kpl?>,
			                    '<?=$product->hyllypaikka?>', <?=$product->automaatti?>)">
	                        Muokkaa</button>
	                </td>
				</tr>
			<?php endforeach; ?>
			<!-- Yhteensä -->
			<tr class="border_top"><td>YHTEENSÄ</td>
				<td colspan="2"></td>
				<td class="number"><?= format_number($yht->kpl,true)?></td>
	            <td></td>
				<td class="number"><?=format_number($yht->hinta)?></td>
				<td colspan="3"></td>
	        </tr>
			</tbody>
		</table>

	<?php else : ?><!-- Annetaan mahdollisuus muokata kpl ja hyllypaikkaa. -->
		<form action="" method="post" onsubmit="return " id="vastaanota_ostotilauskirja">
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
				<?php foreach ($products as $product) : ?>
					<tr>
						<td><?=$product->tilauskoodi?></td>
						<td><?=$product->tuotekoodi?></td>
						<td><?=$product->valmistaja?><br><?=$product->nimi?></td>
						<td class="number">
							<input type="number" name="tuotteet[<?=$product->id?>][kpl]" value="<?=$product->kpl?>" min="0" required>
						</td>
						<td class="number"><?=format_number($product->ostohinta)?></td>
						<td class="number"><?=format_number($product->kokonaishinta)?></td>
						<td class="center">
							<input type="text" name="tuotteet[<?=$product->id?>][hyllypaikka]" value="<?=$product->hyllypaikka?>">
						</td>
						<td>
							<?php if ( $product->automaatti ) : ?>
								<span style="color: red"><?=$product->selite?></span>
							<?php else : ?>
								<?=$product->selite?>
							<?php endif;?>
						</td>
					</tr>
					<input type="hidden" name="tuotteet[<?=$product->id?>][id]" value="<?=$product->id?>">
					<input type="hidden" name="tuotteet[<?=$product->id?>][automaatti]" value="<?=$product->automaatti?>">
				<?php endforeach; ?>
				<!-- Yhteensä -->
				<tr class="border_top"><td>YHTEENSÄ</td>
					<td colspan="2"></td>
					<td class="number"><?= format_number($yht->kpl,true)?></td>
					<td></td>
					<td class="number"><?=format_number($yht->hinta)?></td>
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

	function avaa_modal_muokkaa_tuote(tuote_id, tuotenumero, kpl, hyllypaikka, automaatti){
		Modal.open( {
			content:  '\
				<h4>Muokkaa tilatun tuotteen tietoja mikäli saapuva<br>\
				 erä ei vastaa tilattua tai merkkaa hyllypaikka.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					<label>Tuote</label>\
                    <h4 style="display: inline;">'+tuotenumero+'</h4>\
					<br><br>\
					<label>KPL</label>\
					<input name="kpl" type="number" value="'+kpl+'" title="Tilattavat kappaleet" min="0" required>\
					<br><br>\
					<label>Hyllypaikka</label>\
					<input name="hyllypaikka" type="text" value="'+hyllypaikka+'" title="Hyllypaikka">\
					<br><br>\
					<input name="id" type="hidden" value="'+tuote_id+'">\
					<input name="automaatti" type="hidden" value="'+automaatti+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
			draggable: true
		});
	}

</script>
</body>
</html>