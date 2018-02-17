<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

/**
 * Hakee kaikki ostotilauskirjat
 * @param DByhteys $db
 * @return \stdClass[]
 */
function hae_ostotilauskirjat( DByhteys $db ) {
	$sql = "SELECT * FROM ostotilauskirja_arkisto otk_a";
	return $db->query( $sql, [], DByhteys::FETCH_ALL );
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
			<a class="nappi grey" href="ostoskori.php?cancel">
				<i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Ostotilauskirjahistoria</h1>
		</section>
	</div>

	<?php foreach ( $otkt as $otk ) : ?>
		<ul>
			<li><?php debug( $otk ) ?></li>
		</ul>
	<?php endforeach; ?>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
</script>

</body>
</html>
