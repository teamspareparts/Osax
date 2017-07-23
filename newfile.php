<?php
require '_start.php'; global $db, $user, $cart;
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
		</section>
		<section class="otsikko">
			<h1>Otsikko</h1>
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
