<div id="header">
	<img alt="Logo" src="img/logo1.png" height="70" width="150" align="left">
	<!-- <h1>Logo</h1> -->
	<p class="kirjautuneena">Kirjautuneena:</p>
	<?php 	
		session_start();
		//tarkastaa onko nimi talletettu sessioniin
		if(isset($_SESSION['user'])){
			echo ('<p class="username">' . $_SESSION['user'] . '</p>');
		}
	?>
</div>

<div id="navigationbar">
	<ul>
		<li class='active'><a href='yp_tuotehaku.php'><span>Tuotehaku</span></a></li>
   		<li><a href='yp_omat_tiedot.php'><span>Omat tiedot</span></a></li>
   		<li><a href='yp_asiakkaat.php'><span>Asiakkaat</span></a></li>
   		<li><a href='yp_hinnat.php'><span>Hinnat</span></a></li>
   		<li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
   		<li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>
   		<li><a href='yp_raportit.php'><span>Raportit</span></a></li>
   		<li class="last"><a href='logout.php'><span>Kirjaudu ulos</span></a></li>
	</ul>	
</div>