<div id="header">
	<img alt="Logo" src="img/rantak_varao-Logo.jpg" height="100" width="300" align="left">
	<!-- <h1>Logo</h1> -->
	<p class="kirjautuneena">Kirjautuneena:</p>
	<?php
		session_start();
		//tarkastaa onko nimi talletettu sessioniin
		if(isset($_SESSION['email'])){
			echo ('<p class="username">' . $_SESSION['email'] . '</p>');
		}
	?>
</div>

<div id="navigationbar">
	<ul>
        <li><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
        <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
    <?php
        if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1) {
    ?>
        <li><a href='yp_asiakkaat.php'><span>Asiakkaat</span></a></li>
        <li><a href='yp_hinnat.php'><span>Hinnat</span></a></li>
        <li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
        <li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>
        <li><a href='yp_raportit.php'><span>Raportit</span></a></li>
    <?php
        } else {
    ?>
        <li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
    <?php
        }
    ?>
        <li class="last"><a href='logout.php'><span>Kirjaudu ulos</span></a></li>
	</ul>
</div>
