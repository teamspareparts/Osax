<?php declare(strict_types=1);
spl_autoload_register(function (string $class_name) { require '../luokat/' . $class_name . '.class.php'; });
$db=new DByhteys( [],'../config/config.ini.php' );
function debug($var,$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}
if(!empty($_POST)){debug($_POST);}
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="../css/dialog-polyfill.css">
	<link rel="stylesheet" href="../css/styles.css">
	<script src="../js/dialog-polyfill.js"></script>
	<style>form,p,div,section,span,details,summary,ul,li,dialog,button,main{border:1;}</style>
</head>
<body>

<main class="main_body_container">
	<a href="https://demo.agektmr.com/dialog/">Link to dialog demo and examples</a><br><br>
	<div>
		<button class="show" id="show">Avaa testi modal ja loader icon</button>

		<dialog class="dialog" id="modal">
			<div class="backdrop-close">
				<p>This is a dialog.</p><br>
				
				<div id="loader" style=": none;">
					<div class="loading"></div>
					<p>lataa heat death of the universe...</p>
				</div>
				
				<button class="close" id="close">Close</button>
				
			</div>
		</dialog>

	</div>
</main>

<?php require '../footer.php'; ?>

<script>

	let modal = document.getElementById('modal');
	let show = document.getElementById('show');
	let close = document.getElementById('close');
	let loader = document.getElementById('loader'); // Lataus-ikonin container. Näyttämistä/piilottamista varten.

	dialogPolyfill.registerDialog( modal );

	modal.addEventListener("click", function(e) {
		if ( e.target.id === "modal") {
			modal.close();
		}
	});

	show.addEventListener("click", function() {
		modal.showModal();
		loader.style.display = "block";
	});

	close.addEventListener("click", function(e) {
		modal.close();
	});
</script>


</body>
</html>
