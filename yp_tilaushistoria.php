<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

/**
 * @param DByhteys $db
 * @param int[]    $pagination <p> [ipp, offset]. Itemp Per Page, ja monennestako rivistä aloitetaan palautus.
 * @param int[]    $ordering <p> [kolumni, ASC|DESC]. 1. int on kolumnin järjestys taulukossa. 2. 0=ASC, 1=DESC
 * @return array <p> [row_count, rivit]
 */
function hae_rivit( DByhteys $db, array $pagination=[20,0], array $ordering=[0,1] ) : array {

	$ipp = $pagination[0];
	$offset = $pagination[1];

	$orders = array(
		["t.id", "t.paivamaara", "y.nimi", "k.sukunimi", "summa", "t.kasitelty"],
		["ASC","DESC"]
	);
	$ordering = "{$orders[0][$ordering[0]]} {$orders[1][$ordering[1]]}";


	$sql = "SELECT t.id, t.paivamaara, t.kasitelty, k.etunimi, k.sukunimi, 
				SUM( tt.kpl * (tt.pysyva_hinta * (1+tt.pysyva_alv) * (1-tt.pysyva_alennus)) ) AS summa,
				y.nimi AS yritys
			FROM tilaus t
			LEFT JOIN kayttaja k ON k.id = t.kayttaja_id
			LEFT JOIN yritys y ON y.id = k.yritys_id
			LEFT JOIN tilaus_tuote tt ON tt.tilaus_id = t.id
			GROUP BY t.id ORDER BY {$ordering} 
			LIMIT ? OFFSET ?";

	$results = $db->query( $sql, [$ipp, $offset], FETCH_ALL, null, "Tilaus" );
	$row_count = $db->query("SELECT COUNT(id) AS row_count FROM tilaus")->row_count;

	/** @var Tilaus[] $results */

	return [$row_count, $results];
}

tarkista_admin($user);

$page = (int)($_GET[ 'page' ] ?? 1); // Monesko sivu
$items_per_page = (int)($_GET[ 'ipp' ] ?? 50); // Miten monta riviä per sivu näytetään.
$order_column = (int)($_GET[ 'col' ] ?? 0);
$order_direction = (int)($_GET[ 'dir' ] ?? 1);

if ( $page < 1 ) { $page = 1; }
if ( $items_per_page < 1 || $items_per_page > 5000 ) { $items_per_page = 50; }
$offset = ($page - 1) * $items_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku

$results = hae_rivit( $db, [$items_per_page, $offset], [$order_column,$order_direction] );
$total_items = $results[0];
/** @var Tilaus[] $tilaukset */
$tilaukset = $results[1];

if ( $total_items < $items_per_page ) {
	$items_per_page = $total_items;
}

$total_pages = ( $total_items !== 0 )
	? ceil( $total_items / $items_per_page )
	: 1;

if ( $page > $total_pages ) {
	header( "Location:yp_tilaushistoria.php?page={$total_pages}&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}" );
	exit();
}

$first_page = "?page=1&ppp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$prev_page  = "?&page=".($page-1)."&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$next_page  = "?&page=".($page+1)."&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";
$last_page  = "?&page={$total_pages}&ipp={$items_per_page}&col={$order_column}&dir={$order_direction}";
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaukset</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_tilaukset.php" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Tilaushistoria</h1>
		</section>
	</div>

	<?php if ($tilaukset) : ?>

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
				<br>Tilaukset: <?=format_number($offset,0)?>&ndash;<?=format_number( $offset + $items_per_page, 0)?> /
				<?= format_number( $total_items, 0) ?>
			</div>

			<a class="nappi" href="<?=$next_page?>"> <i class="material-icons">navigate_next</i> </a>
			<a class="nappi" href="<?=$last_page?>"> <i class="material-icons">last_page</i> </a>

			<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
				<span>Valitse sivunumero, ja paina Enter-näppäintä vaihtaaksesi sivua.</span>
				<div>Tilauksia per sivu:
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

		<table style="width:100%;">
			<thead>
			<tr><th colspan="6" class="center" style="background-color:#1d7ae2;">Kaikki tehdyt tilaukset</th></tr>
			<tr><th style="white-space:nowrap;">Tilausnro.<i class="material-icons">arrow_downward</i></th>
				<th>Päivämäärä</th>
				<th>Yritys</th>
				<th>Tilaaja</th>
				<th class="number">Summa</th>
				<th class="smaller_cell">Käsitelty</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($tilaukset as $tilaus) : ?>
				<tr data-href="tilaus_info.php?id=<?= $tilaus->id ?>" data-id="<?= $tilaus->id ?>">
					<td><?= $tilaus->id ?></td>
					<td><?= date("d.m.Y", strtotime($tilaus->paivamaara)) ?></td>
					<td><?= $tilaus->yritys ?></td>
					<td><?= $tilaus->etunimi . " " . $tilaus->sukunimi ?></td>
					<td class="number"><?= format_number($tilaus->summa + $tilaus->pysyva_rahtimaksu) ?></td>
					<td class="smaller_cell">
						<?=	$tilaus->kasitelty == 1
							? "<span style='color:green;'>OK</span>"
							: "<span style='color:red;'>EI</span>" ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<nav style="white-space: nowrap; display: inline-flex; margin:20px auto;">
			<a class="nappi" href="<?=$first_page?>"> <i class="material-icons">first_page</i> </a>
			<a class="nappi" href="<?=$prev_page?>"> <i class="material-icons">navigate_before</i> </a>

			<div class="white-bg" style="border: 1px solid; margin:auto; padding:10px; line-height: 25px;">
				Sivu: <?=$page?> / <?=$total_pages?><br>
				Tilaukset: <?=$offset?>&ndash;<?=$offset + $items_per_page?> / <?=$total_items?>
			</div>

			<a class="nappi" href="<?=$next_page?>"> <i class="material-icons">navigate_next</i> </a>
			<a class="nappi" href="<?=$last_page?>"> <i class="material-icons">last_page</i> </a>

			<div class="white-bg" style="display:flex; flex-direction:column; margin:auto 40px auto; border: 1px solid; padding:5px;">
				<span>Tilaukset per sivu: <?=$items_per_page?></span>
			</div>
		</nav>
	<?php else: ?>
	<span style="font-weight: bold;">Ei tilauksia.</span>
	<?php endif;?>

</main>

<?php require 'footer.php'; ?>

<script>
	$(function(){
		$('*[data-href]')
			.css('cursor', 'pointer')
			.click(function(){
				window.location = $(this).data('href');
				return false;
			});
	});

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
	/* Pagination end */
</script>
</body>
</html>
