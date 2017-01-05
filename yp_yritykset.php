<?php
require '_start.php'; global $db, $user, $cart;
if ( !$user->isAdmin() ) {
    header("Location:tuotehaku.php"); exit();
}

/** Yrityksen, ja sen asiakkaiden, deaktivointi */
if ( !empty($_POST['ids']) ) {
	foreach ($_POST['ids'] as $yritys_id) {
		$query = "UPDATE yritys SET aktiivinen = 0 WHERE id = ?";
		$db->query($query, [$yritys_id]);
		$query = "UPDATE kayttaja SET aktiivinen = 0 WHERE yritys_id = ?";
		$db->query($query, [$yritys_id]);
	}
	$_SESSION['feedback'] = "<p class='success'>Yritys (ja sen asiakkaat) deaktivoitu</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
    $feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
    unset($_SESSION["feedback"]);
}

$yritykset = $db->query( "SELECT * FROM yritys WHERE aktiivinen = 1", NULL, FETCH_ALL ); //TODO: Voisi olla tehokkaampi
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
	<title>Yritykset</title>
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">
	<table style="width: 100%;">
		<thead>
		<tr><th colspan="6" class="center" style="background-color:#1d7ae2;">Asiakasyritykset</th></tr>
		<tr><th>Yritys</th>
			<th>Y-tunnus</th>
			<th>Osoite</th>
			<th>Maa</th>
			<th class="smaller_cell">Poista</th>
			<th class=smaller_cell></th></tr>
		</thead>
		<tbody>
		<?php foreach ($yritykset as $y) : ?>
			<tr data-id="<?= $y->id ?>">
				<td class="cell"><?= $y->nimi ?></td>
				<td class="cell"><?= $y->y_tunnus ?></td>
				<td class="cell"><?= $y->katuosoite . '<br>' . $y->postinumero . ' ' . $y->postitoimipaikka ?></td>
				<td class="cell"><?= $y->maa ?></td>
				<td class="smaller_cell">
					<label>Valitse
						<input form="deactivate_company" type="checkbox" name="ids[]" value="<?= $y->id ?>">
					</label>
				</td>
				<td class="smaller_cell"><a href="yp_muokkaa_yritysta.php?id=<?= $y->id ?>"><span class="nappi">Muokkaa</span></a></td>
			</tr>
		<?php endforeach;?>
		</tbody>
	</table>
	<section class="flex_row" style="margin:20px 40px;">
		<span style="flex-grow:5; text-align:start;">
			<a class="nappi" href="yp_lisaa_yritys.php">Lisää uusi Yritys</a>
		</span>

		<form action="" method="post" id="deactivate_company">
			<span style="flex-grow:5; text-align:end;">
				<input type="submit" value="Poista valitut Yritykset" class="nappi red">
			</span>
		</form>
	</section>
</main>


<script type="text/javascript">
    $(document).ready(function(){
        //painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
        $('.cell').click(function(){
            $('tr').click(function(){
                let id = $(this).attr('data-id');
                window.document.location = 'yp_asiakkaat.php?yritys_id='+id;
            });
        })
        .css('cursor', 'pointer');
    });

</script>

</body>
</html>
