<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
if ( !$user->isAdmin() ) {
    header("Location:tuotehaku.php"); exit();
}

/** Yrityksen, ja sen asiakkaiden, deaktivointi */
if ( !empty($_POST['poista']) ) {
	foreach ($_POST['ids'] as $yritys_id) {
	    if ( $yritys_id == $user->yritys_id ) { //Ei anneta käyttäjän poistaa omaa yritystään
	        continue;
        }
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

$yritykset = $db->query( "SELECT * FROM yritys WHERE aktiivinen = 1", [], FETCH_ALL ); //TODO: Voisi olla tehokkaampi
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

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Asiakasyritykset</h1>
		</section>
		<section class="napit">
			<a class="nappi" href="yp_lisaa_yritys.php">Lisää uusi Yritys</a>
		</section>
	</div>

	<?= $feedback ?>

	<form action="" method="post" id="poista_yritys">
		<table style="width: 100%;">
			<thead>
			<tr><th colspan="6" class="center" style="background-color:#1d7ae2;">Aktiiviset yritykset</th></tr>
			<tr><th>Yritys</th>
				<th>Y-tunnus</th>
				<th>Osoite</th>
				<th>Maa</th>
				<th class="smaller_cell">Poista</th>
				<th class=smaller_cell></th></tr>
			</thead>
			<tbody>
			<?php foreach ($yritykset as $y) : ?>
				<tr>
					<td data-href="yp_asiakkaat.php?yritys_id=<?= $y->id ?>"><?= $y->nimi ?></td>
					<td data-href="yp_asiakkaat.php?yritys_id=<?= $y->id ?>"><?= $y->y_tunnus ?></td>
					<td data-href="yp_asiakkaat.php?yritys_id=<?= $y->id ?>"><?= $y->katuosoite . '<br>' . $y->postinumero . ' ' . $y->postitoimipaikka ?></td>
					<td data-href="yp_asiakkaat.php?yritys_id=<?= $y->id ?>"><?= $y->maa ?></td>
					<td class="smaller_cell">
						<label>Valitse
							<input type="checkbox" name="ids[]" value="<?=$y->id?>">
						</label>
					</td>
					<td class="smaller_cell"><a href="yp_muokkaa_yritysta.php?id=<?= $y->id ?>"><span class="nappi">Muokkaa</span></a></td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
		<div style="text-align:right; padding-top:10px;">
			<input type="submit" name="poista" value="Poista valitut Yritykset" class="nappi red">
		</div>
	</form>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
    $(document).ready(function(){
        // Painettaessa taulun riviä ohjataan
        $('*[data-href]')
            .css('cursor', 'pointer')
            .click(function(){
                window.location = $(this).data('href');
                return false;
            });

        // Poistettaessa yritystä näytetään confirm-dialogi
	    $('#poista_yritys').on('submit', function(e){
	        if ( $('input[name="ids[]"]:checked').length === 0 ) {
                e.preventDefault();
                return false;
            }
	        let c = confirm("Tämä toiminto deaktivoi yrityksen ja kaikki siihen liitetyt käyttäjät." +
		        "Haluatko varmasti jatkaa?");
	        if ( c === false ) {
                e.preventDefault();
                return false;
            }
	    });
    });

</script>

</body>
</html>
