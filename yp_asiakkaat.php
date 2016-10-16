<?php
require '_start.php'; global $db, $user, $cart, $yritys;

/**
 * //TODO: Voisi olla tehokkaampi
 * @param DByhteys $db
 * @param int $yritys_id
 * @return User[]
 */
function hae_yrityksen_asiakkaat ( DByhteys $db, /*int*/ $yritys_id ) {
	$asiakkaat = array();
	$rows = $db->query( "SELECT id FROM kayttaja WHERE yritys_id = ?",
		[$yritys_id], DByhteys::FETCH_ALL );
	foreach ( $rows as $row ) {
		$asiakkaat[] = new User( $db, $row->id );
	}

	return $asiakkaat;
}

$yritys = new Yritys( $db, (!empty($_GET['yritys_id']) ? $_GET['yritys_id'] : null) );
if ( !$user->isAdmin() || !$yritys->isValid() ) {
	header("Location:etusivu.php");	exit();
}

/** Käyttäjien poistaminen */
if ( !empty($_POST['ids']) ){
	$db->prepare_stmt( "UPDATE kayttaja SET aktiivinen = 0 WHERE id = ?" );
	foreach ($_POST['ids'] as $asiakas_id) {
		$db->run_prepared_stmt( [$asiakas_id] );
	}
	$_SESSION['feedback'] = "<p class='success'>Asiakkaat deaktivoitu</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}

$asiakkaat = hae_yrityksen_asiakkaat( $db, $yritys->id );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Asiakkaat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko"><?=$yritys->nimi?></h1>
		<div id="painikkeet">
			<a href="yp_lisaa_asiakas.php?yritys_id=<?=$yritys->id?>" class="nappi"> Lisää uusi asiakas</a>
			<a class="nappi" href="yp_yritykset.php" style="color:#000; background-color:#c5c5c5; border-color:#000;">
				Takaisin</a>
		</div>
		<div class="flex_row" style="background-color:lightgrey; margin:5px; width:50%;">
			<div style="padding: 6pt 10pt;">
				<?=$yritys->y_tunnus?><br><?=$yritys->puhelin?><br><?=$yritys->sahkoposti?></div>
			<div style="padding: 6pt 10pt;">
				<?=$yritys->katuosoite?><br><?=$yritys->postinumero?> <?=$yritys->postitoimipaikka?><br>
				<?=$yritys->maa?></div>
		</div>
		<?= $feedback ?>
	</section>
	<table style="width: 100%;">
		<thead>
			<tr><th>Nimi</th><th>Puhelin</th><th>Sähköposti</th>
				<th class=smaller_cell>Poista</th><th class=smaller_cell></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $asiakkaat as $asiakas ) : ?>
			<tr data-val="<?=$asiakas->id?>">
				<td class="cell"><?=$asiakas->kokoNimi()?></td>
				<td class="cell"><?=$asiakas->puhelin?></td>
				<td class="cell"><?=$asiakas->sahkoposti?></td>
				<td><label>Valitse<input form="poista_asiakas" type="checkbox" name="ids[]" value="<?=$asiakas->id?>">
					</label></td>
				<td><a href="yp_muokkaa_asiakasta.php?id=<?=$asiakas->id?>" class="nappi">Muokkaa</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<form id="poista_asiakas" method="post">
		<div style="text-align:right;padding-top:10px;">
			<input type="submit" value="Poista valitut asiakkaat" class="nappi" style="background-color:red;">
		</div>
	</form>
</main>

<script type="text/javascript">

	$(document).ready(function(){
		//painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
		$('.cell')
			.css('cursor', 'pointer')
			.click(function() {
				$('tr').click(function(){
					var id = $(this).attr('data-val');
					window.document.location = 'tilaushistoria.php?id='+id;
				});
		});
	});

</script>

</body>
</html>
