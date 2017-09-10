<?php
require '_start.php'; global $db, $user, $cart;

$yritys = new Yritys( $db, (!empty($_GET['yritys_id']) ? $_GET['yritys_id'] : null) );
if ( !$user->isAdmin() || !$yritys->isValid() ) {
	header("Location:etusivu.php");	exit();
}

//Käyttäjien poistaminen
if ( !empty($_POST['poista']) ){
	$db->prepare_stmt( "UPDATE kayttaja SET aktiivinen = 0 WHERE id = ?" );
	foreach ($_POST['ids'] as $asiakas_id) {
	    if ( $asiakas_id == $user->id ) { //Ei anneta käyttäjän poistaa itseään
	        continue;
        }
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

/** @var User[] $asiakkaat */
$asiakkaat = $rows = $db->query(
		"SELECT id, sahkoposti, etunimi, sukunimi, puhelin, 
		ifnull(date_format(viime_kirjautuminen, '%Y-%m-%d %H:%i'), 'Never') viime_kirjautuminen 
		FROM kayttaja WHERE yritys_id = ? AND aktiivinen = 1",
		[$yritys->id], FETCH_ALL, null, "User" );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Asiakkaat</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_yritykset.php">
				<i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<span>Yritys:&nbsp;&nbsp;</span><h1><?=$yritys->nimi?></h1>
		</section>
		<section class="napit">
			<a href="yp_lisaa_asiakas.php?yritys_id=<?=$yritys->id?>" class="nappi">
				Lisää yritykselle uusi asiakas</a>
		</section>
	</div>

	<?= $feedback ?>

	<section class="flex_row" style="margin:5px;">
		<p>Yrityksen tiedot:</p>
		<div style="padding: 6pt 10pt; background-color:lightgrey;">
			<?=$yritys->y_tunnus?><br><?=$yritys->puhelin?><br><?=$yritys->sahkoposti?></div>
		<div style="padding: 6pt 10pt; background-color:lightgrey;">
			<?=$yritys->katuosoite?><br><?=$yritys->postinumero?> <?=$yritys->postitoimipaikka?><br>
			<?=$yritys->maa?></div>
		<a href="yp_muokkaa_yritysta.php?id=<?=$yritys->id?>" class="nappi">Muokkaa tietoja</a>
	</section>

	<form id="poista_asiakas" method="post">
		<table style="width: 100%;">
			<thead>
				<tr> <th>Nimi</th> <th>Puhelin</th> <th>Sähköposti</th> <th>Viim. kirj.</th>
					<th class=smaller_cell>Poista</th> <th class=smaller_cell></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $asiakkaat as $asiakas ) : ?>
				<tr>
					<td data-href="tilaushistoria.php?id=<?= $asiakas->id ?>"><?=$asiakas->kokoNimi()?></td>
					<td data-href="tilaushistoria.php?id=<?= $asiakas->id ?>"><?=$asiakas->puhelin?></td>
					<td data-href="tilaushistoria.php?id=<?= $asiakas->id ?>"><?=$asiakas->sahkoposti?></td>
					<td data-href="tilaushistoria.php?id=<?= $asiakas->id ?>"><?=$asiakas->viime_kirjautuminen?></td>
					<td><label>Valitse<input type="checkbox" name="ids[]" value="<?=$asiakas->id?>">
						</label></td>
					<td><a href="yp_muokkaa_asiakasta.php?id=<?=$asiakas->id?>" class="nappi">Muokkaa</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div style="text-align:right;padding-top:10px;">
			<input type="submit" name="poista" value="Poista valitut asiakkaat" class="nappi red">
		</div>
	</form>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">

	$(document).ready(function(){
		//painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
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
