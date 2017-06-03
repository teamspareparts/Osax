<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/**
 * Linkitetään valmistaja hankintapaikkaan. Tallennetaan vaihtoehtoinen brand_id
 * jota käytetään kyseisen valmistajan hinnastossa.
 *
 * @param DByhteys $db
 * @param int $brandId
 * @param int $hankintapaikkaId
 * @param String $brandName
 * @return array|bool|stdClass
 */
function lisaa_linkitys( DByhteys $db, /*int*/ $hankintapaikka_id, array $brand_ids, array $optional_brand_ids) {
	$sql = "DELETE FROM brandin_linkitys WHERE hankintapaikka_id = ?";
	$db->query($sql, [$hankintapaikka_id]);
	$placeholders = [];
	foreach ( $brand_ids as $index=>$brand_id ) {
		$placeholders[] = $hankintapaikka_id;
		$placeholders[] = $brand_id;
		$placeholders[] = $optional_brand_ids[$index];
	}
	$questionmarks = implode(',', array_fill( 0, count($brand_ids), '( ?, ?, ? )'));
	$sql = "  INSERT INTO brandin_linkitys
			  (hankintapaikka_id, brandi_id, brandi_kaytetty_id)
			  VALUES {$questionmarks}
			  ON DUPLICATE KEY
			  UPDATE brandi_kaytetty_id = VALUES(brandi_kaytetty_id)";
	return $db->query($sql, $placeholders);
}

// Haetaan hankintapaikan tiedot
$hankintapaikka_id = isset($_GET['hankintapaikka_id']) ? $_GET['hankintapaikka_id'] : null;
$hankintapaikka = $db->query("SELECT *, LPAD(id, 3, '0') AS id FROM hankintapaikka WHERE id = ?", [$hankintapaikka_id]);
// Poistutaan, jos hankintapaikkaa ei löydy
if ( !$hankintapaikka ) {
	header("Location:yp_hankintapaikat.php");
	exit();
}

// Haetaan kaikki brändit
$sql = "SELECT brandi.*, brandin_linkitys.brandi_kaytetty_id FROM brandi
 		LEFT JOIN brandin_linkitys
 			ON brandi.id = brandin_linkitys.brandi_id 
 				AND brandin_linkitys.hankintapaikka_id = ?
 		WHERE brandi.aktiivinen = 1
 		GROUP BY brandi.id
 		ORDER BY nimi ASC";
$brands = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);

// Haetaan linkitetyt brändit

if ( isset($_POST['lisaa_linkitys']) ) {
	lisaa_linkitys($db, $_POST['hankintapaikka_id'], $_POST['brand_ids'], $_POST['optional_ids']);
	header("Location: yp_hankintapaikka.php?hankintapaikka_id={$_POST['hankintapaikka_id']}");
	exit();
}
/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if (!empty($_POST)) {
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION["feedback"]);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
    <link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="js/jsmodal-1.0d.min.js"></script>
    <title>Toimittajat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<!-- Otsikko ja napit -->
	<section>
		<h1 class="otsikko"><?=$hankintapaikka->nimi?> - <?=$hankintapaikka->id?></h1>
		<div id="painikkeet">
			<a href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>" class="nappi grey">Takaisin</a>
			<input form="linkitys_form" type="submit" name="lisaa_linkitys" value="Valitse" class="nappi">
		</div>
		<h4>Valitse linkitettävät brändit</h4>
	</section><br>

	<!-- Brändien listaus -->
	<div class="container">
		<form action="" method="post" id="linkitys_form">
			<?php foreach ($brands as $brand) : ?>
				<div class="floating-box clickable"  data-brandId="<?=$brand->id?>">
					<div class="line">
						<label for="brand_checkbox-<?=$brand->id?>">
							<img src="<?=$brand->url?>" style="vertical-align:middle; padding-right:10px;">
							<span><?=mb_strtoupper($brand->nimi)?></span>
						</label>
						<input type="checkbox" name="brand_ids[]" value="<?=$brand->id?>"
						       id="brand_checkbox-<?=$brand->id?>" class="checkbox">
					</div>
					<div id="brand_box-<?=$brand->id?>" hidden>
						<label for="id=<?=$brand->id?>">Hinnastossa käytetty id:</label>
						<input type="text" name="optional_ids[]"
						       value="<?= !empty($brand->brandi_kaytetty_id) ? $brand->brandi_kaytetty_id : $brand->id ?>"
						       id="brand_input-<?=$brand->id?>"
						       placeholder="ID" required disabled>
					</div>
				</div>
			<?php endforeach;?>
			<input type="hidden" name="hankintapaikka_id" value="<?=$hankintapaikka->id?>">
		</form>
	</div>
</main>

</body>
</html>

<script>
	// Kun checkboxia painaa, näytetään input
    $('.checkbox').change(function () {
	    let id = $(this).val();
	    let box = $('#brand_box-'+id);
	    let input = $('#brand_input-'+id);
		if ( $(this).prop("checked") ){
			box.show();
			input.prop('disabled', false);
		} else {
		    box.hide();
            input.prop('disabled', true);
        }
    });

	// Sivun latautuessa merkataan checkboxit
    let brands = <?php echo json_encode($brands); ?>;
    brands.forEach(function (brand) {
        if ( brand.brandi_kaytetty_id ) {
            let box = $('#brand_box-'+brand.id);
            let input = $('#brand_input-'+brand.id);
            let checkbox = $('#brand_checkbox-'+brand.id);
            box.show();
            input.prop('disabled', false);
            checkbox.prop('checked', 'true');
        }
    });

</script>