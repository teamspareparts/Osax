<?php
require '_start.php'; global $db, $user, $cart;

//TODO: PhpDoc
function jaottele_uutiset ( &$news ) {
	$cmp_dt = new DateTime('3 days ago');
	$arr = array([],[],[]);
	foreach ( $news as $item ) {
		$item->uusi = (new DateTime($item->pvm) > $cmp_dt);
		switch ( $item->tyyppi ) {
			case 0:
				$arr[0][] = $item;
				break;
			case 1:
				$arr[1][] = $item;
				break;
			case 2:
				$arr[2][] = $item;
				break;
			default:
				echo "Something went wrong. Uutisen tyyppiä ei löytynyt.";
		}
	}

	if ( $arr[0] ) {
		$arr[0][0]->col_loc = "left_section";
	}
	if ( $arr[1] ) {
		$arr[1][0]->col_loc = "center_section";
	}
	if ( $arr[2] ) {
		$arr[2][0]->col_loc = "right_section";
	}

	$news = $arr;
}

$sql = "SELECT id, tyyppi, otsikko, summary, details, pvm, DATE_FORMAT(pvm,'%Y-%m-%d %H:00') AS simple_pvm, loppu_pvm
		FROM etusivu_uutinen
		WHERE aktiivinen = 1 AND loppu_pvm > CURDATE()
		ORDER BY pvm DESC";
$news = $db->query( $sql, null, FETCH_ALL );

jaottele_uutiset( $news );

// Varmistetaan vielä lopuksi, että uusin CSS-tiedosto on käytössä. (See: CSS cache-busting)
$css_version = filemtime( 'css/styles.css' );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Osax - Etusivu</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css?v=<?=$css_version?>">
	<link rel="stylesheet" href="./css/details-shim.min.css">
	<script src="./js/details-shim.min.js" async></script>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<section>
		<div class="tuotekoodihaku">
			<form action="tuotehaku.php" method="get" class="haku">
				<div class="inline-block">
					<label for="search">Hakunumero:</label>
					<br>
					<input id="search" type="text" name="haku" placeholder="Tuotenumero">
				</div>
				<div class="inline-block">
					<label for="numerotyyppi">Numerotyyppi:</label>
					<br>
					<select id="numerotyyppi" name="numerotyyppi">
						<option value="all">Kaikki numerot</option>
						<option value="articleNo">Tuotenumero</option>
						<option value="comparable">Tuotenumero + vertailut</option>
						<option value="oe">OE-numerot</option>
					</select>
				</div>
				<div class="inline-block">
					<label for="hakutyyppi">Hakutyyppi:</label>
					<br>
					<select id="hakutyyppi" name="exact">
						<option value="true">Tarkka</option>
						<option value="false">Samankaltainen</option>
					</select>
				</div>
				<br>
				<input class="nappi" type="submit" value="Hae">
			</form>
		</div>
		<a href="./tuotehaku.php" style="text-decoration:underline;">Linkki ajoneuvomallilla hakuun</a>
	</section>

	<?php if ( $user->isAdmin() ) : ?>
	<div class="admin_hallinta">
		<span>Admin:</span>
		<a class="nappi" href="yp_lisaa_uutinen.php">
			Lisää uusi uutinen/mainos (ohjaa uudelle sivulle)</a>
	</div>
	<?php endif; ?>

	<section class="etusivu_content">
		<?php foreach ( $news as $column ) : ?>
			<section class="<?=($column) ? "{$column[0]->col_loc}" : ''?>"
				<?=(!$column) ? "hidden" : ''?> >
				<ul>
					<?php foreach ( $column as $uutinen ) : ?>
						<li>
							<h4 class="news_headline">
								<?=$uutinen->otsikko?>
								<?=($uutinen->uusi) ? "<span style='color: red;'>NEW!</span>" : ''?>
							</h4>

							<?php if ( !empty($uutinen->details) ) : ?>
								<details class="news_content">
									<summary> <?=$uutinen->summary?> </summary>
									<p> <?=$uutinen->details?> </p>
								</details>
								<p class="small_note">Klikkaa nuolta nähdäkseksi enemmän.</p>
							<?php else : ?>
								<div class="news_content">
									<?=$uutinen->summary?>
								</div>
							<?php endif; ?>

							<p class="news_date">
								<?=$uutinen->simple_pvm?><br>
								<?=($user->isAdmin())
									? "<a href='./yp_lisaa_uutinen.php?id={$uutinen->id}'>Admin: 
										<i class='material-icons'>edit</i></a> End date: $uutinen->loppu_pvm"
									: ''?>
							</p>
						</li>
					<?php endforeach; ?>
				</ul>

			</section>

		<?php endforeach; ?>
	</section>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
