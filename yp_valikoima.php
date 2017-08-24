<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header( "Location:etusivu.php" );
	exit();
}

/**
 * @param DByhteys   $db
 * @param int|string $brandNo <p> Minkä brändin tuotteet haetaan.
 *                            Jos === "all", niin tulostaa kaikki tietokannassa olevat tuotteet.
 * @param int|string $hankintapaikka_id
 * @param int        $ppp     <p> Montako tuotetta kerralla ruudussa.
 * @param int        $offset  <p> Mistä tuotteesta aloitetaan palautus.
 * @return stdClass[] <p> Tuotteet
 */
function haeTuotteet( DByhteys $db, /*int*/ $brandNo, /*int*/ $hankintapaikka_id, /*int*/ $ppp, /*int*/ $offset ) {
	if ( $brandNo !== "all" && $hankintapaikka_id !== "all" ) {
		$sql = "SELECT *, (SELECT COUNT(id) FROM tuote WHERE brandNo = ?) AS row_count
				FROM tuote WHERE brandNo = ? AND hankintapaikka_id = ?
				LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [ $brandNo, $brandNo, $hankintapaikka_id, $ppp, $offset ], FETCH_ALL );
	}
	else {
		$sql = "SELECT *, (SELECT COUNT(id) FROM tuote) AS row_count
				FROM tuote LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [ $ppp, $offset ], FETCH_ALL );
	}

	return $result;
}

$brand_id = isset( $_GET[ 'brand' ] ) ? (int)$_GET[ 'brand' ] : "all";
$hankintapaikka_id = isset( $_GET[ 'hkp' ] ) ? (int)$_GET[ 'hkp' ] : "all";
$page = isset( $_GET[ 'page' ] ) ? (int)$_GET[ 'page' ] : 1; // Mikä sivu tuotelistauksessa
$products_per_page = isset( $_GET[ 'ppp' ] ) ? (int)$_GET[ 'ppp' ] : 20; // Miten monta tuotetta per sivu näytetään.

if ( $page < 1 ) { $page = 1; }
if ( $products_per_page < 1 || $products_per_page > 10000 ) { $products_per_page = 20; }

$offset = ($page - 1) * $products_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku
$products = haeTuotteet( $db, $brand_id, $hankintapaikka_id, $products_per_page, $offset );

$total_products = isset( $products[ 0 ] ) ? $products[ 0 ]->row_count : 0;

if ( $total_products < $products_per_page ) {
	$products_per_page = $total_products;
}

$total_pages = ( $total_products !== 0 )
	? ceil( $total_products / $products_per_page )
	: 1;

if ( $page > $total_pages ) {
	header( "Location:yp_valikoima.php?brand={$brand_id}&hkp={$hankintapaikka_id}&page={$total_pages}&ppp={$products_per_page}" );
	exit();
}

$first_page = "?brand={$brand_id}&hkp={$hankintapaikka_id}&ppp={$products_per_page}&page=1";
$prev_page = "?brand={$brand_id}&hkp={$hankintapaikka_id}&ppp={$products_per_page}&page=" . ($page-1);
$next_page = "?brand={$brand_id}&hkp={$hankintapaikka_id}&ppp={$products_per_page}&page=" . ($page+1);
$last_page = "?brand={$brand_id}&hkp={$hankintapaikka_id}&ppp={$products_per_page}&page={$total_pages}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Valikoima</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style>
	</style>
</head>
<body>
<?php require 'header.php'; ?>

<main class="main_body_container">
	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Valikoima</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<nav style="white-space: nowrap; display: inline-flex; margin:5px auto 20px;">
		<a class="nappi" href="<?=$first_page?>">
			<i class="material-icons">first_page</i>
		</a>
		<a class="nappi" href="<?=$prev_page?>">
			<i class="material-icons">navigate_before</i>
		</a>
		<div class="white-bg" style="border: 1px solid; margin:auto; padding:10px; line-height: 25px;">
			<form method="GET" style="display: inline;">
				<input type="hidden" name="brand" value="<?=$brand_id?>">
				<input type="hidden" name="hkp" value="<?=$hankintapaikka_id?>">
				<input type="hidden" name="ppp" value="<?=$products_per_page?>">
				<label>Sivu:
					<input type="number" name="page" value="<?=$page?>"
					       min="1"   maxlength="2"
					       style="padding:5px; border:0; width:3.5rem; text-align: right;">
				</label>/ <?=$total_pages?>
				<input class="hidden" type="submit">
			</form>
			<br> Tuotteet: <?=$offset?>&ndash;<?=$offset + $products_per_page?> / <?=$total_products?>
		</div>
		<a class="nappi" href="<?=$next_page?>">
			<i class="material-icons">navigate_next</i>
		</a>
		<a class="nappi" href="<?=$last_page?>">
			<i class="material-icons">last_page</i>
		</a>

		<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
			<span>Valitse sivunumero, ja paina Enter-näppäintä vaihtaaksesi sivua.</span>
			<div>Tuotteita per sivu:
				<form class="productsPerPageForm" method="GET">
					<input type="hidden" name="brand" value="<?=$brand_id?>">
					<input type="hidden" name="hkp" value="<?=$hankintapaikka_id?>">
					<input type="hidden" name="page" value="<?=$page?>">
					<select name="ppp" title="Montako tuotetta sivulla näytetään kerralla.">
						<?php $x=10; for ( $i = 0; $i<5; ++$i ) {
							echo ( $x == $products_per_page )
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

	<table>
		<thead>
		<tr><td>ID</td>
			<td>ArtNo</td>
			<td>BrandNo</td>
			<td>Hinta</td>
			<td>ALV</td>
			<td>Saldo</td>
			<td>Min.erä</td>
			<td>Sis.ostohinta</td>
			<td>Yht.kpl</td>
			<td>Keskios.hinta</td>
			<td>Alen.kpl</td>
			<td>Alen.%</td>
			<td>Akt.</td></tr>
		</thead>

		<tbody>
		<?php foreach ( $products as $p ) : ?>
			<tr><td><?= $p->id ?></td>
				<td><?= $p->articleNo ?></td>
				<td><?= $p->brandNo ?></td>
				<td><?= $p->hinta_ilman_ALV ?></td>
				<td><?= $p->ALV_kanta ?></td>
				<td><?= $p->varastosaldo ?></td>
				<td><?= $p->minimimyyntiera ?></td>
				<td><?= $p->sisaanostohinta ?></td>
				<td><?= $p->yhteensa_kpl ?></td>
				<td><?= $p->keskiostohinta ?></td>
				<td><?= $p->alennusera_kpl ?></td>
				<td><?= $p->alennusera_prosentti ?></td>
				<td><?= $p->aktiivinen ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<nav style="white-space: nowrap; display: inline-flex; margin:20px auto;">
		<a class="nappi" href="<?=$first_page?>">
			<i class="material-icons">first_page</i>
		</a>
		<a class="nappi" href="<?=$prev_page?>">
			<i class="material-icons">navigate_before</i>
		</a>
		<div class="white-bg" style="border: 1px solid; margin:auto; padding:10px; line-height: 25px;">
			Sivu: <?=$page?> / <?=$total_pages?><br>
			Tuotteet: <?=$offset?>&ndash;<?=$offset + $products_per_page?> / <?=$total_products?>
		</div>
		<a class="nappi" href="<?=$next_page?>">
			<i class="material-icons">navigate_next</i>
		</a>
		<a class="nappi" href="<?=$last_page?>">
			<i class="material-icons">last_page</i>
		</a>

		<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
			<span>Tuotteita per sivu: <?=$products_per_page?></span>
		</div>
	</nav>

</main>

<?php require 'footer.php'; ?>

<script>
	$(document).ready(function(){
		let backwards = document.getElementsByClassName('backward_nav');
		let forwards = document.getElementsByClassName('forward_nav');
		let total_pages = <?= $total_pages ?>;
		let current_page = <?= $page ?>;
		let i = 0; //for-looppia varten

		if ( current_page === 1 ) { //Tarkistetaan taaksepäin-nappien käytettävyys
			for ( i=0; i< backwards.length; i++ ) {
				backwards[i].className += " disabled";
			}
		}
		if ( current_page === total_pages ) { // Tarkistetaan eteenpäin-nappien käytettävyys
			for ( i=0; i< forwards.length; i++ ) {
				forwards[i].className += " disabled";
			}
		}

		$(".pageNumberForm").keypress(function(event) {
			if (event.which === 13) {
				$("form.pageNumberForm").submit();
				return false;
			}
		});

	});
</script>
</body>
</html>
