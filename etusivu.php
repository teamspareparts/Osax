<?php
/**
 * Väliaikainen ratkaisu.
 * Sillä välin kun tätä sivua rakennetaan, niin ole hyvä ja pistä kaikki
 * ns. 'etusivulle' menevät redirectit tälle sivulle.
 */
if ( isset( $_GET['ohita'] ) ) {
	header( "Location:tuotehaku.php" );
	exit();
}

require '_start.php'; global $db, $user, $cart;

//TODO: PhpDoc
function jaottele_uutiset ( &$news ) {
	$arr = array([],[],[]);
	foreach ( $news as $item ) {
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

	$news = $arr;
}

$sql_query = "SELECT id, tyyppi, otsikko, summary, details, pvm 
			  FROM etusivu_uutinen
			  WHERE aktiivinen = TRUE AND pvm > ?
			  ORDER BY pvm DESC";
$date = new DateTime('today -300 days');
$news = $db->query( $sql_query, [$date->format("Y-m-d")], FETCH_ALL );

jaottele_uutiset( $news );

// Varmistetaan vielä lopuksi, että uusin CSS-tiedosto on käytössä. (See: cache-busting)
$css_version = filemtime( 'css/styles.css' );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Osax - Etusivu</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css?v=<?=$css_version?>">
	<style>
		div, section, ul, li {
			/*border: 1px solid;*/
		}
		.etusivu_content {
			display: flex;
			flex-direction: row;
			white-space: normal;
		}
		.etusivu_content ul{
			padding: 0 20px;
		}
		.left_section, .right_section, .center_section {
			flex-grow: 1;
			width: 30%;
			border: 1px solid;
			border-radius: 3px;
			padding: 5px;
			margin: 5px;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.etusivu_content ul {
			list-style-type: none;
		}
		.etusivu_content li {
			border-bottom: 1px dashed;
			margin-bottom: 10px;
		}
		.news_content {
			max-height: 10rem;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.news_date {
			font-style: oblique;
		}
		.news_date a {
			text-decoration: underline;
		}
	</style>
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
		<section class="left_section" <?= (empty($news[0])) ? 'hidden' : '' ?>>
			<ul>
				<?php foreach ( $news[0] as $uutinen ) : ?>
				<li>
					<h4 class="news_headline"> <?=$uutinen->otsikko?> </h4>

					<div class="news_content">
						<details>
							<summary> <?=$uutinen->summary?> </summary>
							<p> <?=$uutinen->details?> </p>
						</details>
					</div>

					<p class="news_date">
						<?=$uutinen->pvm?>
						<?=($user->isAdmin()) ? "--- Admin: <button value='{$uutinen->id}' class='nappi red'>Poista uutinen</button>" : ''?>
					</p>
				</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="center_section" <?= (empty($news[1])) ? 'hidden' : '' ?>>
			<ul>
				<?php foreach ( $news[1] as $uutinen ) : ?>
				<li>
					<h4 class="news_headline"> <?=$uutinen->otsikko?> </h4>

					<div class="news_content">
						<details>
							<summary> <?=$uutinen->summary?> </summary>
							<p> <?=$uutinen->details?> </p>
						</details>
					</div>

					<p class="news_date">
						<?=$uutinen->pvm?>
						<?=($user->isAdmin()) ? "--- Admin: <button value='{$uutinen->id}' class='nappi red'>Poista uutinen</button>" : ''?>
					</p>
				</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="right_section" <?= (empty($news[2])) ? 'hidden' : '' ?>>
			<ul>
				<?php foreach ( $news[2] as $uutinen ) : ?>
				<li>
					<h4 class="news_headline"> <?=$uutinen->otsikko?> </h4>

					<div class="news_content">
						<details>
							<summary> <?=$uutinen->summary?> </summary>
							<p> <?=$uutinen->details?> </p>
						</details>
					</div>

					<p class="news_date">
						<?=$uutinen->pvm?>
						<?=($user->isAdmin()) ? "--- Admin: <button value='{$uutinen->id}' class='nappi red'>Poista uutinen</button>" : ''?>
					</p>
				</li>
				<?php endforeach; ?>
			</ul>
		</section>
	</section>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
