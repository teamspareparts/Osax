<div class="header_container">
	<div class="header_top">
		<div id="head_logo">
			<img src="img/osax_logo.jpg" align="left" alt="No pics, plz">
		</div>

		<div id="head_info">
			Tervetuloa takaisin, <?= $user->kokoNimi() ?><br>
			Kirjautuneena: <?= $user->sahkoposti ?>
		</div>

		<div id="head_cart">
			<a href='ostoskori.php' class="flex_row">
				<div style="margin:auto 5px;">
					<i class="material-icons">shopping_cart</i>
				</div>
				<div>
					Ostoskori<br>
					Tuotteita: <span id="head_cart_tuotteet"><?= $cart->montako_tuotetta ?></span>
					(Kpl:<span id="head_cart_kpl"><?= $cart->montako_tuotetta_kpl_maara_yhteensa ?></span>)
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
