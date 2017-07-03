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
		.otsikko_header {
			display: flex;
		}
		.otsikko_header .takaisin {

		}
		.otsikko_header h1 {
			width: 15rem;
			margin: 0 3rem;
			/*border: 1px dashed;*/
			border-radius: 4px;
			padding: 2px;
			background-color: lightsteelblue;
			color: #2f5cad;
			flex-grow: 4;
			text-align: center;
		}
		.otsikko_header .napit {
			display: flex;
			flex-grow: 0;
			justify-content: flex-end;
		}
		.otsikko_header .material-icons {
			margin-left: -10px;
		}
	</style>
</head>
<body>

<?php require 'header.php'; ?>

<main class="main_body_container">

	<div class="otsikko_header">
		<span class="takaisin">
			<button class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</button>
		</span>
		<h1>Stuff</h1>
		<span class="napit">
			<button class="nappi">Toinen nappi</button>
			<!--<button class="nappi">Lisää uusi</button>-->
		</span>

	</div>

	<p>Some random text about red foxes jumping happy fences.</p>

</main>

<?php require 'footer.php'; ?>

<script>
</script>


</body>
</html>
