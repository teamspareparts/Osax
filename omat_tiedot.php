<?php
require '_start.php'; global $db, $user, $cart;
/**
 * @param DByhteys $db
 * @param User $user
 * @param $variables
 * @return bool
 */
function tallenna_uudet_tiedot( DByhteys $db, User $user, /*array*/ $variables ) {
	$possible_fields = [
		'etunimi', 'sukunimi', 'sahkoposti','puhelin','yritys','katuosoite','postinumero','postitoimipaikka'];
	$i = 0;
	$filtered_array = array_filter($variables); // Poistaa tyhjat
	unset( $filtered_array['muokkaa_vanha_osoite'] ); // Ei tarvita, ja häritsee SQL-hakua lopussa
	$len = count($filtered_array) - 1; // Sisältää jo osoite_id:n, jota ei tarvita pituudessa.

	if ( $len >= 1 ) {	// Onko päivitettäviä tietoja? Ei turhia sql-hakuja.
		$sql_query = "UPDATE toimitusosoite SET "; //Aloitusosa

		foreach ( $filtered_array as $key => $value ) {	// Täytetään hakuun päivitettävät arvot
			$k = htmlspecialchars( $key );
			if ( in_array( $k, $possible_fields ) ) {
				$sql_query .= $k . " = ?";
				if ( $i < ($len) ) { $sql_query .= ', '; } // Jos vielä arvoja, lisätään erotin
				$i++;
			}
		}

		$sql_query .= " WHERE osoite_id = ? AND kayttaja_id = ?"; //Loppuosa
		$filtered_array[] = $user->id; // Lisätään käyttäjän ID arrayhin db->querya varten
		return $db->query( $sql_query, $filtered_array);
	}
	return false; //Jos ei yhtään päivitettävää osaa
}

/**
 * @param DByhteys $db
 * @param User $user
 * @param $variables
 * @return bool
 */
function lisaa_uusi_osoite( DByhteys $db, User $user, /*array*/ $variables ) {
	unset( $variables['tallenna_uusi_osoite'] ); //Poistetaan turha array-index.
	$variables[] = $user->id;
	$variables[] = count($user->toimitusosoitteet); //Lisätään osoite-ID (viimeinen indeksi +1).

	$sql = "INSERT INTO toimitusosoite
				(etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, 
				 postinumero, postitoimipaikka, maa, kayttaja_id, osoite_id)
			VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

	return $db->query( $sql, $variables );
}

/**
 * @param DByhteys $db
 * @param User $user
 * @param $osoite_id
 * @return bool
 */
function poista_osoite( DByhteys $db, User $user, /*int*/ $osoite_id) {
	$osoite_id_viimeinen = count($user->toimitusosoitteet) - 1;

	$sql = "DELETE FROM toimitusosoite
			WHERE kayttaja_id = ? AND osoite_id = ?";
	$stmt = $db->getConnection()->prepare( $sql ); //Tarvitaan rowCount-metodia, joten hieman manuaalia PDO:ta.
	$stmt->execute( [$user->id, $osoite_id] );

	if ( $stmt->rowCount() > 0 ) {
		$sql = "UPDATE	toimitusosoite
				SET		osoite_id = ?
				WHERE	kayttaja_id = ? AND osoite_id = ?";
		return $db->query( $sql, [$osoite_id, $user->id, $osoite_id_viimeinen] );
	}

	else return false;
}

$yritys = new Yritys( $db, $user->yritys_id );
$user->haeToimitusosoitteet( $db, -1 );
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION['feedback']);

if ( isset($_POST['uudet_tiedot']) ){
	$sql = "UPDATE kayttaja 
            SET etunimi = ? , sukunimi = ? , puhelin = ?
  		    WHERE sahkoposti = ?";
	if ( $db->query($sql, [$_POST['etunimi'], $_POST['sukunimi'], $_POST['puh'], $user->sahkoposti])){
		$_SESSION['feedback'] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>Tietojen päivittäminen epäonnistui.</p>";
	}
}

elseif ( !empty($_POST['new_password']) ) {
	if ( strlen($_POST['new_password']) >= 8 ) {
		if ( $_POST['new_password'] === $_POST['confirm_new_password'] ) {
			if ( $user->vaihdaSalasana( $db, $_POST['new_password'] ) ) {
				$_SESSION['feedback'] = "<p class='success'>Salasanan vaihtaminen onnistui.</p>";

			} else { $_SESSION['feedback'] = "<p class='error'>Salasanan vaihtaminen epäonnistui tuntemattomasta syystä.</p>"; }
		} else { $_SESSION['feedback'] = "<p class='error'>Salasanan vahvistus ei täsmää.</p>"; }
	} else { $_SESSION['feedback'] = "<p class='error'>Salasanan pitää olla vähintään kahdeksan merkkiä pitkä.</p>"; }
}


elseif ( !empty($_POST["muokkaa_vanha_osoite"]) ) {
	tallenna_uudet_tiedot( $db, $user, $_POST );

} elseif ( !empty($_POST["tallenna_uusi_osoite"]) ) {
	lisaa_uusi_osoite( $db, $user, $_POST );

} elseif ( !empty($_POST["poista_osoite"]) ) {
	poista_osoite( $db, $user, $_POST["poista_osoite"]);
}
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen selaimen takaisin-napilla.
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
</head>
<body>
<?php require("header.php"); ?>

<main id="lomake">
	<?= $feedback ?>

	<form action="#" name="asiakkaan_tiedot" method="post" accept-charset="utf-8">
		<fieldset><legend>Nykyiset tiedot</legend>
			<label><span>Sähköposti</span></label>
			<p style="display: inline; font-size: 16px;"><?= $user->sahkoposti ?></p>
			<?php if ( $user->demo ) : ?>
				<br><br><label><span>Voimassa</span></label>
				<p style="display: inline; font-size: 16px;">
					<?= (new DateTime($user->voimassaolopvm))->format("d.m.Y H:i:s") ?>
				</p>
			<?php endif; ?>
			<br><br>
			<label><span>Etunimi</span></label>
			<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" value="<?= $user->etunimi ?>"
				   title="Vain aakkosia.">
			<br><br>
			<label><span>Sukunimi</span></label>
			<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" value="<?= $user->sukunimi ?>"
				   title="Vain aakkosia">
			<br><br>
			<label><span>Puhelin</span></label>
			<input name="puh" type="tel" value="<?= $user->puhelin ?>" title="Vain numeroita"
				   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
			<br><br><br>

			<div class="center">
				<input type="hidden" name="uudet_tiedot">
				<input class="nappi" name="submit" value="Päivitä tiedot" type="submit">
			</div>
		</fieldset>
	</form>

	<br><br>

	<form action="#" name="uusi_salasana" method="post" accept-charset="utf-8">
		<fieldset><legend>Vaihda salasana</legend>
			<label><span>Uusi salasana</span></label>
			<input name="new_password" type="password" pattern=".{8,}" title="Pituus min 8 merkkiä."
				   id="uusi_salasana">
			<br><br>
			<label><span>Vahvista salasana</span></label>
			<input name="confirm_new_password" type="password" pattern=".{8,}" title="Pituus min 8 merkkiä."
				   id="vahv_uusi_salasana"><br>
			<span id="check"></span>
			<br><br><br>
			<div class="center">
				<input class="nappi" name="submit" value="Vaihda salasana" type="submit" id="pw_submit">
			</div>
		</fieldset>
	</form>

	<br><br>

	<fieldset style="display:inline-block; text-align:left;"><Legend>Osoitekirja</legend>
		<?php foreach ( $user->toimitusosoitteet as $key => $row ) : ?>
			<div> Osoite <?= $key+1 ?><br>
				<br>
				<label><span>Nimi</span></label><?= "{$row->etunimi} {$row->sukunimi}" ?><br>
				<label><span>Sähköposti</span></label><?= $row->sahkoposti ?><br>
				<label><span>Puhelin</span></label><?= $row->puhelin ?><br>
				<label><span>Yritys</span></label><?= $row->yritys ?><br>
				<label><span>Katuosoite</span></label><?= $row->katuosoite ?><br>
				<label><span>Postinumero</span></label><?= $row->postinumero ?><br>
				<label><span>Postitoimipaikka</span></label><?= $row->postitoimipaikka ?><br><br>

				<input class="nappi" type="button" value="Muokkaa"
					   onClick="avaa_Modal_toimitusosoite_muokkaa(<?= $key ?>);">
				<input class="nappi" type="button" value="Poista" style="background:#d20006; border-color:#b70004;"
					   onClick="vahvista_Osoitteen_Poistaminen(<?= $key ?>);">

				<form style="display:none;" id="<?="poista_Osoite_Form_{$key}"?>" name="poista_osoite" action="" method=post>
					<input type=hidden name=poista_osoite value="'<?= $key ?>'">
				</form>
			</div><hr>
		<?php endforeach; ?>
		<div class="center">
			<input class="nappi" type="button" value="Lisää uusi toimitusosoite"
				   onClick="avaa_Modal_toimitusosoite_lisaa_uusi();">
		</div>
	</fieldset>

    <br><br>
    <fieldset style="display:inline-block; text-align:left;"><legend>Yritys</legend>
        <label><span>Nimi</span></label><?=$yritys->nimi?><br>
        <label><span>Sähköposti</span></label><?=$yritys->sahkoposti?><br>
        <label><span>Puhelin</span></label><?=$yritys->puhelin?><br>
        <label><span>Katuosoite</span></label><?=$yritys->katuosoite?><br>
        <label><span>Postinumero</span></label><?=$yritys->postinumero?><br>
        <label><span>Postitoimipaikka</span></label><?=$yritys->postitoimipaikka?><br>
        <label><span>Maa</span></label><?=$yritys->maa?><br>
    </fieldset>
</main>

<script>
	/**
	 * Avaa jsModal-ikkunan tietyn toimitusosoitteen muokkaamista varten
	 * Huom. input name-arvo pitää olla sama kuin tietokannassa.
	 * @param osoite_id
	 */
	function avaa_Modal_toimitusosoite_muokkaa( osoite_id ) {
		Modal.open( {
			content:  '\
			<div>Muokkaa tietoja (Osoite ' + osoite_id + ')</div>\
			<br>\
			<form action="#" method=post>\
				<label>Etunimi</label>\
					<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Edellinen etunimi"><br>\
				<label>Sukunimi</label>\
					<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Edellinen sukunimi"><br>\
				<label>Sähköposti</label>\
					<input name="sahkoposti" type="email" pattern=".{3,50}" placeholder="Edellinen sähköposti"><br>\
				<label>Puhelin</label>\
					<input name="puhelin" type="tel" pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" placeholder="Edellinen puhelinumero"><br>\
				<label>Yritys</label>\
					<input name="yritys" type="text" pattern=".{3,50}" placeholder="Edellinen yritys"><br>\
				<label>Katuosoite</label>\
					<input name="katuosoite" type="text" pattern=".{3,50}" placeholder="Edellinen katuosoite"><br>\
				<label>Postinumero</label>\
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="Edellinen postinumero"><br>\
				<label>Postitoimipaikka</label>\
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="Edellinen postitoimipaikka">\
				<br><br>\
				<input type="hidden" name="osoite_id" value= ' + osoite_id + '>\
				<input type="submit" name="muokkaa_vanha_osoite" value="Tallenna uudet tiedot (tyhjiä kenttiä ei oteta huomioon)">\
				<br>\
			</form>\
			',
			draggable: true
		} );
	}

	/**
	 * Modal-ikkuna uuden toimitusosoitteen lisäämistä varten
	 */
	function avaa_Modal_toimitusosoite_lisaa_uusi() {
		Modal.open( {
			content:  '\
			<div>Lisää uuden toimitusosoitteen tiedot</div>\
			<br>\
			<form action="#" method=post>\
				<label>Etunimi</label>\
					<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Etunimi" required><br>\
				<label>Sukunimi</label>\
					<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Sukunimi" required><br>\
				<label>Sähköposti</label>\
					<input name="sahkoposti" type="email" pattern=".{3,50}" placeholder="yourname@email.com" required><br>\
				<label>Puhelin</label>\
					<input name="puhelin" type="tel" pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" placeholder="000 1234 789" required><br>\
				<label>Yritys</label>\
					<input name="yritys" type="text" pattern=".{3,50}" placeholder="Yritys Oy"><br>\
				<label>Katuosoite</label>\
					<input name="katuosoite" type="text" pattern=".{3,50}" placeholder="Katu 42" required><br>\
				<label>Postinumero</label>\
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="00001" required><br>\
				<label>Postitoimipaikka</label>\
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="KAUPUNKI" required>\
				<label>Maa</label>\
					<input name="maa" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="Maa">\
				<br><br>\
				<input type="submit" name="tallenna_uusi_osoite" value="Tallenna uusi osoite">\
				<br>\
			</form>\
			',
			draggable: true
		} );
	}

	/**
	 * Vahvistetaan kayttajalta osoitteen poistaminen javascript confirm-ikkunan avulla
	 * OK:n jälkeen lähettää lomakkeen, jossa osoitteen ID.
	 * @param osoite_id
	 * @return Boolean false, jos kayttaja ei paina OK:ta.
	 */
	function vahvista_Osoitteen_Poistaminen( osoite_id ) {
		var vahvistus = confirm( "Oletko varma, että haluat poistaa osoitteen?\n"
			+ "Tätä toimintoa ei voi perua jälkeenpäin.\n"
			+ "(Huom. Osoitetietoja ei poisteta mahdollisista tilaustiedoista.)");
		if ( vahvistus === true ) {
			document.getElementById("poista_Osoite_Form_" + osoite_id).submit();
		} else {
			return false;
		}
	}


	$(document).ready(function() {
		var pwSubmit = $('#pw_submit'); // Salasanan pituuden ja vahvistuksen tarkistusta varten
		var newPassword = $('#uusi_salasana'); // Ditto
		var pwCheck = $('#check'); // Ditto

		/** Salasanojen tarkastus reaaliajassa */
		$('#uusi_salasana, #vahv_uusi_salasana').on('keyup', function () {
			pwSubmit.prop('disabled', true).addClass('disabled');
			if ( newPassword.val().length >= 8 ) {
				if ( newPassword.val() === $('#vahv_uusi_salasana').val() ) {
					pwCheck.html('<i class="material-icons">done</i>Salasana OK.').css('color', 'green');
					pwSubmit.prop('disabled', false).removeClass('disabled');
				} else {
					pwCheck.html('<i class="material-icons">warning</i>Salasanat eivät täsmää').css('color', 'red');
				}
			} else {
				pwCheck.html('<i class="material-icons">warning</i>Salasanat min. pituus on 8 merkkiä.')
					.css('color', 'red');
			}
		});
	});
</script>

</body>
</html>
