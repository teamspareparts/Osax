<?php
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';
if( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}
$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, 
			SUM( tilaus_tuote.kpl * (tilaus_tuote.pysyva_hinta*(1+tilaus_tuote.pysyva_alv))) AS summa 
		FROM tilaus 
		LEFT JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id=tilaus.id
		GROUP BY tilaus.id
		ORDER BY tilaus.id DESC";
$tilaukset = $db->query( $query, NULL, FETCH_ALL );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaukset</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">

</main>
<div id="tilaukset">
	<div id="lista">

		<form>
			<fieldset class="lista_info">
				<p><span class="tilausnumero">Tilausnro.</span><span class="pvm">Päivämäärä</span><span class="tilaaja">Tilaaja</span><span class="sum">Summa</span>Käsitelty</p>
			</fieldset>

			<?php
			foreach ($tilaukset as $tilaus){
				echo '<fieldset>';
				echo '<a href="tilaus_info.php?id=' . $tilaus->id . '"><span class="tilausnumero">' . $tilaus->id .
					'</span><span class="pvm">' . date("d.m.Y", strtotime($tilaus->paivamaara)) .
					'</span><span class="tilaaja">' . $tilaus->etunimi . ' ' . $tilaus->sukunimi .
					'</span><span class="sum">' . format_euros($tilaus->summa) .
					'</span>';
				if ($tilaus->kasitelty == 1) {
					echo 'OK';
				} else {
					echo '<span style="color:red">EI</span>';
				}
				echo '</a></fieldset>';
			}
			?>
		</form>
	</div>
</div>
</body>
</html>
