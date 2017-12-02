<?php declare(strict_types=1);
spl_autoload_register(function (string $class_name) { require '../luokat/' . $class_name . '.class.php'; });
$db=new DByhteys( [],'../config/config.ini.php' );
function debug($var,$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}
if(!empty($_POST)){debug($_POST);}

/** @var Tuote[] $tuotteet */
$tuotteet = $db->query( "SELECT * FROM tuote LIMIT 500", [], true, null, 'Tuote');
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="../css/dialog-polyfill.css">
	<link rel="stylesheet" href="../css/styles.css">
	<script src="../js/dialog-polyfill.js"></script>
	<style>form,p,div,section,span,details,summary,ul,li,dialog,button,main{border:0;}</style>
</head>
<body>

<main class="main_body_container">
	<a href="https://demo.agektmr.com/dialog/">Link to dialog demo and examples</a><br><br>
	<?php foreach ( $tuotteet as $t ) : ?>
		<div>
			<button class="show" data-dialog-id="#dialog_<?=$t->id?>">Tuotteen <?=$t->id?> tiedot</button>

			<dialog class="dialog" id="dialog_<?=$t->id?>">
				<div class="backdrop-close">
					<p>This is a dialog.</p><br>
					<button class="close" data-dialog-id="#dialog_<?=$t->id?>">Close</button>
				</div>
			</dialog>

		</div>
		<br><br>
	<?php endforeach; ?>
</main>

<?php require '../footer.php'; ?>

<script>
	let dialogs = document.querySelectorAll('dialog');
	let openButtons = document.querySelectorAll('.show');
	let closeButtons = document.querySelectorAll('.close');
	let i;

	for (i = 0; i < dialogs.length; i++) {
		dialogPolyfill.registerDialog(dialogs[i]);

		dialogs[i].addEventListener("click", function(e) {
			console.log( e.target.tagName );
			if ( e.target.tagName === "DIALOG" ) {
				let d = document.getElementById( e.target.id );
				console.log( d );
				d.close();
			}
		});
	}

	for (i = 0; i < openButtons.length; i++) {
		openButtons[i].addEventListener("click", function(e) {
			let d = document.querySelector( e.target.dataset.dialogId );
			d.showModal();
		});
	}

	for (i = 0; i < closeButtons.length; i++) {
		closeButtons[i].addEventListener("click", function(e) {
			let d = document.querySelector( e.target.dataset.dialogId );
			d.close();
		});
	}


	/*let modal = document.getElementById('modal');
	let show = document.getElementById('show');
	let close = document.getElementById('close');

	dialogPolyfill.registerDialog( modal );

	modal.addEventListener("click", function(e) {
		if ( e.target.id === "modal") {
			modal.close();
		}
	});

	show.addEventListener("click", function() {
		modal.showModal();
	});

	close.addEventListener("click", function(e) {
		modal.close();
	});*/
</script>


</body>
</html>
