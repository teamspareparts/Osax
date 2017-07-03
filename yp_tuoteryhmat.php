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
					<a href='#' class='edit' data-id='{$el->id}' data-nimi='{$el->nimi}'
						data-kerroin='{$el->hinnoittelukerroin}'><i class='material-icons'>edit</i></a>
					<a href='#' class='sales'> Alennukset </a>
				</span>";
		}

		echo '</li>';
	}
	echo "<li> <a href='#' class='add' data-id='{$par_ID}'><i class='material-icons'>add_box</i></a> </li>
		</ul>";
}

/**
 * //TODO: Väliaikainen ratkaisu
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_yritykset_ja_luo_alasvetovalikko( $db ) {
	$sql = "SELECT id, nimi FROM yritys WHERE aktiivinen = 1 ORDER BY nimi ASC";
	$rows = $db->query( $sql, NULL, FETCH_ALL );

	$return_string = '<select name="yritys_id"> <option name="yritys"  value="0">- Tyhjä -</option>';
	foreach ( $rows as $yritys ) {
		$return_string .= "<option name='yritys' value='{$yritys->id}'>{$yritys->id}; {$yritys->nimi}</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

/**
 * //TODO: Väliaikainen ratkaisu
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_hankintapaikat_ja_luo_alasvetovalikko( $db ) {
	$sql = "SELECT id, nimi FROM hankintapaikka ORDER BY nimi ASC";
	$rows = $db->query( $sql, NULL, FETCH_ALL );

	$return_string = '<select name="hkp_id" required> <option disabled>- Tyhjä -</option>';
	foreach ( $rows as $hkp ) {
		$return_string .= "<option name='hkp_id' value='{$hkp->id}'>{$hkp->id}; {$hkp->nimi}</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}

// Uusi tuoteryhmä
if ( isset($_POST['lisaa_parent_id']) ) {
	$db->query( "INSERT INTO tuoteryhma (parent_id, nimi, hinnoittelukerroin) VALUES (?,?,?)",
				[ $_POST['lisaa_parent_id'], $_POST['nimi'], $_POST['hkerroin'] ] );
}
// Tuoteryhmän muokkaus
else if ( !empty($_POST['muokkaa_id']) ) {
	$db->query( "UPDATE tuoteryhma SET nimi = ?, hinnoittelukerroin = ? WHERE id = ?",
				[ $_POST['nimi'], $_POST['hkerroin'], $_POST['muokkaa_id'] ] );
}

// Alennuksen lisäys
else if ( !empty($_POST['lisaa_alennus_id']) ) {
	$sql = "INSERT INTO tuoteryhma_erikoishinta
				(tuoteryhma_id, hankintapaikka_id, yritys_id, maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm)
			VALUES (?,?,?,?,?,?,?)";
	$db->query( $sql, [ $_POST['lisaa_alennus_id'], $_POST['hkp_id'], $_POST['yritys_id'], $_POST['maara'], $_POST['pros']/100, $_POST['alku_pvm'], $_POST['loppu_pvm'] ] );
}
// Alennuksen muokkaus
else if ( !empty($_POST['muokkaa_alennus_id']) ) {
	$sql = "UPDATE tuoteryhma_erikoishinta SET maaraalennus_kpl = ?, alennus_prosentti = ?, alkuPvm = ?, loppuPvm = ? WHERE id = ?";
	$db->query( $sql, [ $_POST['maara'], $_POST['pros']/100, $_POST['alku_pvm'], $_POST['loppu_pvm'], $_POST['muokkaa_alennus_id'] ] );
}

$sql = "SELECT id, parent_id AS parentID, oma_taso AS omaTaso, nimi, hinnoittelukerroin
		FROM tuoteryhma";
$rows = $db->query( $sql, null, true, null, 'Tuoteryhma' );
$tree = rakenna_puu( $rows );

$yrityksien_nimet_alennuksen_asettamista_varten = hae_kaikki_yritykset_ja_luo_alasvetovalikko( $db );
$hkp_nimet_alennuksen_asettamista_varten = hae_kaikki_hankintapaikat_ja_luo_alasvetovalikko( $db );

// Loppu pvm:n valmistelu, niin ei tarvitse sekoittaa HTML:ää.
$today = date('Y-m-d');
$future = date('Y-m-d',strtotime('+6 months'));

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
}
else {
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
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	<script src="./js/datepicker-fi.js"></script>
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

	<section class="white-bg" style="min-width:200px; white-space:nowrap; border:1px solid; border-radius:5px;">
		<h3>Tuoteryhmän alennukset:</h3>
		<div id="loader" style="display: none;">
			<div class="loading"></div>
			<p>lataa alennuksia...</p>
		</div>
		<button id="uusi_alennus" class="nappi" data-id='' style="visibility: hidden;">Lisää uusi alennus</button>
		<div id="alennukset">
		</div>
	</section>

</main>

<?php require './footer.php'; ?>

<script>
	let add_napit = document.getElementsByClassName("add");
	let edit_napit = document.getElementsByClassName("edit");
	let sales_napit = document.getElementsByClassName("sales");
	let uusi_alennus = document.getElementById('uusi_alennus');
	let yrit_valikko = <?= json_encode($yrityksien_nimet_alennuksen_asettamista_varten) ?>;
	let hkp_valikko = <?= json_encode($hkp_nimet_alennuksen_asettamista_varten) ?>;
	let today = <?= json_encode($today) ?>;
	let future = <?= json_encode($future) ?>;

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
			let tuoteryhmaID = element.dataset.id; // Tuoteryhmä, jonka alennukset haetaan.
			let ajax =  new XMLHttpRequest(); // AJAX-pyyntöä varten.
			let loader = document.getElementById('loader'); // Lataus-ikonin container. Näyttämistä/piilottamista varten.
			let alennukset, saleHTML, alennusCount, i;

			// Asetetaan nappi uuden alennuksen lisäämistä varten näkyviin, ja lisätään siihen tr.ID
			uusi_alennus.dataset.id = tuoteryhmaID;
			uusi_alennus.style.visibility = 'visible';
			// Pistetään lataus ikoni näkyviin. Käytännössä tätä ei tarvita, koska systeemi liian nopea muutenkin.
			loader.style.display = "visible";
			// Luodaan AJAX-pyyntö
			ajax.open('POST', 'ajax_requests.php', true);
			ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8;');

			ajax.onreadystatechange = function() {
				if (ajax.readyState === 4 && ajax.status === 200) {
					// Pyyntö valmis, ja tiedot vastaanotettu: piilotetaan lataus-ikoni
					loader.style.visibility = 'none';
					alennukset = JSON.parse(ajax.responseText);
					alennusCount = alennukset.length;
					saleHTML = "";
					// Lisätäään muokkaus-formit jokaista alennusta varten.
					for ( i=0; i<alennusCount; i++ ) {
						saleHTML += `
							<form method="post" class='lomake'>
								<fieldset><legend>Alennuksen muokkaus</legend>
								<label for='id' class='required'>Alennus-ID</label>
								<span>${alennukset[i].id}</span>
								<input type='hidden' name='muokkaa_alennus_id' value='${alennukset[i].id}' id='id'>
								<br><br>
								<label>Hankintapaikka</label>
								<span>${alennukset[i].hankintapaikka_id}: ${alennukset[i].hkp_nimi}</span>
								<br><br>
								<label>Yritys</label>
								<span>${alennukset[i].yritys_id}: ${alennukset[i].yritys_nimi}</span>
								<br><br>
								<label for='maara' class='required'>Määräalennus-kpl</label>
								<input type='number' name='maara' value='${alennukset[i].maaraalennus_kpl}' id='maara' required>
								<br><br>
								<label for='pros' class='required'>Prosentti (kokonaislukuna)</label>
								<input type='number' name='pros' value='${alennukset[i].alennus_prosentti*100}' min="0" max="100" id='pros' required> %
								<br><br>
								<label for='alku_pvm' class='required'>Alku-pvm</label>
								<input type='date' name='alku_pvm' value='${alennukset[i].alkuPvm}' max='${future}' id='alku_pvm' required>
								<br><br>
								<label for='loppu_pvm' class='required'>Loppu-pvm</label>
								<input type='date' name='loppu_pvm' value='${alennukset[i].loppuPvm}'
										min='${today}' max='${future}' id='loppu_pvm' required>
								<br><br>
								<span class='small_note'><span class='required'></span> = pakollinen kenttä</span>
								<br>
								<div class="center">
									<input type='submit' value='Tallenna muokkaukset' class='nappi'>
								</div>
								</fieldset>
							</form>`;
					}

					document.getElementById('alennukset').innerHTML = saleHTML;
				}
			};

			ajax.send( "tuoteryhma_alennukset=" + tuoteryhmaID );
		});
	});

	uusi_alennus.addEventListener('click', function () {
		Modal.open({
			content: `
				<form method="post">
					<fieldset> <legend>Uusi alennus</legend>
						<label for='hkp' class="required">Hankintapaikka</label>
						${hkp_valikko}
						<br><br>
						<label for='yr'>Yritys</label>
						${yrit_valikko}
						<br><br>
						<label for='maara' class='required'>Määräalennus-kpl</label>
						<input type='number' name='maara' value='1' min="1" max="9000" id='maara' required>
						<br><br>
						<label for='pros' class='required'>Prosentti (kokonaislukuna)</label>
						<input type='number' name='pros' value='1' min="0" max="100" id='pros' required> %
						<br><br>
						<label for='alku_pvm' class='required'>Alku-pvm</label>
						<input type='date' name='alku_pvm' value='${today}' min='${today}' max='${future}'
								id='alku_pvm' required>
						<br><br>
						<label for='loppu_pvm' class="required">Loppu-pvm</label>
						<input type='date' name='loppu_pvm' value='' id='loppu_pvm' min='${today}' max='${future}' required>
						<br><br>
						<span class='small_note'><span class='required'></span> = pakollinen kenttä</span>
						<br>
						<div class="center">
							<input type='hidden' name="lisaa_alennus_id" value='${uusi_alennus.dataset.id}'>
							<input type='submit' value='Tallenna muokkaukset' class='nappi'>
						</div>
					</fieldset>
				</form>
			`,
			draggable: true
		});
	});
</script>
</body>
</html>
