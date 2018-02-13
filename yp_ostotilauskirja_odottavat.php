<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

// Ladataan csv-tiedosto, jos tilauskirja on juuri lähetetty.
if ( isset($_SESSION["download"]) ) {
	$otk_id = (int)$_SESSION["download"];
	unset($_SESSION["download"]);
	if ( $otk_id ) {
		header( "refresh:0;URL=yp_luo_ostotilauskirjatiedosto.php?id={$otk_id}" );
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);

$sql = "SELECT *, SUM(kpl*ostohinta) AS hinta, SUM(kpl) AS kpl FROM ostotilauskirja_arkisto
 		LEFT JOIN ostotilauskirja_tuote_arkisto
 			ON ostotilauskirja_arkisto.id = ostotilauskirja_tuote_arkisto.ostotilauskirja_id
 		WHERE hyvaksytty = 0
 		GROUP BY ostotilauskirja_arkisto.id";
$ostotilauskirjat = $db->query($sql, [], FETCH_ALL);
?>

<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Saapuvat tilauskirjat</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<?= $feedback ?>

	<?php if ( $ostotilauskirjat ) : ?>
		<table>
			<thead>
			<tr><th colspan="7" class="center" style="background-color:#1d7ae2;">Varastoon saapuvat tilauskirjat</th></tr>
			<tr><th>Tunniste</th>
				<th>Lähetetty</th>
				<th>Saapuu</th>
				<th>KPL</th>
				<th>Hinta</th>
				<th>Rahti</th><th></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $ostotilauskirjat as $otk ) : ?>
				<tr>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= $otk->tunniste?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= date("d.m.Y", strtotime($otk->lahetetty))?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= $otk->kpl?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= format_number($otk->hinta)?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= format_number($otk->rahti)?></td>
                    <td><a href="yp_luo_ostotilauskirjatiedosto.php?id=<?=$otk->id?>" class="nappi">
                            Lataa</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form
	<?php else : ?>
		<p class="center">Ei lähetettyjä ostotilauskirjoja.</p>
	<?php endif; ?>

</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">

	$(document).ready(function(){

		$('*[data-href]')
			.css('cursor', 'pointer')
			.click(function(){
				window.location = $(this).data('href');
				return false;
			});
	});

</script>
</body>
</html>
