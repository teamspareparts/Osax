<?php
/**
 * @param DByhteys $db
 * @param bool[]    $rivi_tiedot <p> [0,1]. Tietoja, mitä rivejä haetaan. Kts. yp_valikoima for more complex example.
 * @param int[]    $pagination <p> [ipp, offset]. Itemp Per Page, ja monennestako rivistä aloitetaan palautus.
 * @param int[]    $ordering <p> [kolumni, ASC|DESC]. 1. int on kolumnin järjestys taulukossa. 2. 0=ASC, 1=DESC
 * @return array <p> [row_count, rivit]
 */
function hae_rivit( DByhteys $db, array $rivi_tiedot=[0,0], array $pagination=[20,0], array $ordering=[1,1] ) : array {

	$foo = $rivi_tiedot[0];
	$bar = $rivi_tiedot[1];

	$ppp = $pagination[0];
	$offset = $pagination[1];

	$orders = array(
		["brandNo", "tuotekoodi", "a_hinta", "hinta_ilman_alv", "sisaanostohinta", "hinnoittelukate", "varastosaldo", "hyllypaikka"],
		["ASC","DESC"]
	);
	$ordering = "{$orders[0][$ordering[0]]} {$orders[1][$ordering[1]]}";


	$sql = "SELECT tuote.id, articleNo, brandNo, tuote.hankintapaikka_id, tuotekoodi,
					tilauskoodi, varastosaldo, minimimyyntiera, valmistaja, tuote.nimi,
					ALV_kanta.prosentti AS alv_prosentti, hyllypaikka, sisaanostohinta AS ostohinta, 
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
					hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_alennettu_ilman_alv,
					toimittaja_tehdassaldo.tehdassaldo, paivitettava, tecdocissa, aktiivinen, vuosimyynti,
					ensimmaisen_kerran_varastossa as ensimmaisenKerranVarastossa,
					hankintapaikka.nimi as hankintapaikkaNimi, keskiostohinta, yhteensa_kpl AS yhteensaKpl
				FROM tuote
				LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				LEFT JOIN toimittaja_tehdassaldo 
					ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
				LEFT JOIN hankintapaikka ON tuote.hankintapaikka_id = hankintapaikka.id 
				ORDER BY {$ordering} LIMIT ? OFFSET ?";

	$results = $db->query( $sql, [ $ppp, $offset ], FETCH_ALL, null, "Tuote" );
	$row_count = $db->query("SELECT COUNT(id) AS row_count FROM tuote")->row_count;

	/** @var Tuote[] $results */

	foreach ( $results as $t ) {
		$t->haeTuoteryhmat( $db, true );
		$t->haeAlennukset( $db );

		/**
		 * Seuraava osio voisi olla luultavasti huomattavasti nopeampi jos lisätty ylhäällä olevaan isoon hakuun.
		 * Mutta en jaksa. Joten nopea ratkaisu. Tämä on ollut jo tarpeeksi pitkään tekeillä.
		 * --jj 180122
		 */
		/** @var \stdClass $temp_myyntitiedot */
		$temp_myyntitiedot = TuoteMyyntitiedot::tuotteenVuosimyynti( $db, $t->id );
		//debug( $temp_myyntitiedot, true );
		$t->keskimyyntihinta = $temp_myyntitiedot->keskimyyntihinta ?? 0;
		$t->vuosimyynti = $temp_myyntitiedot->kpl_maara ?? 0;
	}

	return [$row_count, $results];
}

$page = (int)($_GET[ 'page' ] ?? 1); // Monesko sivu
$items_per_page = (int)($_GET[ 'ipp' ] ?? 20); // Miten monta riviä per sivu näytetään.
$order_column = (int)($_GET[ 'col' ] ?? 1);
$order_direction = (int)($_GET[ 'dir' ] ?? 1);

if ( $page < 1 ) { $page = 1; }
if ( $items_per_page < 1 || $items_per_page > 5000 ) { $items_per_page = 20; }
$offset = ($page - 1) * $items_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku

$results = hae_rivit( $db, [0,1], [$items_per_page, $offset], [$order_column,$order_direction] );

$total_products = $results[0];
/**
 * @var Tuote[] $tuotteet
 */
$tuotteet = $results[1];

if ( $total_products < $items_per_page ) {
	$items_per_page = $total_products;
}

$total_pages = ( $total_products !== 0 )
	? ceil( $total_products / $items_per_page )
	: 1;

if ( $page > $total_pages ) {
	header( "Location:esim_pagination.php?page={$total_pages}&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}" );
	exit();
}

$first_page = "?page=1&ppp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$prev_page  = "?&page=".($page-1)."&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$next_page  = "?&page=".($page+1)."&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$last_page  = "?&page={$total_pages}&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Valikoima</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style>
	</style>
</head>
<body>

<main class="main_body_container">
	<div class="otsikko_container">
		<section class="otsikko">
			<h1>Valikoima</h1>
			<span><?= $otsikko_tulostus ?></span>
		</section>
	</div>

	<nav style="white-space: nowrap; display: inline-flex; margin:5px auto 20px;">
		<a class="nappi" href="<?=$first_page?>"> <i class="material-icons">first_page</i> </a>
		<a class="nappi" href="<?=$prev_page?>"> <i class="material-icons">navigate_before</i> </a>

		<div class="white-bg" style="border: 1px solid; margin:auto; padding:10px; line-height: 25px;">
			<form method="GET" style="display: inline;">
				<input type="hidden" name="foo" value="<?=0?>">
				<input type="hidden" name="ipp" value="<?=$items_per_page?>">
				<input type="hidden" name="col" value="<?=$order_column?>">
				<input type="hidden" name="dir" value="<?=$order_direction?>">
				<label>Sivu:
					<input type="number" name="page" value="<?=$page?>"
						   min="1" max="<?=$total_pages?>"  maxlength="2"
						   style="padding:5px; border:0; width:3.5rem; text-align: right;">
				</label>/ <?=format_number($total_pages,0)?>
				<input class="hidden" type="submit">
			</form>
			<br>Tuotteet: <?=format_number($offset,0)?>&ndash;<?=format_number( $offset + $items_per_page, 0)?> /
			<?= format_number($total_products, 0) ?>
		</div>

		<a class="nappi" href="<?=$next_page?>"> <i class="material-icons">navigate_next</i> </a>
		<a class="nappi" href="<?=$last_page?>"> <i class="material-icons">last_page</i> </a>

		<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
			<span>Valitse sivunumero, ja paina Enter-näppäintä vaihtaaksesi sivua.</span>
			<div>Tuotteita per sivu:
				<form class="productsPerPageForm" method="GET">
					<input type="hidden" name="foo" value="<?=0?>">
					<input type="hidden" name="page" value="<?=$page?>">
					<select name="ipp" title="Montako riviä sivulla näytetään kerralla.">
						<?php $x=10; for ( $i = 0; $i<5; ++$i ) {
							echo ( $x == $items_per_page )
								? "<option value='{$x}' selected>{$x}</option>"
								: "<option value='{$x}'>{$x}</option>";
							$x = ($i%2 == 0)
								? $x=$x*5
								: $x=$x*2;
						} ?>
					</select>
					<input type="submit" value="Muuta">
				</form>
			</div>
		</div>
	</nav>

	<table></table>

	<nav style="white-space: nowrap; display: inline-flex; margin:20px auto;">
		<a class="nappi" href="<?=$first_page?>"> <i class="material-icons">first_page</i> </a>
		<a class="nappi" href="<?=$prev_page?>"> <i class="material-icons">navigate_before</i> </a>

		<div class="white-bg" style="border: 1px solid; margin:auto; padding:10px; line-height: 25px;">
			Sivu: <?=$page?> / <?=$total_pages?><br>
			Rivit: <?=$offset?>&ndash;<?=$offset + $items_per_page?> / <?=$total_products?>
		</div>

		<a class="nappi" href="<?=$next_page?>"> <i class="material-icons">navigate_next</i> </a>
		<a class="nappi" href="<?=$last_page?>"> <i class="material-icons">last_page</i> </a>

		<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
			<span>Rivejä per sivu: <?=$items_per_page?></span>
		</div>
	</nav>

</main>

<script>
	/**
	 * Pagination
	 */
	let backwards = document.getElementsByClassName('backward_nav');
	let forwards = document.getElementsByClassName('forward_nav');
	let total_pages = <?= $total_pages ?>;
	let current_page = <?= $page ?>;
	let i = 0; //for-looppia varten

	if ( current_page === 1 ) { // Ei anneta mennä taaksepäin ekalla sivulla
		for ( i=0; i< backwards.length; i++ ) {
			backwards[i].setAttribute("disabled","");
		}
	}
	if ( current_page === total_pages ) { // ... sama juttu, mutta eteenpäin-nappien kohdalla (viimeinen sivu)
		for ( i=0; i< forwards.length; i++ ) {
			forwards[i].setAttribute("disabled","");
		}
	}

	$(".pageNumberForm").keypress(function(event) {
		// 13 == Enter-näppäin //TODO: tarkista miten mobiililla toimii.
		if (event.which === 13) {
			$("form.pageNumberForm").submit();
			return false;
		}
	});
</script>
