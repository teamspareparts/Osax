<?php
/**
 * Väliaikainen ratkaisu.
 * Sillä välin kun tätä sivua rakennetaan, niin ole hyvä ja pistä kaikki
 * ns. 'etusivulle' menevät redirectit tälle sivulle.
 */
if ( !isset( $_GET['test'] ) ) {
	header("Location:tuotehaku.php"); exit();
}

require 'tietokanta.php';

function debug ($var) {print_r($var);var_dump($var);}

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
			  WHERE aktiivinen = TRUE
				AND pvm > ?";
$date = new DateTime('today -10 days');
$news = $db->query( $sql_query, [$date->format("Y-m-d")], FETCH_ALL, PDO::FETCH_OBJ );

$fp_content = jaottele_uutiset($news);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<!-- https://design.google.com/icons/ -->

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

	<title>Osax - Etusivu</title>
	<style>
		div, section, ul, li {
			border: 1px solid;
		}
		.ostoskori_header {
			height: 30px;
			text-align: end;
		}
		.etusivu_content {
			display: flex;
			flex-direction: row;
			white-space: normal;
		}
		.left_section, .right_section {
			flex-grow: 1;
		}
		.center_section {
			flex-grow: 3;
		}
	</style>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<div class="ostoskori_header">Ostoskori</div>
	<?php if (is_admin()) : ?>
	<div class="admin_hallinta">
		<span>Admin:</span>
		<a class="nappi" href="yp_lisaa_uutinen.php">
			Lisää uusi uutinen/mainos (ohjaa uudelle sivulle)</a>
	</div>
	<?php endif; ?>
	<div class="etusivu_content">
		<section class="left_section">
			<?php if ( $fp_content[0] ) : ?>
			<ul><?php foreach ( $fp_content[0] as $uutinen ) : ?>
				<li>
					<div class="news_headline">
						<?= $uutinen->otsikko ?>
					</div>
					<div class="news_content">
						<?= $uutinen->teksti ?>
						<?= $uutinen->pvm ?>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php else : ?>
				<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>

		<section class="center_section">
			<?php if ( $fp_content[1] ) : ?>
			<ul><?php foreach ( $fp_content[1] as $uutinen ) : ?>
					<li>
						<div class="news_headline">
							<?= $uutinen->otsikko ?>
						</div>
						<div class="news_content">
							<?= $uutinen->teksti ?>
							<?= $uutinen->pvm ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php else : ?>
			<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>

		<section class="right_section">
			<?php if ( $fp_content[2] ) : ?>
				<ul><?php foreach ( $fp_content[2] as $uutinen ) : ?>
					<li>
						<div class="news_headline">
							<?= $uutinen->otsikko ?>
						</div>
						<div class="news_content">
							<?= $uutinen->teksti ?>
							<?= $uutinen->pvm ?>
						</div>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div> Ei sisältöä </div>
			<?php endif; ?>
		</section>
	</div>
</main>

</body>
</html>
