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
	$sql = "SELECT * FROM ostotilauskirja_arkisto ORDER BY saapumispaiva";
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
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="UTF-8">
	<title>Ostotilauskirjat</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
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

	<ul><?php foreach ( $otkt as $otk ) : ?>
		<li><?= $otk->id . ", " . $otk->tunniste . ", " . $otk->saapumispaiva . " -- " ?>
			<a href="yp_ostotilauskirja_historia.php">Linkki</a>
		</li>
		<?php endforeach; ?>
	</ul>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
	document.getElementById('takaisin_nappi').addEventListener('click', function() {
		window.history.back();
	});
</script>

</body>
</html>
