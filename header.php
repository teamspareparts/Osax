<?php
/**
 * Tarkistaa onko käyttäjä kirjautunut sisään.
 * Jos käyttäjä ei ole kirjautunut sisään, funktio heittää hänet ulos.
 * Muussa tapauksessa funktio ei tee mitään.
 */
function check_login_status() {
	if ( empty($_SESSION['email']) ) {
	    header('Location: index.php?redir=5');
	    exit;
	}	
}

/**
 * Tarkistaa onko käyttäjä admin.
 * Onko admin-arvo asetettu Session-datassa.
 * @return Boolean; onko arvo asetettu ja == 1
 */
function is_admin() {
	return isset($_SESSION['admin']) && $_SESSION['admin'] == 1;
}

session_start();
check_login_status();
?>

<div class="header_container">
	<div class="header_top">
		<div id="head_logo">
			<img src="img/rantak_varao-Logo.jpg" height="100" width="300" align="left" alt="No pics, plz">
		</div>	
		
		<div id="head_info">
			Tervetuloa takaisin, <?= $_SESSION['etunimi'] . " " . $_SESSION['sukunimi'] ?><br>
			Kirjautuneena: <?= $_SESSION['email'] ?>
		</div>	
	</div>
	
	<div id="navigationbar">
		<ul>
	        <li><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
	        <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
			<?php if ( is_admin() ) { ?>
		        <li><a href='yp_asiakkaat.php'><span>Asiakkaat</span></a></li>
		        <li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
		        <li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>
			<?php } else { ?>
				<li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
			<?php } ?>
			<li class="last"><a href="logout.php?redir=5"><span>Kirjaudu ulos</span></a></li>
		</ul>
	</div>
</div>