<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$otk = $db->query("SELECT * FROM ostotilauskirja_arkisto WHERE id = ? LIMIT 1", [$ostotilauskirja_id])) {
	header("Location: yp_ostotilauskirja_odottavat.php"); exit();
}

if( isset($_POST['vastaanotettu']) ) {

	unset($_POST['vastaanotettu']);
	if ( $db->query("UPDATE ostotilauskirja_arkisto SET saapumispaiva = NOW(), hyvaksytty = 1
  					WHERE id = ? ", [$_POST['id']]) ) {

		$ids = $_POST['tuote_ids'];
		$kpl = $_POST['kpl'];
		//Päivitetään lopulliset kappalemäärät sekä ostotilaukseen että varastosaldoihin
		//TODO: Jaottele rahtimaksu touotteiden hintaan
		foreach ($ids as $index => $id) {
			$db->query("UPDATE ostotilauskirja_tuote_arkisto SET kpl = ?
  						WHERE tuote_id = ? AND ostotilauskirja_id = ? ", [$kpl[$index], $id, $ostotilauskirja_id] );
			$db->query("UPDATE tuote SET varastosaldo = varastosaldo + ?, 
							keskiostohinta = IFNULL(((keskiostohinta*yhteensa_kpl + sisaanostohinta* ? )/
							(yhteensa_kpl + ? )),0),
						yhteensa_kpl = yhteensa_kpl + ?
						WHERE id = ? ", [$kpl[$index],  $kpl[$index], $kpl[$index], $kpl[$index], $id]);
		}


		$_SESSION["feedback"] = "<p class='success'>Tuotteet lisätty varastoon.</p>";
		header("Location: yp_ostotilauskirja_odottavat.php"); //Estää formin uudelleenlähetyksen
		exit();
	} else {
		$_SESSION["feedback"] = "<p class='error'>ERROR.</p>";
	}
}


if ( !empty($_POST) ){
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);


$sql = "  SELECT *, SUM(ostohinta * kpl) AS tuotteet_hinta FROM ostotilauskirja_tuote_arkisto
          LEFT JOIN tuote
            ON ostotilauskirja_tuote_arkisto.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY ostotilauskirja_id";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
if( $products ) get_basic_product_info($products);
$yht_hinta = !empty($products) ? ($products[0]->tuotteet_hinta + $otk->rahti) : $otk->rahti;

?>



<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko">Ostotilauskirja, matkalla...</h1>
		<div id="painikkeet">
			<a class="nappi grey" href="yp_ostotilauskirja_odottavat.php">Takaisin</a>
			<button id="merkkaa_vastaanotetuksi" class="nappi" onclick="muokkaa_ostotilauskirjaa(<?=$otk->id?>)">Tarkasta tiedot ja hyväsky</button>

		</div>
		<h3><?=$otk->tunniste?><br><span style="font-size: small;">Arvioitu saapumispäivä: <?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></span></h3>
	</section>

	<?= $feedback?>

	<table style="min-width: 90%;">
		<thead>
		<tr><th>Tuotenumero</th>
			<th>Tuote</th>
			<th class="number">KPL</th>
			<th class="number">Ostohinta</th>
		</tr>
		</thead>
		<tbody>
		<!-- Rahtimaksu -->
		<tr><td></td><td>Rahtimaksu</td><td class="number">1</td><td class="number"><?=format_euros($otk->rahti)?></td></tr>
		<!-- Tuotteet -->
		<?php foreach ($products as $product) : ?>
			<tr data-id="<?=$product->id?>">
				<td><?=$product->tuotekoodi?></td>
				<td><?=$product->brandName?><br><?=$product->articleName?></td>
				<td class="number"><?=format_integer($product->kpl)?></td>
				<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
			</tr>
		<?php endforeach;?>
		<!-- Yhteensä -->
		<tr><td style="border-top: 1px solid black;">YHTEENSÄ</td><td style="border-top: 1px solid black"></td>
			<td class="number" style="border-top: 1px solid black">1</td>
			<td class="number" style="border-top: 1px solid black"><?=format_euros($yht_hinta)?></td>
		</tr>


		</tbody>
	</table>
</main>




<script type="text/javascript">

	
	function muokkaa_ostotilauskirjaa(ostotilauskirja_id) {
		var form = document.createElement("form");
		form.setAttribute("method", "POST");
		form.setAttribute("action", "");
		form.setAttribute("name", "muokkaa_ostotilauskirjaa");
		form.setAttribute("id", "muokkaa_ostotilauskirjaa");

		//asetetaan $_POST["laheta"]
		var field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", "vastaanotettu");
		field.setAttribute("value", "true");
		form.appendChild(field);

		field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", "id");
		field.setAttribute("value", ostotilauskirja_id);
		form.appendChild(field);

		document.body.appendChild(form);

		//Muutetaan cellit inputeiksi ja luodaan kaksi post arrayta id:t ja kappaleet;
		$('tr td:nth-child(3):not(:first):not(:last)').each(function () {
			var kpl = $(this).html();
			var tuote_id = $(this).closest('tr').data('id');
			var input = $('<input name="kpl[]" form="muokkaa_ostotilauskirjaa" type="number" class="number" style="width: 40pt"/>');
			input.val(kpl);
			$(this).html(input);
			field = document.createElement("input");
			field.setAttribute("type", "hidden");
			field.setAttribute("name", "tuote_ids[]");
			field.setAttribute("value", tuote_id);
			form.appendChild(field);
		});

		//Muutetaan "Merkkaa vastaanotetuksi" -nappi Hyväksy -napiksi
		$('#merkkaa_vastaanotetuksi').text("Merkitse Vastaanotetuksi").off('onclick').click(function () {
			form.submit();
		});

		return false;
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