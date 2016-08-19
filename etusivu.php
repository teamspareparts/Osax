<?php
/**
 * Väliaikainen ratkaisu.
 * Sillä välin kun tätä sivua rakennetaan, niin ole hyvä ja pistä kaikki
 * ns. 'etusivulle' menevät redirectit tälle sivulle.
 */
if ( !isset( $_GET['test'] ) ) {
	header("Location:newfile.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Osax - Etusivu</title>
	<style>
		div, section {
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
			/*flex-grow: 1;*/
		}
		.center_section {
			/*flex-grow: 3;*/
		}
	</style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="main_body_container">
	<div class="ostoskori_header">Ostoskori</div>
	<div class="etusivu_content">
		<section class="left_section">
			<p>Hypetystä, eli esim "Tarjolla kilpailukykyisin hinnoin mm"
				ja sitten vaikka toimittajien logot pieninä kuvina tms,
				joilla luodaan tunnelmaa.</p>
			<p>Toimittajien ylläpitoon valinta siitä kenen logo julkaistaan.</p>
		</section>
		<section class="center_section">
			<p>Mainospalsta, eli tähän julkaistaan tarjouksia yms kamppiksia.
				Ratkaistava vielä se, että missä muodossa ( kuva / pdf / yms... )</p>
		</section>
		<section class="right_section">
			<p>Lähtevät tilaukset yms uutisvirtaa</p>
			<p>- seuraava tilaus SWF pyyhkijänsulista lähdössä 31.12.2016... vielä kerkeät mukaan!</p>
			<p>- Tilaus EGR-venttiileistä saapunut varastoon, purku käynnissä yms... </p>
		</section>
	</div>
</main>

</body>
</html>
