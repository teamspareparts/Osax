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
function rakenna_puu( array &$elements, $parentId = null, /*int*/$depth = 0, /*int*/$maxDepth = 5 ) {
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
function tulosta_puu( array &$elements, /*int*/$par_ID = null, /*int*/$depth = 0, /*int*/$maxDepth = 3 ) {
	if ( ++$depth > $maxDepth ) {
		return;
	}
	echo "<ul>";
	foreach ($elements as &$el) {
		echo "<li id='li_{$el->id}'>";

		if ( $depth < $maxDepth OR $el->children ) {
			echo "<details>";
			echo "<summary>";
			echo "{$el->parentID}-{$el->id}: {$el->nimi}";
			echo "<a href='#' class='edit' data-id='{$el->id}'><i class='material-icons'>edit</i></a>";
			echo '</summary>';
			tulosta_puu( $el->children, $el->id, $depth );
			echo '</details>';
		}
		else {
			echo "<span>";
			echo "{$el->parentID}-{$el->id}: {$el->nimi}";
			echo "<a href='#' class='edit' data-id='{$el->id}'><i class='material-icons'>edit</i></a>";
			echo '</span>';
		}

		echo '</li>';
	}
	echo "<li>";
	echo "<a href='#' class='add' data-id='{$par_ID}'><i class='material-icons'>add_box</i></a>";
	echo '</li>';
	echo "</ul>";
}

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

if ( !empty($_POST) AND empty($_POST['id']) AND empty($_POST['parent_id']) ) {
	// Uusi root tuoteryhmä
	$db->query( "INSERT INTO tuoteryhma (nimi, hinnoittelukerroin) VALUES (?,?)",
				[ $_POST['nimi'], $_POST['hkerroin'] ] );
}
else if ( empty($_POST['id']) AND !empty($_POST['parent_id']) ) {
	// Uusi lapsi tuoteryhmä
	$db->query( "INSERT INTO tuoteryhma (parent_id, nimi, hinnoittelukerroin) VALUES (?,?,?)",
				[ $_POST['parent_id'], $_POST['nimi'], $_POST['hkerroin'] ] );
}
else if ( !empty($_POST['id']) ) {
	// Tuoteryhmän muokkaus
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
	</style>
</head>
<body>

<?php require './header.php'; ?>

<main class="main_body_container">
	<?= $feedback ?>

	<section class="lomake">
		<form method="post">
			<fieldset> <legend>Tuoteryhmien muokkaus / lisäys</legend>
				<span>WIP - tämä boxi tulee luultavasti siirtymään jonnekin muualle.</span>
				<br><br>
				<label for="form_id"> ID: </label>
				<input type="number" name="id" value="" id="form_id">
				<br>
				<label for="form_par_id"> Parent ID:</label>
				<input type="number" name="parent_id" id="form_par_id">
				<br>
				<label for="form_name" class="required">Nimi:</label>
				<input type="text" name="nimi" value="" id="form_name" required>
				<br>
				<label for="form_hkerroin" class="required">Hinnoittelukerroin:</label>
				<input type="number" name="hkerroin" value="1" step="0.01" placeholder="1,00" id="form_hkerroin" required>
				<br><br>
				<span class="small_note"><span class="required"></span> = pakollinen kenttä</span>
				<p class="center">
					<input type="submit" value="Lisää / Muokkaa" class="nappi">
				</p>
			</fieldset>
		</form>
	</section>

	<section class="tuoteryhmat_tree">
		<h3>Tuoteryhmät</h3>
		<?php tulosta_puu( $tree ); ?>
	</section>

</main>

<?php require './footer.php'; ?>

<script>
	console.log( "Hello World" );
</script>
</body>
</html>
