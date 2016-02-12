<div id="header">
	<img alt="Logo" src="img/rantak_varao-Logo.jpg" height="100" width="300" align="left">
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
		<li class='active'><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
   		<li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
   		<li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
   		<li class="last"><a href='logout.php'><span>Kirjaudu ulos</span></a></li>
	</ul>	
</div>
