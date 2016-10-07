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
if ( !is_admin() || !$yritys->isValid() ) {
	header("Location:etusivu.php");	exit();
}

//poistetaanko käyttäjä
if ( isset($_POST['ids']) ){
	$db->prepare_stmt( "UPDATE kayttaja SET aktiivinen=0 WHERE id = ?" );

	foreach ($ids as $asiakas_id) {
		$db->run_prepared_stmt( [$asiakas_id] );
	}
}

$asiakkaat = hae_yrityksen_asiakkaat( $db, $yritys->id );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>Asiakkaat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko"><?=$yritys->nimi?></h1>
		<a href="yp_lisaa_asiakas.php?yritys_id=<?=$yritys->id?>" class="nappi">Lisää uusi asiakas</a>
		<br>
		<table>
			<tr><td><?=$yritys->y_tunnus?><br><?=$yritys->puhelin?><br><?=$yritys->sahkoposti?></td>
				<td><?=$yritys->katuosoite?><br><?=$yritys->postinumero?> <?=$yritys->postitoimipaikka?><br><?=$yritys->maa?></td>
			</tr>
		</table>
	</section>
	<form action="" method="post">
	<table>
		<thead>
			<tr><th>Nimi</th>
				<th>Puhelin</th>
				<th>Sähköposti</th>
				<th class=smaller_cell>Poista</th>
				<th class=smaller_cell></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $asiakkaat as $asiakas ) : ?>
			<tr data-val="<?=$asiakas->id?>">
				<td class="cell"><?=$asiakas->kokoNimi()?></td>
				<td class="cell"><?=$asiakas->puhelin?></td>
				<td class="cell"><?=$asiakas->sahkoposti?></td>
				<td><label>Valitse<input type="checkbox" name="ids[]" value="<?=$yritys->id?>"></label></td>
				<td><a href="yp_muokkaa_asiakasta.php?id='<?=$yritys->id?>'" class="nappi">Muokkaa</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
		<div id=submit><input type="submit" value="Poista valitut asiakkaat"></div>
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
