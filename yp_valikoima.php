<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header( "Location:etusivu.php" );
	exit();
}

/**
 * @param DByhteys $db
 * @param int      $brandNo           <p> Minkä brändin tuotteet haetaan. Jos tyhjä, hakee kaikki.
 * @param int      $hankintapaikka_id <p> Jos tyhjä, hakee kaikki.
 * @param int      $ppp               <p> Montako tuotetta kerralla ruudussa.
 * @param int      $offset            <p> Mistä tuotteesta aloitetaan palautus.
 * @param string   $order_by          <p> Minkä kolumnin mukaan järjestetään?
 * @param string   $order_direction   <p> ASC vai DESC järjestys?
 * @return array <p> [0] = row count, [1] tuotteet
 */
function haeTuotteet( DByhteys $db, /*int*/ $brandNo, /*int*/ $hankintapaikka_id, /*int*/ $ppp, /*int*/ $offset,
		/*string*/ $order_by = "tuotekoodi", /*string*/ $order_direction = "DESC" ) {

	$orders = array( ["nimi", "tuotekoodi", "varastosaldo"], ["ASC","DESC"] );
	$col_name = $orders[0][ array_search( $order_by, $orders[0] ) ];
	$order_dir = $orders[1][ array_search( $order_direction, $orders[1] ) ];

	if ( $brandNo and $hankintapaikka_id ) {
		$sql = "SELECT tuote.id, articleNo, brandNo, tuote.hankintapaikka_id, tuotekoodi,
					tilauskoodi, varastosaldo, minimimyyntiera, valmistaja, nimi,
					ALV_kanta.prosentti AS alv_prosentti, hyllypaikka, sisaanostohinta AS ostohinta, 
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
					hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_alennettu_ilman_alv,
					toimittaja_tehdassaldo.tehdassaldo
				FROM tuote
				LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				LEFT JOIN toimittaja_tehdassaldo 
					ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
				WHERE brandNo = ? AND tuote.hankintapaikka_id = ?
				ORDER BY {$col_name} {$order_dir} LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [ $brandNo, $hankintapaikka_id, $ppp, $offset ],
		                      FETCH_ALL, null, "Tuote" );

		$row_count = $db->query("SELECT COUNT(id) AS row_count FROM tuote WHERE brandNo = ? AND hankintapaikka_id = ?",
		                   [$brandNo, $hankintapaikka_id])->row_count;
	}
	elseif ( $brandNo or $hankintapaikka_id ) {
		$sql = "SELECT tuote.id, articleNo, brandNo, tuote.hankintapaikka_id, tuotekoodi,
					tilauskoodi, varastosaldo, minimimyyntiera, valmistaja, nimi,
					ALV_kanta.prosentti AS alv_prosentti, hyllypaikka, sisaanostohinta AS ostohinta, 
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
					hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_alennettu_ilman_alv,
					toimittaja_tehdassaldo.tehdassaldo
				FROM tuote
				LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				LEFT JOIN toimittaja_tehdassaldo 
					ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
				WHERE tuote.brandNo = ? OR tuote.hankintapaikka_id = ?
				ORDER BY {$col_name} {$order_dir} LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [ $brandNo, $hankintapaikka_id, $ppp, $offset ],
		                      FETCH_ALL, null, "Tuote" );

		$row_count = $db->query("SELECT COUNT(id) AS row_count FROM tuote WHERE brandNo = ? OR hankintapaikka_id = ?",
						   [$brandNo, $hankintapaikka_id])->row_count;
	}
	else {
		$sql = "SELECT tuote.id, articleNo, brandNo, tuote.hankintapaikka_id, tuotekoodi,
					tilauskoodi, varastosaldo, minimimyyntiera, valmistaja, nimi,
					ALV_kanta.prosentti AS alv_prosentti, hyllypaikka, sisaanostohinta AS ostohinta, 
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
					(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
					hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_alennettu_ilman_alv,
					toimittaja_tehdassaldo.tehdassaldo
				FROM tuote
				LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				LEFT JOIN toimittaja_tehdassaldo 
					ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
				ORDER BY {$col_name} {$order_dir} LIMIT ? OFFSET ?";
		$result = $db->query( $sql, [ $ppp, $offset ], FETCH_ALL, null, "Tuote" );
		$row_count = $db->query("SELECT COUNT(id) AS row_count FROM tuote")->row_count;
	}

	return [$row_count, $result];
}

$brand_id = !empty( $_GET[ 'brand' ] ) ? (int)$_GET[ 'brand' ] : "";
$hankintapaikka_id = !empty( $_GET[ 'hkp' ] ) ? (int)$_GET[ 'hkp' ] : "";
$page = !empty( $_GET[ 'page' ] ) ? (int)$_GET[ 'page' ] : 1; // Mikä sivu tuotelistauksessa
$products_per_page = !empty( $_GET[ 'ppp' ] ) ? (int)$_GET[ 'ppp' ] : 20; // Miten monta tuotetta per sivu näytetään.

if ( $page < 1 ) { $page = 1; }
if ( $products_per_page < 1 || $products_per_page > 5000 ) { $products_per_page = 20; }

$offset = ($page - 1) * $products_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku
$results = haeTuotteet( $db, $brand_id, $hankintapaikka_id, $products_per_page, $offset );
$total_products = $results[0];
/**
 * @var Tuote[] $tuotteet
 */
$tuotteet = $results[1];

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
					       min="1" max="<?=$total_pages?>"  maxlength="2"
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
		<tr><th colspan="10" class="center" style="background-color:#1d7ae2;">Löydetyt tuotteet</th></tr>
		<tr><th>Brändi</th>
			<th>Tuotekoodi</th>
			<th>Nimi</th>
			<th>Myyntihinta</th>
			<th>ALV 0 %</th>
			<th>Ostohinta ALV 0 %</th>
			<th>Hinnoittelukate</th>
			<th>Varastossa</th>
			<th>Myyty kpl</th>
			<th>Hyllypaikka</th>
		</tr>
		</thead>

		<tbody>
		<?php foreach ( $tuotteet as $t ) : ?>
			<tr data-id="<?= $t->id ?>">
				<td><?= $t->brandNo ?></td>
				<td><?= $t->articleNo ?></td>
				<td><?= $t->nimi ?></td>
				<td><?= $t->aHinta_toString() ?></td>
				<td><?= $t->aHintaIlmanALV_toString() ?></td>
				<td><?= $t->ostohinta ?></td>
				<td><?= round(100*(($t->a_hinta_ilman_alv - $t->ostohinta)/$t->a_hinta_ilman_alv), 0)?>&nbsp;%</td>
				<td><?= $t->varastosaldo ?></td>
				<td><?= "" ?></td>
				<td><?= $t->hyllypaikka ?></td>
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
				backwards[i].setAttribute("disabled","");
			}
		}
		if ( current_page === total_pages ) { // Tarkistetaan eteenpäin-nappien käytettävyys
			for ( i=0; i< forwards.length; i++ ) {
				forwards[i].setAttribute("disabled","");
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
