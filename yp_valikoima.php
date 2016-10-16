<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { header("Location:etusivu.php"); exit(); }

/**
 * @param DByhteys $db
 * @param int|string $brandNo <p> Minkä brändin tuotteet haetaan.
 * 		Jos === "all", niin tulostaa kaikki tietokannassa olevat tuotteet.
 * @param int $ppp <p> Montako tuotetta kerralla ruudussa.
 * @param int $offset <p> Mistä tuotteesta aloitetaan palautus.
 * @return stdClass[] <p> Tuotteet
 */
function haeTuotteet ( DByhteys $db, /*int*/ $brandNo, /*int*/ $ppp, /*int*/ $offset ) {
	if ( $brandNo !== "all") {
		$sql = "SELECT *, (SELECT COUNT(id) FROM tuote WHERE brandNo = ?) AS row_count
				FROM tuote 
				WHERE brandNo = ?
				LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [$brandNo, $brandNo, $ppp, $offset], FETCH_ALL );
	} else {
		$sql = "SELECT *, (SELECT COUNT(id) FROM tuote) AS row_count
				FROM tuote
				LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [$ppp, $offset], FETCH_ALL );
	}

	return $result;
}

$brand = isset($_GET['brand']) ? $_GET['brand'] : "all";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Mikä sivu tuotelistauksessa
$products_per_page = isset($_GET['ppp']) ? (int)$_GET['ppp'] : 20; // Miten monta tuotetta per sivu näytetään.
$other_options = "brand={$brand}&ppp={$products_per_page}"; //URL:in GET-arvojen asettamista
if ( $page < 1 ) { $page = 1; }
if ( $products_per_page < 1 || $products_per_page > 10000 ) { $products_per_page = 20; }
$offset = ($page-1) * $products_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku

$products = haeTuotteet($db, $brand, $products_per_page, $offset);

$total_products = isset($products[0]) ? $products[0]->row_count : 0;
if ( $total_products < $products_per_page ) { $products_per_page = $total_products; }
if ( $total_products !== 0 ) { $total_pages = ceil($total_products / $products_per_page);
} else { $total_pages = 1; }
if ( $page > $total_pages ) {
	header("Location:yp_valikoima.php?brand={$brand}&page={$total_pages}&ppp={$products_per_page}"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Valikoima</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/css/bootstrap.min.css" integrity="sha384-MIwDKRSSImVFAZCVLtU0LMDdON6KVCrZHyVQQj6e8wIEJkW4tvwqXrbMIya1vriY" crossorigin="anonymous">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style>
		.material-icons { /* Jakaa sivustuksen nappien ikonin ja tekstin kahdelle riville */
			display: flex; !important;
		}
	</style>
</head>
<body>
<?php require 'header.php'; ?>

<main>
	<nav aria-label="Page navigation" class="page_nav">
		<ul class="pagination">
			<li class="page-item backward_nav" id="first_page">
				<a class="page-link" href="?<?=$other_options?>">
					<i class="material-icons">first_page</i>
					First
				</a>
			</li>
			<li class="page-item backward_nav" id="previous_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$page-1?>" aria-label="Previous">
					<i class="material-icons">arrow_back</i>
					Previous
				</a>
			</li>

			<li class="page-item active"><span class="page-link">
					Sivu:
					<form class="pageNumberForm" method="GET">
						<input type="hidden" name="brand" value="<?=$brand?>"/>
						<input type="hidden" name="ppp" value="<?=$products_per_page?>">
						<input type="number" name="page" class="pageNumber"
							   min="1" max="<?=$total_pages?>" value="<?=$page?>" maxlength="3"/>
						<input class="hidden" type="submit">
					</form>
					 / <?=$total_pages?><br>
					Tuotteet: <?=$offset?>&ndash;<?=$offset + $products_per_page?> / <?=$total_products?></span>
			</li>

			<li class="page-item forward_nav" id="next_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$page+1?>" aria-label="Next">
					<i class="material-icons">arrow_forward</i>
					Next
				</a>
			</li>
			<li class="page-item forward_nav" id="last_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$total_pages?>">
					<i class="material-icons">last_page</i>
					Last
				</a>
			</li>
		</ul>
		<div class="page_control">
			<span>Valitse sivunumero, ja paina Enter-näppäintä vaihtaaksesi sivua.</span>
			<span>Tuotteita per sivu:
				<form class="productsPerPageForm" method="GET">
					<input type="hidden" name="brand" value="<?=$brand?>"/>
					<input type="hidden" name="page" value="<?=$page?>"/>
					<select name="ppp">
						<option value="10">10</option>
						<option value="20" selected>20</option>
						<option value="30">30</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
						<option value="1000">1000</option>
						<option value="5000">5000</option>
						<option value="10000">10000 (max)</option>
					</select>
					<input type="submit" value="Muuta">
				</form>
			</span>
		</div>
	</nav>

	<p>Taulukon viimeistely tulee myöhemmin</p>
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


	<nav aria-label="Page navigation" class="page_nav">
		<ul class="pagination">
			<li class="page-item backward_nav" id="first_page">
				<a class="page-link" href="?<?=$other_options?>">
					<i class="material-icons">first_page</i>
					First
				</a>
			</li>
			<li class="page-item backward_nav" id="previous_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$page-1?>" aria-label="Previous">
					<i class="material-icons">arrow_back</i>
					Previous
				</a>
			</li>

			<li class="page-item active"><span class="page-link">
					Sivu: <?=$page?> / <?=$total_pages?><br>
					Tuotteet: <?=$offset?>&ndash;<?=$offset + $products_per_page?> / <?=$total_products?></span></li>

			<li class="page-item forward_nav" id="next_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$page+1?>" aria-label="Next">
					<i class="material-icons">arrow_forward</i>
					Next
				</a>
			</li>
			<li class="page-item forward_nav" id="last_page">
				<a class="page-link" href="?<?=$other_options?>&page=<?=$total_pages?>">
					<i class="material-icons">last_page</i>
					Last
				</a>
			</li>
		</ul>
		<div class="page_control">
			<span>Tuotteita per sivu: <?=$products_per_page?></span>
		</div>
	</nav>

</main>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/js/bootstrap.min.js"></script>
<script>
	$(document).ready(function(){
		var backwards = document.getElementsByClassName('backward_nav');
		var forwards = document.getElementsByClassName('forward_nav');
		var total_pages = <?= $total_pages?>;
		var current_page = <?= $page?>;
		var i = 0; //for-looppia varten

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
