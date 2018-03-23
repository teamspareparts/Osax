<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

/**
 * Hakee kaikki ostotilauskirjat
 * @param DByhteys $db
 * @return \Ostotilauskirja[]
 */
function hae_ostotilauskirjat( DByhteys $db ) {
	$sql = "SELECT otk_a.id, tunniste, lahetetty, DATE_FORMAT(lahetetty, '%d.%m.%Y') AS lahetettyHieno, saapumispaiva,
  				DATE_FORMAT(saapumispaiva, '%d.%m.%Y') AS saapumispaivaHieno, rahti, 
  				hankintapaikka_id, hp.nimi AS hankintapaikka,
  				(SELECT IFNULL(SUM(otk_t_a.kpl*t.sisaanostohinta),0) FROM ostotilauskirja_tuote_arkisto otk_t_a 
  				LEFT JOIN tuote t ON t.id = otk_t_a.tuote_id WHERE otk_t_a.ostotilauskirja_id = otk_a.id)
  					AS hinta
			FROM ostotilauskirja_arkisto otk_a
			LEFT JOIN hankintapaikka hp ON otk_a.hankintapaikka_id = hp.id
			WHERE hyvaksytty = 1
 			ORDER BY saapumispaiva DESC";
	return $db->query( $sql, [], DByhteys::FETCH_ALL, null, "Ostotilauskirja" );
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);

$otkt = hae_ostotilauskirjat( $db );
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Ostotilauskirjat</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>

<?php require 'header.php'?>

<main class="main_body_container">
	<div class="otsikko_container">
		<section class="takaisin">
			<button class="nappi grey" id="takaisin_nappi">
				<i class="material-icons">navigate_before</i>Takaisin</button>
		</section>
		<section class="otsikko">
			<h1>Ostotilauskirjahistoria</h1>
		</section>
	</div>

	<?php if ( $otkt ) : ?>
		<table style="width:90%; margin:auto;">
			<thead>
			<tr>
				<th>Hankintapaikka</th>
				<th>Tunniste</th>
				<th>Lähetyspäivä</th>
				<th style="white-space:nowrap;">Saapumispäivä<i class="material-icons">arrow_downward</i></th>
				<th class="number">Hinta</th>
				<th class="number">Rahti</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $otkt as $otk ) : ?>
				<tr data-id="<?=$otk->id?>" data-href="yp_ostotilauskirja_historia_tuotteet.php?id=<?=$otk->id?>">
					<td data-id="<?=$otk->hankintapaikka_id?>"><?= $otk->hankintapaikka ?></td>
					<td><?= $otk->tunniste ?></td>
					<td><?= $otk->lahetettyHieno ?></td>
					<td><?= $otk->saapumispaivaHieno ?></td>
					<td class="number"><?= format_number($otk->hinta) ?></td>
					<td class="number"><?= format_number($otk->rahti) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>Ei historiaa saatavilla!</p>
	<?php endif; ?>

<?php require 'footer.php'; ?>

<script type="text/javascript">
	document.getElementById('takaisin_nappi').addEventListener('click', function() {
		window.history.back();
	});


	$('*[data-href]')
		.css('cursor', 'pointer')
		.click(function(){
			window.location = $(this).data('href');
			return false;
		});
</script>

</body>
</html>
