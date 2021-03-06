<?php declare(strict_types=1);
require './_start.php'; global $db, $user, $cart;

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
function rakenna_puu( array &$elements, int $parentId = 0, int $depth = 0, int $maxDepth = 5 ) : array {
	if ( ++$depth > $maxDepth ) {
		return array();
	}
	$branch = array();
	foreach ( $elements as &$element ) {
		if ( $element->parentID == $parentId ) {
			$children = rakenna_puu( $elements, (int)$element->id, $depth );
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
 * @param string       $par_oT
 * @param int          $depth    <p> Syvyys rekursiossa. Pidetään lukua varmuuden vuoksi.
 * @param int          $maxDepth <p> Max syvyys. To prevent endless loops.
 */
function tulosta_puu( array &$elements, int $par_ID = 0, string $par_oT = "", int $depth = 0, int $maxDepth = 3 ) {
	if ( ++$depth > $maxDepth ) {
		return;
	}
	echo "<ul>";
	foreach ($elements as &$el) {
		if ( $depth == 1 ) {
			$new_oT = $par_oT . sprintf("%03d", $el->id) ;
		} else {
			$new_oT = $par_oT . sprintf( "-%03d", $el->id );
		}

		echo "<li id='li_{$el->id}'>";

		if ( $depth < $maxDepth OR $el->children ) {
			echo "<details>
				<summary>{$el->nimi} ({$el->id})
					<a href='#' class='edit'
						data-id='{$el->id}' data-nimi='{$el->nimi}' data-kerroin='{$el->hinnoittelukerroin}'>
						<i class='material-icons'>edit</i>
					</a> |
					<a href='#' class='sales' data-id='{$el->id}' data-nimi='{$el->nimi}'>Alennukset & Tuotteet</a> |
					<a href='#' class='delete' data-id='{$el->id}' style='color:red;'>X</a>
				</summary>";
			tulosta_puu( $el->children, (int)$el->id, $new_oT, $depth );
			echo '</details>';
		}
		else {
			echo "<span>{$el->nimi}
					<a href='#' class='edit' data-id='{$el->id}' data-nimi='{$el->nimi}'
						data-kerroin='{$el->hinnoittelukerroin}'><i class='material-icons'>edit</i>
					</a> |
					<a href='#' class='sales' data-id='{$el->id}' data-nimi='{$el->nimi}'>Alennukset & Tuotteet</a> |
					<a href='#' class='delete' data-id='{$el->id}' style='color:red;'>X</a>
				</span>";
		}

		echo '</li>';
	}
	echo "<li> <a href='#' class='add' data-id='{$par_ID}' data-taso='{$par_oT}'>
			<i class='material-icons'>add_box</i></a> </li>
		</ul>";
}

/**
 * //TODO: Väliaikainen ratkaisu
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_yritykset_ja_luo_alasvetovalikko( DByhteys $db ) : string {
	$sql = "SELECT id, nimi FROM yritys WHERE aktiivinen = 1 ORDER BY nimi ASC";
	$rows = $db->query( $sql, [], FETCH_ALL );

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
function hae_kaikki_hankintapaikat_ja_luo_alasvetovalikko( DByhteys $db ) : string {
	$sql = "SELECT id, nimi FROM hankintapaikka ORDER BY nimi ASC";
	$rows = $db->query( $sql, [], FETCH_ALL );

	$return_string = '<select name="hkp_id" required> <option disabled>- Tyhjä -</option>';
	foreach ( $rows as $hkp ) {
		$return_string .= "<option name='hkp_id' value='{$hkp->id}'>{$hkp->id}; {$hkp->nimi}</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

tarkista_admin( $user );

/*
 * Uusi tuoteryhmä
 */
if ( isset($_POST['lisaa_parent_id']) ) {
	$db->query( "INSERT INTO tuoteryhma (parent_id, nimi, hinnoittelukerroin) VALUES (?,?,?)",
				[ $_POST['lisaa_parent_id'], $_POST['nimi'], $_POST['hkerroin'] ] );

	$last_id = $db->getConnection()->lastInsertId();

	$prefix = $_POST[ 'lisaa_parent_id' ]==0 ? "" : "-";
	$uusi_taso = $_POST['par_taso'] . sprintf("{$prefix}%03d", $last_id);

	$db->query( "UPDATE tuoteryhma SET oma_taso = ? WHERE id = ?",
				[ $uusi_taso, $last_id ] );
}
/*
 * Tuoteryhmän muokkaus
 */
else if ( !empty($_POST['muokkaa_id']) ) {
	$db->query( "UPDATE tuoteryhma SET nimi = ?, hinnoittelukerroin = ? WHERE id = ?",
				[ $_POST['nimi'], $_POST['hkerroin'], $_POST['muokkaa_id'] ] );
}
/*
 * Alennuksen lisäys
 */
else if ( !empty($_POST['lisaa_alennus_id']) ) {
	$db->query(
		"INSERT INTO tuoteryhma_erikoishinta
				(tuoteryhma_id, hankintapaikka_id, yritys_id, maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm)
			VALUES (?,?,?,?,?,?,?)",
		[ $_POST['lisaa_alennus_id'], $_POST['hkp_id'], $_POST['yritys_id'], $_POST['maara'],
			$_POST['pros']/100, $_POST['alku_pvm'], $_POST['loppu_pvm'] ] );
}
/*
 * Alennuksen muokkaus
 */
else if ( !empty($_POST['muokkaa_alennus_id']) ) {
	$db->query(
		"UPDATE tuoteryhma_erikoishinta SET maaraalennus_kpl = ?, alennus_prosentti = ?, alkuPvm = ?, loppuPvm = ? WHERE id = ?",
		[ $_POST['maara'], $_POST['pros']/100, $_POST['alku_pvm'], $_POST['loppu_pvm'], $_POST['muokkaa_alennus_id'] ] );
}

$feedback = check_feedback_POST();

$sql = "SELECT id, parent_id AS parentID, oma_taso AS omaTaso, nimi, hinnoittelukerroin FROM tuoteryhma";
$rows = $db->query( $sql, [], true, null, 'Tuoteryhma' );
$tree = rakenna_puu( $rows );

$yrityksien_nimet_alennuksen_asettamista_varten = hae_kaikki_yritykset_ja_luo_alasvetovalikko( $db );
$hkp_nimet_alennuksen_asettamista_varten = hae_kaikki_hankintapaikat_ja_luo_alasvetovalikko( $db );

// Loppu pvm:n valmistelu, niin ei tarvitse sekoittaa HTML:ää.
$today = date('Y-m-d');
$future = date('Y-m-d',strtotime('+6 months'));
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<title>Tuoteryhmät</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css">
	<link rel="stylesheet" href="./css/jsmodal-light.css">
	<link rel="stylesheet" href="./css/details-shim.min.css">
	<script src="./js/details-shim.min.js" async></script>
	<script src="./js/jsmodal-1.0d.min.js" async></script>
	<style>
		ul { list-style: none; }
		summary { padding-top: 10px; }
		a { color: #2f5cad; /* Sivulla paljon linkkejä ikoneina */ }
	</style>
</head>
<body>

<?php require './header.php'; ?>

<main class="main_body_container">

	<div class="otsikko_container">
		<section class="otsikko">
			<h1>Tuoteryhmät</h1>
		</section>
	</div>

	<?= $feedback ?>

	<section class="flex_row" style="flex-wrap: wrap-reverse;">
		<section class="white-bg" style="width:510px; margin-right:30px; white-space:nowrap; border: 1px solid; border-radius:5px;">
			<div class="otsikko_container blue">
				<section class="otsikko">
					<h3>Ryhmät</h3>
				</section>
			</div>
			<?php tulosta_puu( $tree ); ?>
		</section>

		<section class="white-bg" style="min-width:200px; margin-right:30px; white-space:nowrap; border:1px solid; border-radius:5px;">
			<div class="otsikko_container blue">
				<section class="otsikko">
					<h3>Alennukset</h3>
					<span id="alennus_box_otsikko"></span>
				</section>
			</div>
			<button id="uusi_alennus" class="nappi" data-id='' style="visibility: hidden;">Lisää uusi alennus</button>
			<div id="alennukset">
			</div>
		</section>

		<section class="white-bg" style="min-width:200px; white-space:nowrap; border:1px solid; border-radius:5px;">
			<div class="otsikko_container blue">
				<section class="otsikko">
					<h3>Tuotteet</h3>
					<span id="tuotteet_box_otsikko"></span>
				</section>
			</div>
			<div id="tuotteet">
			</div>
		</section>
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
	let ale_tr_otsikko = document.getElementById("alennus_box_otsikko");
	let tuot_tr_otsikko = document.getElementById("tuotteet_box_otsikko");

	/** Lisää uusi tuoteryhmä */
	Array.from(add_napit).forEach(function(element) {
		element.addEventListener('click', function () {
			let parent = element.dataset.id;
			let pTaso = element.dataset.taso;

			Modal.open({
				content: `
					<form method="post">
						<fieldset> <legend>Tuoteryhmien lisäys</legend>
							<label for="form_par_id" class="required"> Parent ID:</label>
							<input type="hidden" name="lisaa_parent_id" value="${parent}" id="form_par_id">
							<input type="hidden" name="par_taso" value="${pTaso}">
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

	/** Muokkaa tuoteryhmää */
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

	/** Alennuksien haku napit */
	Array.from(sales_napit).forEach(function(element) {
		element.addEventListener('click', function () {
			let tuoteryhmaID = element.dataset.id; // Tuoteryhmä, jonka alennukset haetaan.
			let ajax =  new XMLHttpRequest(); // AJAX-pyyntöä varten.
			let response, tuotteet, alennukset, saleHTML, tuotteetHTML, alennusCount, tuotteetCount, i;

			ale_tr_otsikko.innerHTML = "" + tuoteryhmaID + ": " + element.dataset.nimi;
			tuot_tr_otsikko.innerHTML = "" + tuoteryhmaID + ": " + element.dataset.nimi;

			// Asetetaan nappi uuden alennuksen lisäämistä varten näkyviin, ja lisätään siihen tr.ID
			uusi_alennus.dataset.id = tuoteryhmaID;
			uusi_alennus.style.visibility = 'visible';

			// Luodaan AJAX-pyyntö
			ajax.open('POST', 'ajax_requests.php', true);
			ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8;');

			ajax.onreadystatechange = function() {
				if (ajax.readyState === 4 && ajax.status === 200) {
					response = JSON.parse(ajax.responseText);
					alennukset = response[0];
					tuotteet = response[1];
					alennusCount = alennukset.length;
					tuotteetCount = tuotteet.length;
					saleHTML = "";
					tuotteetHTML = "";
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
					if (alennusCount === 0) {
						saleHTML += "<p>Ei alennuksia.</p>"
					}

					// Tuotteiden listaus.
					for ( i=0; i<tuotteetCount; i++ ) {
						tuotteetHTML += `
							<dl data-id="${tuotteet[i].id}">
								<dt>ID</dt> <dd>${tuotteet[i].id}</dd>
								<dt>Tuotekoodi</dt> <dd>${tuotteet[i].tuotekoodi} </dd>
								<dt>Nimi</dt> <dd>${tuotteet[i].nimi} </dd>
							</dl>
							<hr>`;
					}
					if (tuotteetCount === 0) {
						tuotteetHTML += "<p>Ei tuotteita.</p>"
					}

					document.getElementById('alennukset').innerHTML = saleHTML;
					document.getElementById('tuotteet').innerHTML = tuotteetHTML;
				}
			};

			ajax.send( "tuoteryhma_alennukset=" + tuoteryhmaID );
		});
	});

	/** Uuden alennuksen lisäys */
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
