<?php
require '_start.php';
if(!empty($_POST)){debug($_POST);}
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css">
	<style>
		form, p, div, section, span, details, summary, ul, li { border: 0 solid; }
	</style>
</head>
<body>

<?php require 'header.php'; ?>

<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<button class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</button>
		</section>
		<section class="otsikko">
			<h1>Otsikko</h1>
		</section>
		<section class="napit">
			<button class="nappi">Toinen nappi</button>
			<button class="nappi red">Toinen nappi jossa pitkä teksti</button>
		</section>
	</div>

	<br>
	<hr>
	<br>

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Otsikko</h1>
			<span>&nbsp;&nbsp;Pieni teksti.</span>
			<p>&nbsp;&nbsp;Toinen pieni teksti.</p>
		</section>
		<section class="napit">
		</section>
	</div>

</main>

<?php require 'footer.php'; ?>

<script>
</script>


</body>
</html>
