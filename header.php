<?php
/*
 * //TODO: This is for backwards compatibility.
 */
if ( !function_exists("check_login_status") ) {
	function check_login_status() {
		if ( empty($_SESSION['id']) ) { header('Location: index.php?redir=4'); exit; } }

	function is_admin() { return isset($_SESSION['admin']) && $_SESSION['admin'] == 1; }

	/*
	* Aloitetaan sessio ja tarkistetaan kirjautuminen jo ennen kaikkea muuta
	*/
	session_start(); check_login_status(); include 'luokat/user.class.php';

	$db = parse_ini_file("../src/tietokanta/db-config.ini.php");
	$user = new User(new DByhteys($db['user'],$db['pass'],$db['name'],$db['host']), $_SESSION['id']);
	$cart = new Ostoskori(new DByhteys($db['user'],$db['pass'],$db['name'],$db['host']), $user->yritys_id);
}
?>

<div class="header_container">
	<div class="header_top">
		<div id="head_logo">
			<img src="img/osax_logo.jpg" align="left" alt="No pics, plz">
		</div>

		<div id="head_info">
			Tervetuloa takaisin, <?= $user->kokoNimi() ?><br>
			Kirjautuneena: <?= $user->sahkoposti ?>
		</div>

		<!-- TODO: Korjaa tyylittelyä -->
		<div id="head_cart">
			<a href='ostoskori.php' class="flex_row">
				<div style="margin:auto 5px;">
					<i class="material-icons">shopping_cart</i>
				</div>
				<div>
					Ostoskori<br>
					Tuotteita: <?= $cart->montako_tuotetta ?> (Kpl:<?= $cart->montako_tuotetta_kpl_maara_yhteensa ?>)
				</div>
			</a>
		</div>
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
					<!-- //FIXME: Element <li> is not allowed here -->
					<div class="dropdown-content">
						<li><a href="yp_hallitse_eula.php"><span>EULA</span></a></li>
						<li><a><span>raportit</span></a></li>
					</div>
				</div>
			<?php } else { ?>
				<li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
			<?php } ?>
			<li class="last"><a href="logout.php?redir=5"><span>Kirjaudu ulos</span></a></li>
		</ul>
	</div>
</div>
