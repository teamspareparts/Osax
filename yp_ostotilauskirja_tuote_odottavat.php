<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/** Järjestetään tuotteet artikkelinumeron mukaan
 * @param $catalog_products
 * @return array <p> Sama array sortattuna
 */
function sortProductsByName( $products ){
	usort($products, "cmpName");
	return $products;
}

//TODO: Sitten kun Janne on saanut päivitettyä kantaan tilauskoodit,
//TODO: muutetaan vertailu artikkelinumerosta tilauskoodeihin.
/** Vertailufunktio usortille.
 * @param $a
 * @param $b
 * @return bool
 */
function cmpName($a, $b) {
	return ($a->articleNo > $b->articleNo);
}


//Tarkastetaan onko GET muuttujat sallittuja
//Haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$otk = $db->query("SELECT * FROM ostotilauskirja_arkisto WHERE id = ? AND hyvaksytty = 0 LIMIT 1",
                        [$ostotilauskirja_id])) {
	header("Location: yp_ostotilauskirja_odottavat.php");
	exit();
}

if( isset($_POST['vastaanotettu']) ) {

	unset($_POST['vastaanotettu']);
	if ( $db->query("UPDATE ostotilauskirja_arkisto SET saapumispaiva = NOW(), hyvaksytty = 1, vastaanottaja = ?
  					WHERE id = ? ", [$user->id, $_POST['id']]) ) {

		$ids = $_POST['tuote_ids'];
		$kpl = $_POST['kpl'];
		$hyllypaikka = $_POST['hyllypaikat'];
		$automaatti = $_POST['automaatti'];
		//Päivitetään lopulliset kappalemäärät sekä ostotilaukseen että varastosaldoihin
		//TODO: Jaottele rahtimaksu touotteiden ostohintaan
		foreach ($ids as $index => $id) {
			$db->query("UPDATE ostotilauskirja_tuote_arkisto SET kpl = ?
  						WHERE tuote_id = ? AND ostotilauskirja_id = ? AND automaatti = ?",
                [$kpl[$index], $id, $ostotilauskirja_id, $automaatti[$index]] );
			$db->query("UPDATE tuote SET varastosaldo = varastosaldo + ?, 
							keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + sisaanostohinta* ? )/
							(yhteensa_kpl + ? )),0),
						yhteensa_kpl = yhteensa_kpl + ?, hyllypaikka = ?,
						ensimmaisen_kerran_varastossa = IF(ISNULL(ensimmaisen_kerran_varastossa), now(), ensimmaisen_kerran_varastossa)
						WHERE id = ? ",
				[$kpl[$index],  $kpl[$index], $kpl[$index], $kpl[$index], $hyllypaikka[$index], $id]);
		}

		//Päivitetään uusin/tarkin saapumispäivä alkuperäiselle ostotilauskirjalle
		$sql = "UPDATE ostotilauskirja 
                SET oletettu_saapumispaiva = now() + INTERVAL toimitusjakso WEEK
                WHERE id = ?";
        $db->query($sql, [$otk->ostotilauskirja_id]);

		$_SESSION["feedback"] = "<p class='success'>Tuotteet lisätty varastoon.</p>";
		header("Location: yp_ostotilauskirja_odottavat.php"); //Estää formin uudelleenlähetyksen
		exit();
	} else {
		$_SESSION["feedback"] = "<p class='error'>ERROR.</p>";
	}
}

if( isset($_POST['muokkaa']) ) {
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
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
} else {
	$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
	unset($_SESSION["feedback"]);
}


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
	<section>
		<h1 class="otsikko">Varastoon saapuminen</h1>
		<div id="painikkeet">
			<a class="nappi grey" href="yp_ostotilauskirja_odottavat.php">Takaisin</a>
			<button id="merkkaa_vastaanotetuksi" class="nappi" onclick="muokkaa_ostotilauskirjaa(<?=$otk->id?>)">Tarkasta tiedot ja hyväsky</button>

		</div>
		<h3><?=$otk->tunniste?><br><span style="font-size: small;">Arvioitu saapumispäivä: <?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></span></h3>
	</section>

	<?= $feedback?>

	<table style="min-width: 90%;">
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
		<tr><td></td><td></td><td>Rahtimaksu</td><td></td>
			<td class="number"><?=format_euros($otk->rahti)?></td>
            <td class="number"><?=format_euros($otk->rahti)?></td>
            <td class="center">---</td><td></td><td></td></tr>
		<!-- Tuotteet -->
		<?php foreach ($products as $product) : ?>
			<tr data-id="<?=$product->id?>" data-automaatti="<?=$product->automaatti?>">
                <td><?=$product->tilauskoodi?></td>
				<td><?=$product->tuotekoodi?></td>
				<td><?=$product->valmistaja?><br><?=$product->nimi?></td>
				<td class="number"><?=format_integer($product->kpl)?></td>
				<td class="number"><?=format_euros($product->ostohinta)?></td>
                <td class="number"><?=format_euros($product->kokonaishinta)?></td>
				<td class="center"><?= $product->hyllypaikka?></td>
                <td>
                    <?php if ( $product->automaatti ) : ?>
                        <span style="color: red"><?=$product->selite?></span>
                    <?php else : ?>
                        <?=$product->selite?>
                    <?php endif;?>
                </td>
                <td class="toiminnot">
                    <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>, '<?=$product->tuotekoodi?>',
                        <?=$product->kpl?>, '<?=$product->hyllypaikka?>', <?=$product->automaatti?>)">
                        Muokkaa</button>
                </td>
			</tr>
		<?php endforeach;?>
		<!-- Yhteensä -->
		<tr><td style="border-top: 1px solid black;">YHTEENSÄ</td><td style="border-top: 1px solid black"></td><td style="border-top: 1px solid black"></td>
			<td class="number" style="border-top: 1px solid black"><?= format_integer($yht->kpl)?></td>
            <td style="border-top: 1px solid black"></td>
			<td class="number" style="border-top: 1px solid black"><?=format_euros($yht->hinta)?></td>
			<td style="border-top: 1px solid black"></td>
            <td style="border-top: 1px solid black"></td>
            <td style="border-top: 1px solid black"></td>
        </tr>


		</tbody>
	</table>
</main>




<script type="text/javascript">

	
	function muokkaa_ostotilauskirjaa(ostotilauskirja_id) {
		//Luodaan form
		let form = document.createElement("form");
		form.setAttribute("method", "POST");
		form.setAttribute("action", "");
		form.setAttribute("name", "muokkaa_ostotilauskirjaa");
		form.setAttribute("id", "muokkaa_ostotilauskirjaa");

		//POST["vastaanotettu"]
		let field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", "vastaanotettu");
		field.setAttribute("value", "true");
		form.appendChild(field);

		//POST["id"]
		field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", "id");
		field.setAttribute("value", ostotilauskirja_id);
		form.appendChild(field);

        //POST["tuote_id"] & POST["automaatti"];
		$('tbody tr:not(:first):not(:last)').each(function () {
			let tuote_id = $(this).data('id');
			field = document.createElement("input");
			field.setAttribute("type", "hidden");
			field.setAttribute("name", "tuote_ids[]");
			field.setAttribute("value", tuote_id);
			form.appendChild(field);
            let automaatti = $(this).data('automaatti');
            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "automaatti[]");
            field.setAttribute("value", automaatti);
            form.appendChild(field);
		});
		//Muutetaan hyllypaikka-cellit inputeiksi
		$('tr td:nth-child(7):not(:first):not(:last)').each(function () {
			let hyllypaikka = $(this).html();
			let input = $('<input name="hyllypaikat[]" form="muokkaa_ostotilauskirjaa" type="text" class="number" style="width: 60pt; float: right;">');
			input.val(hyllypaikka);
			$(this).html(input);
		});
		//Muutetaan hyllypaikka-cellit inputeiksi
		$('tr td:nth-child(4):not(:first):not(:last)').each(function () {
			let kpl = $(this).html();
			let input = $('<input name="kpl[]" form="muokkaa_ostotilauskirjaa" type="number" min="0" class="number" style="width: 40pt; float: right;">');
			input.val(kpl);
			$(this).html(input);
		});
		//Poistetaan muokkaa -napit
		$('tr td:nth-child(9):not(:first):not(:last)').each(function () {
			$(this).html("");
		});
		document.body.appendChild(form);

		//Muutetaan "Merkkaa vastaanotetuksi" -nappi Hyväksy -napiksi
		$('#merkkaa_vastaanotetuksi').text("Merkitse Vastaanotetuksi").off('onclick').click(function () {
			form.submit();
		});

		return false;
	}


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



	$(document).ready(function(){

		$('*[data-href]')
			.css('cursor', 'pointer')
			.click(function(){
				window.location = $(this).data('href');
				return false;
			});

	});


</script>
</body>
</html>