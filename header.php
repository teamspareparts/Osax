<?php

session_start();

//
// Kirjautumiseen liittyviä apufunktioita
//

function is_logged_in() {
	return isset($_SESSION['email']);
}

function is_admin() {
	return isset($_SESSION['admin']) && $_SESSION['admin'] == 1;
}

// Tarkistetaan, onko käyttäjä kirjautunut sisään;
// jos ei, niin ohjataan suoraan kirjautumissivulle.
if (!is_logged_in()) {
    header('Location: index.php?redir=5');
    exit;
}

?>

<div class="header_container">
	<div class="header">
		<div id="logo">
			<img src="img/rantak_varao-Logo.jpg" height="100" width="300" align="left" alt="No pics, plz">
		</div>	
		
		<div id="info">
			Tervetuloa takaisin, <?= $_SESSION['etunimi'] . " " . $_SESSION['sukunimi']?><br>
			Kirjautuneena: <?= $_SESSION['email']?>
		</div>	
	</div>
	
	<div id="navigationbar">
		<ul>
	        <li><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
	        <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
			<?php
	        if (is_admin()) {
			?>
	        <li><a href='yp_asiakkaat.php'><span>Asiakkaat</span></a></li>
	        <li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
	        <li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>
	        <!-- <li><a href='yp_raportit.php'><span>Raportit</span></a></li> -->
			<?php
	        } else {
			?>
	        <li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
			<?php
			}
			?>
			<li class="last"><a href="logout.php"><span>Kirjaudu ulos</span></a></li>
		</ul>
	</div>
</div>