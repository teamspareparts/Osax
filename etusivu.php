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
	$foos = $things = $some_more_stuff = array();
	foreach ( $news as $item ) {
		switch ( $item->tyyppi ) {
			case 0:
				$foos[] = $item;
				break;
			case 1:
				$things[] = $item;
				break;
			case 2:
				$some_more_stuff[] = $item;
				break;
			default:
				echo "Something went wrong. Uutisen tyyppiä ei löytynyt.";
		}
	}

	return [$foos, $things, $some_more_stuff];
}

$sql_query = "SELECT tyyppi, otsikko, teksti, pvm 
			  FROM etusivu_uutinen
			  WHERE aktiivinen = TRUE AND pvm > ?
			  ORDER BY pvm DESC";
$date = new DateTime('today -14 days');
$news = $db->query( $sql_query, [$date->format("Y-m-d")], FETCH_ALL );

$news = jaottele_uutiset( $news);

// Varmistetaan vielä lopuksi, että uusin CSS-tiedosto on käytössä. (See: cache-busting)
$css_version = filemtime( 'css/styles.css' );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Osax - Etusivu</title>
	<link rel="stylesheet" href="css/styles.css?v=<?=$css_version?>">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style>
		div, section, ul, li {
			/*border: 1px solid;*/
		}
		.etusivu_content {
			display: flex;
			flex-direction: row;
			white-space: normal;
		}
		.left_section, .right_section, .center_section {
			flex-grow: 1;
			width: 30%;
			border: 1px solid;
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
			/*white-space: nowrap;*/
			max-height: 6rem;
			overflow: hidden;
			text-overflow: ellipsis;
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
		<section class="left_section">
			<?php if ( $news[0] ) : ?>
				<ul>
					<?php foreach ( $news[0] as $uutinen ) : ?>
						<li>
							<div class="news_headline">
								<?= $uutinen->otsikko ?>
							</div>
							<div class="news_content">
								<p><?= $uutinen->teksti ?></p>
							</div>
							<div class="news_date">
								<p><?= $uutinen->pvm ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>

		<section class="center_section">
			<?php if ( $news[1] ) : ?>
				<ul><?php foreach ( $news[1] as $uutinen ) : ?>
						<li>
							<div class="news_headline">
								<?= $uutinen->otsikko ?>
							</div>
							<div class="news_content">
								<p><?= $uutinen->teksti ?></p>
							</div>
							<div class="news_date">
								<p><?= $uutinen->pvm ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>

		<section class="right_section">
			<?php if ( $news[2] ) : ?>
				<ul><?php foreach ( $news[2] as $uutinen ) : ?>
					<li>
						<div class="news_headline">
							<?= $uutinen->otsikko ?>
						</div>
						<div class="news_content">
							<p><?= $uutinen->teksti ?></p>
						</div>
						<div class="news_date">
							<p><?= $uutinen->pvm ?></p>
						</div>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>
	</section>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
