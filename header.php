<?php
/**
 * Tarkistaa onko käyttäjä kirjautunut sisään.
 * Jos käyttäjä ei ole kirjautunut sisään, funktio heittää hänet ulos.
 * Muussa tapauksessa funktio ei tee mitään.
 */
function check_login_status() {
	if ( empty($_SESSION['email']) ) {
	    header('Location: index.php?redir=4'); exit; //Et ole kirjautunut sisään
	}
}

/**
 * Tarkistaa onko käyttäjä admin.
 * Onko admin-arvo asetettu Session-datassa.
 * @return Boolean <p> Onko arvo asetettu ja TRUE.
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
			<img src="img/osax_logo.jpg" height="100" width="300" align="left" alt="No pics, plz">
			<!-- TODO: Uusi logo, muuta headerin tausta valkoiseksi -->
		</div>

		<div id="head_info">
			Tervetuloa takaisin, <?= $_SESSION['koko_nimi'] ?><br>
			Kirjautuneena: <?= $_SESSION['email'] ?>
		</div>

		<!-- TODO: Lisää ostoskorin linkki tähän, jos pystyy -->
	</div>

	<div id="navigationbar">
		<ul>
	        <li><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
	        <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
			<?php if ( is_admin() ) { ?>
		        <li><a href='yp_yritykset.php'><span>Yritykset</span></a></li>
		        <li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
		        <li><a href='toimittajat.php'><span>Toimittajat</span></a></li>
		        <li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>
				<div class="dropdown">
					<li><a><span>Muut</span></a></li> <!-- //TODO: Lisää dropdown-ikoni -->
					<div class="dropdown-content">
						<li><a href="yp_hallitse_eula.php"><span>EULA</span></a></li>
						<li><a><span>raportit</span></a></li>
						<li><a><span>jne jne...</span></a></li>
					</div>
				</div>
			<?php } else { ?>
				<li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
			<?php } ?>
			<li class="last"><a href="logout.php?redir=5"><span>Kirjaudu ulos</span></a></li>
		</ul>
	</div>
</div>
