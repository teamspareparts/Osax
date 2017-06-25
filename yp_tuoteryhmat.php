<?php
require './_start.php'; global $db, $user, $cart;
require './luokat/tuoteryhma.class.php';

/**
 * Rakentaa puun rekursiivisesti parametrina annetusta 1D object-arraysta. Olioilla oltava luokan muuttujina
 * id (int), parentID (int), ja children (array).
 *
 * @param Tuoteryhma[] $elements
 * @param null         $parentId <p> Rekursiota varten. Juuren vanhempi ensimmäisessä funktio kutsussa (default == null).
 * @param int          $depth    <p> Syvyys rekursiossa. Pidetään lukua varmuuden vuoksi.
 * @param int          $maxDepth <p> Max syvyys. To prevent endless loops.
 * @return array <p> Palauttaa k-ary puun. K == lasten määrä.
 */
function rakenna_puu( array &$elements, $parentId = 0, /*int*/$depth = 0, /*int*/$maxDepth = 5 ) {
	if ( ++$depth > $maxDepth ) {
		return array();
	}
	$branch = array();
	foreach ( $elements as &$element ) {
		if ( $element->parentID == $parentId ) {
			$children = rakenna_puu( $elements, $element->id, $depth );
			if ( $children ) {
				$element->children = $children;
			}
			$branch[ $element->id ] = $element;
			unset( $element );
		}
	}

	return $branch;
}

/**
 * Tulostaa suoraan funktiossa puun käyttäen listoja, ja <details><summary>-tageja.
 * @param Tuoteryhma[] $elements
 * @param int          $par_ID
 * @param int          $depth    <p> Syvyys rekursiossa. Pidetään lukua varmuuden vuoksi.
 * @param int          $maxDepth <p> Max syvyys. To prevent endless loops.
 */
function tulosta_puu( array &$elements, /*int*/$par_ID = 0, /*int*/$depth = 0, /*int*/$maxDepth = 3 ) {
	if ( ++$depth > $maxDepth ) {
		return;
	}
	echo "<ul>";
	foreach ($elements as &$el) {
		echo "<li id='li_{$el->id}'>";

		if ( $depth < $maxDepth OR $el->children ) {
			echo "<details>
				<summary> {$el->parentID}-{$el->id}: {$el->nimi}
					<a href='#' class='edit'
						data-id='{$el->id}' data-nimi='{$el->nimi}' data-kerroin='{$el->hinnoittelukerroin}'>
						<i class='material-icons'>edit</i>
					</a>
					<a href='#' class='sales' data-id='{$el->id}'> Alennukset </a>
				</summary>";
			tulosta_puu( $el->children, $el->id, $depth );
			echo '</details>';
		}
		else {
			echo "<span>
					{$el->parentID}-{$el->id}: {$el->nimi}
					<a href='#' class='edit' data-id='{$el->id}'><i class='material-icons'>edit</i></a>
					<a href='#' class='sales'> Alennukset </a>
				</span>";
		}

		echo '</li>';
	}
	echo "<li> <a href='#' class='add' data-id='{$par_ID}'><i class='material-icons'>add_box</i></a> </li>
		</ul>";
}

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

// Uusi tuoteryhmä
if ( !empty($_POST['lisaa_parent_id']) ) {
	$db->query( "INSERT INTO tuoteryhma (parent_id, nimi, hinnoittelukerroin) VALUES (?,?,?)",
				[ $_POST['parent_id'], $_POST['nimi'], $_POST['hkerroin'] ] );
}
// Tuoteryhmän muokkaus
else if ( !empty($_POST['muokkaa_id']) ) {
	$db->query( "UPDATE tuoteryhma SET nimi = ?, hinnoittelukerroin = ? WHERE id = ?",
				[ $_POST['nimi'], $_POST['hkerroin'], $_POST['id'] ] );
}

$sql = "SELECT id, parent_id AS parentID, oma_taso AS omaTaso, nimi, hinnoittelukerroin
		FROM tuoteryhma";
$rows = $db->query( $sql, null, true, null, 'Tuoteryhma' );
$tree = rakenna_puu( $rows );

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css">
	<link rel="stylesheet" href="./css/jsmodal-light.css">
	<link rel="stylesheet" href="./css/details-shim.min.css">
	<script src="./js/details-shim.min.js" async></script>
	<script src="./js/jsmodal-1.0d.min.js" async></script>
	<style>
		/*form, p, div, section, span, details, summary, ul, li { border: 1px solid; }*/
		ul {
			list-style: none;
		}
		summary {
			padding-top: 10px;
		}
		a {     /* Käsittää vain ostoskori-linkin */
			color: #2f5cad; /* Ostoskori-linkin väri ei muutu randomisti. Näyttää siistimmältä, eikä kiinnitä huomiota. */
		}
		.loading {
			/*background-color: #6f6f6f;*/
			border: 10px solid #f3f3f3; /* Light grey */
			border-top: 10px solid #2f5cad; /* Blue */
			border-bottom: 10px solid #2f5cad; /* Blue */
			border-radius: 100%;
			width: 50px;
			height: 50px;
			animation: spin 4s linear infinite;
			margin: auto;
		}

		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	</style>
</head>
<body>

<?php require './header.php'; ?>

<main class="main_body_container flex_row" style="flex-wrap: wrap-reverse;">
	<?= $feedback ?>

	<section class="white-bg" style="width:444px; margin-right:30px; white-space:nowrap; border: 1px solid; border-radius:5px;">
		<h3>Tuoteryhmät</h3>
		<?php tulosta_puu( $tree ); ?>
	</section>

	<section id="alennukset" class="white-bg"
	         style="min-width:200px; white-space:nowrap; border:1px solid; border-radius:5px;">
		<div id="loader">
			<div class="loading"></div>
			<p>lataa alennuksia...</p>
		</div>
		<div id="alennukset">
		</div>
	</section>

</main>

<?php require './footer.php'; ?>

<script>
	let add_napit = document.getElementsByClassName("add");
	let edit_napit = document.getElementsByClassName("edit");
	let sales_napit = document.getElementsByClassName("sales");


	Array.from(add_napit).forEach(function(element) {
		element.addEventListener('click', function () {
			let parent = element.dataset.id;

			Modal.open({
				content: `
					<form method="post">
						<fieldset> <legend>Tuoteryhmien lisäys</legend>
							<label for="form_par_id" class="required"> Parent ID:</label>
							<input type="hidden" name="lisaa_parent_id" value="${parent}" id="form_par_id">
							<span>${parent}</span>
							<br>
							<label for="form_name" class="required">Nimi:</label>
							<input type="text" name="nimi" value="" id="form_name" placeholder="Nimi" required>
							<br>
							<label for="form_hkerroin" class="required">Hinnoittelukerroin:</label>
							<input type="number" name="hkerroin" value="1" step="0.01"
									placeholder="1,00" id="form_hkerroin" required>
							<br><br>
							<span class="small_note"><span class="required"></span> = pakollinen kenttä</span>
							<p class="center">
								<input type="submit" value="Lisää" class="nappi">
							</p>
						</fieldset>
					</form>
				`,
				draggable: true
			});
		});
	});

	Array.from(edit_napit).forEach(function(element) {
		element.addEventListener('click', function () {
			let id = element.dataset.id;
			let nimi = element.dataset.nimi;
			let hinnoittelukerroin = element.dataset.kerroin;

			Modal.open({
				content: `
					<form method="post">
						<fieldset> <legend>Tuoteryhmien muokkaus</legend>
							<label for="form_id" class="required"> ID: </label>
							<input type="hidden" name="muokkaa_id" value="${id}" id="form_id">
							<span>${id}</span>
							<br>
							<label for="form_name" class="required">Nimi:</label>
							<input type="text" name="nimi" value="${nimi}" id="form_name" required>
							<br>
							<label for="form_hkerroin" class="required">Hinnoittelukerroin:</label>
							<input type="number" name="hkerroin" value="${hinnoittelukerroin}"
									step="0.01" placeholder="1,00" id="form_hkerroin" required>
							<br><br>
							<span class="small_note"><span class="required"></span> = pakollinen kenttä</span>
							<p class="center">
								<input type="submit" value="Muokkaa" class="nappi">
							</p>
						</fieldset>
					</form>
				`,
				draggable: true
			});
		});
	});

	Array.from(sales_napit).forEach(function(element) {
		element.addEventListener('click', function () {
			let tuoteryhmaID = element.dataset.id;
			let ajax =  new XMLHttpRequest();

			ajax.open('POST', 'ajax_requests.php', true);
			ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8;');

			ajax.onreadystatechange = function() {
				if (ajax.readyState === 2 ) {
						document.getElementById('loading').insertAdjacentHTML("afterbegin","<p>Loading...</p>", );
				}
				if (ajax.readyState === 4 && ajax.status === 200) {
					if ( ajax.responseText === '1' ) {
						document.getElementById('alennukset').style.display = "hidden";
					}
				}
			};

			ajax.send( tuoteryhmaID );
		});
	});
</script>
</body>
</html>
