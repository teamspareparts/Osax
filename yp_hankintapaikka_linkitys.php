<?php declare(strict_types=1);
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
 * @param int $hankintapaikka_id
 * @param array $brands
 * @return bool
 */
function lisaa_linkitys( DByhteys $db, int $hankintapaikka_id, array $brands ) : bool {
	if ( !$brands ) {
		return false;
	}
	$sql = "DELETE FROM brandin_linkitys WHERE hankintapaikka_id = ?";
	$db->query($sql, [$hankintapaikka_id]);
	$values = [];
	foreach ( $brands as $brand ) {
		$values[] = $hankintapaikka_id;
		$values[] = $brand['id'];
		$values[] = $brand['optional_id'];
	}
	$questionmarks = implode(',', array_fill( 0, count($brands), '( ?, ?, ? )'));
	$sql = "INSERT INTO brandin_linkitys
			(hankintapaikka_id, brandi_id, brandi_kaytetty_id)
			VALUES {$questionmarks}
			ON DUPLICATE KEY
			UPDATE brandi_kaytetty_id = VALUES(brandi_kaytetty_id)";
	return $db->query($sql, $values) ? true : false;
}

/**
 * @param DByhteys $db
 * @param $hankintapaikka_id
 * @param array $brands
 * @return array|int|stdClass
 */
function poista_tuote_linkitys( DByhteys $db, /*int*/$hankintapaikka_id, array $brands ){
	$values = [];
	if ( $brands ) {
		$questionmarks = "(" . implode(',', array_fill(0, count($brands), '?')) . ")";
	} else {
		$questionmarks = "('')";
	}
	$sql = "UPDATE tuote
			SET aktiivinen = 0
			WHERE hankintapaikka_id = ? 
				AND brandNo NOT IN {$questionmarks}";
	$values[] = $hankintapaikka_id;
	foreach ( $brands as $brand ) {
		$values[] = $brand['id'];
	}
	return $db->query($sql, $values);
}

if ( isset($_POST['lisaa_linkitys']) ) {
	$brands = isset($_POST['brands']) ? $_POST['brands'] : [];
	$hankintapaikka_id = isset($_POST['hankintapaikka_id']) ? (int)$_POST['hankintapaikka_id'] : 0;
	lisaa_linkitys($db, $hankintapaikka_id, $brands);
	poista_tuote_linkitys($db, $hankintapaikka_id, $brands);
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

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hankintapaikka->id?>" class="nappi grey">Takaisin</a>
		</section>
		<section class="otsikko">
			<span>Brändien linkitys&nbsp;&nbsp;</span>
			<h1><?=$hankintapaikka->nimi?></h1>
			<span>&nbsp;&nbsp;<?=$hankintapaikka->id?></span>
		</section>
		<section class="napit">
			<button onclick="submit_linkitys();" class="nappi">Vahvista linkitettävät brändit</button>
		</section>
	</div>
	
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
						<input type="checkbox" name="brands[<?=$brand->id?>][id]" value="<?=$brand->id?>"
						       id="brand_checkbox-<?=$brand->id?>" class="checkbox">
					</div>
					<div id="brand_box-<?=$brand->id?>" hidden>
						<label for="id=<?=$brand->id?>">Hinnastossa käytetty id:</label>
						<input type="text" name="brands[<?=$brand->id?>][optional_id]"
						       value="<?= !empty($brand->brandi_kaytetty_id) ? $brand->brandi_kaytetty_id : $brand->id ?>"
						       id="brand_input-<?=$brand->id?>" style="width: 50px;"
						       placeholder="ID" required disabled>
					</div>
				</div>
			<?php endforeach;?>
			<input type="hidden" name="hankintapaikka_id" value="<?=$hankintapaikka->id?>">
			<input type="hidden" name="lisaa_linkitys">
		</form>
	</div>
</main>

<?php require 'footer.php'; ?>

</body>
</html>

<script>

    /**
     * Vahvistus-nappia painettaessa tulostetaan vielä varoitus.
     * @returns {boolean}
     */
	function submit_linkitys() {
        let c = confirm('Poistettujen brändien tuotteet deaktivoidaan ' +
            'kyseiseltä hankintapaikalta.\n\nHaluatko varmasti jatkaa?');
        if (c === false) {
            return false;
        }
        document.getElementById("linkitys_form").submit();
        return true;
    }

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
    for ( let i=0; i<brands.length; i++ ) {
        if (brands[i].brandi_kaytetty_id) {
            let box = $('#brand_box-' + brands[i].id);
            let input = $('#brand_input-' + brands[i].id);
            let checkbox = $('#brand_checkbox-' + brands[i].id);
            box.show();
            input.prop('disabled', false);
            checkbox.prop('checked', 'true');
        }
    }

</script>
