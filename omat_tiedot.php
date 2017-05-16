<?php
require '_start.php'; global $db, $user, $cart;

/**
 * @param DByhteys $db
 * @param array    $variables
 * @return string <p> for _SESSION['feedback']
 */
function vaihda_salasana( DByhteys $db, array $variables ) {
	$row = $db->query( "SELECT salasana_hajautus FROM kayttaja WHERE id = ?" );
	if ( !password_verify( $variables[ 'vanha_salasana' ], $row->salasana_hajautus ) ) {
		return "<p class='error'>Vanha salasana ei ole oikein.</p>";
	}
	if ( strlen( $variables[ 'uusi_salasana' ] ) < 8 ) {
		return "<p class='error'>Salasanan pitää olla vähintään kahdeksan merkkiä pitkä.</p>";
	}
	if ( $variables[ 'uusi_salasana' ] !== $variables[ 'vahv_uusi_salasana' ] ) {
		return "<p class='error'>Salasanan vahvistus ei täsmää.</p>";
	}
	if ( $variables[ 'uusi_salasana' ] !== $variables[ 'vahv_uusi_salasana' ] ) {
		return "<p class='error'>Salasanan vahvistus ei täsmää.</p>";
	}

	$hajautettu_uusi_salasana = password_hash( $variables[ 'uusi_salasana' ], PASSWORD_DEFAULT );
	if ( $db->query( "UPDATE kayttaja SET salasana_hajautus = ? WHERE id = ?",
					 [ $hajautettu_uusi_salasana, $variables[ 'user_id' ] ] )
	) {
		return "<p class='success'>Salasanan vaihtaminen onnistui.</p>";
	}
	else {
		return "<p class='error'>Salasanan vaihtaminen epäonnistui.</p>";
	}
}

/**
 * @param DByhteys $db
 * @param User     $user
 * @param array    $variables
 * @return bool
 */
function muokkaa_uudet_tiedot( DByhteys $db, User $user, array $variables ) {
	$possible_fields =
		[ 'etunimi', 'sukunimi', 'sahkoposti', 'puhelin', 'yritys', 'katuosoite', 'postinumero', 'postitoimipaikka' ];
	$i = 1;
	$filtered_array = array_filter( $variables ); // Poistaa tyhjat
	unset( $filtered_array[ 'muokkaa_vanha_osoite' ] ); // Ei tarvita, ja häritsee SQL-hakua lopussa
	$len = count( $filtered_array ) - 1; // Sisältää jo osoite_id:n, jota ei tarvita pituudessa.

	if ( $len >= 1 ) {    // Onko päivitettäviä tietoja? Ei turhia sql-hakuja.
		$sql_query = "UPDATE toimitusosoite SET "; //Aloitusosa

		foreach ( $filtered_array as $key => $value ) {    // Täytetään hakuun päivitettävät arvot
			$k = htmlspecialchars( $key );
			if ( in_array( $k, $possible_fields ) ) {
				$sql_query .= $k . " = ?";
				if ( $i < $len ) {
					$sql_query .= ', ';
				} // Jos vielä arvoja, lisätään erotin
				$i++;
			}
		}

		$sql_query .= " WHERE osoite_id = ? AND kayttaja_id = ?"; //Loppuosa
		$filtered_array[] = $user->id; // Lisätään käyttäjän ID arrayhin db->querya varten
		return $db->query( $sql_query, array_values( $filtered_array ) );
	}

	return false; //Jos ei yhtään päivitettävää osaa
}

/**
 * @param DByhteys $db
 * @param User $user
 * @param $variables
 * @return bool
 */
function lisaa_uusi_osoite( DByhteys $db, User $user, array $variables ) {
	unset( $variables['tallenna_uusi_osoite'] ); //Poistetaan turha array-index.
	$variables[] = $user->id;
	$variables[] = count($user->toimitusosoitteet) + 1; //Lisätään osoite-ID (viimeinen indeksi +1).

	$sql = "INSERT INTO toimitusosoite
				(etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, 
				 postinumero, postitoimipaikka, maa, kayttaja_id, osoite_id)
			VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

	return $db->query( $sql, array_values($variables) );
}

/**
 * @param DByhteys $db
 * @param User $user
 * @param $osoite_id
 * @return bool
 */
function poista_osoite( DByhteys $db, User $user, /*int*/ $osoite_id ) {
	$osoite_id_viimeinen = count($user->toimitusosoitteet);
	$sql = "DELETE FROM toimitusosoite WHERE kayttaja_id = ? AND osoite_id = ?";
	$stmt = $db->getConnection()->prepare( $sql ); //Tarvitaan rowCount-metodia, joten hieman manuaalia PDO:ta.
	$stmt->execute( [$user->id, $osoite_id] );
	// TODO: Päivitä takaisin normaaliin luokan käyttöön
	if ( $stmt->rowCount() > 0 ) {
		$sql = "UPDATE	toimitusosoite
				SET		osoite_id = ?
				WHERE	kayttaja_id = ? AND osoite_id = ?";
		return $db->query( $sql, [$osoite_id, $user->id, $osoite_id_viimeinen] );
	}

	else return false;
}

$yritys = new Yritys( $db, $user->yritys_id );
$user->haeToimitusosoitteet( $db, -1, true );

if ( !empty( $_POST[ 'uudet_tiedot' ] ) ) {
	$sql = "UPDATE kayttaja SET etunimi = ? , sukunimi = ? , puhelin = ?
  		    WHERE id = ? LIMIT 1";
	if ( $db->query( $sql, array_values( $_POST ) ) ) {
		$_SESSION[ 'feedback' ] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
	}
	else {
		$_SESSION[ 'feedback' ] = "<p class='error'>Tietojen päivittäminen epäonnistui.</p>";
	}
}
elseif ( !empty( $_POST[ 'uusi_salasana' ] ) ) {
	$_SESSION['feedback'] = vaihda_salasana( $db, $_POST );
}
elseif ( !empty( $_POST[ "muokkaa_vanha_osoite" ] ) ) {
	muokkaa_uudet_tiedot( $db, $user, $_POST );
}
elseif ( !empty( $_POST[ "tallenna_uusi_osoite" ] ) ) {
	lisaa_uusi_osoite( $db, $user, $_POST );
}
elseif ( !empty( $_POST[ "poista_osoite" ] ) ) {
	poista_osoite( $db, $user, $_POST[ "poista_osoite" ] );
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<style>
		fieldset.muut_tiedot label {
			float: left;
			width: 220px;
			font-weight: bold;
			white-space: nowrap;
		}
	</style>
</head>
<body>
<?php require("header.php"); ?>

<main class="main_body_container lomake">
	<?= $feedback ?>

	<form method="post" accept-charset="utf-8">
		<fieldset><legend>Nykyiset tiedot</legend>
			<label class="required">Sähköposti</label> <span><?= $user->sahkoposti ?></span>
			<?php if ( $user->demo ) : ?>
				<br><br>
				<label>Voimassa</label>
				<p style="display: inline; font-size: 16px;">
					<?= (new DateTime($user->voimassaolopvm))->format("d.m.Y H:i:s") ?>
				</p>
			<?php endif; ?>
			<br><br>
			<label for="enimi">Etunimi</label>
			<input type="text" name="etunimi" value="<?= $user->etunimi ?>" id="enimi" pattern="[a-öA-Ö]{3,20}" >
			<br><br>
			<label for="snimi">Sukunimi</label>
			<input type="text" name="sukunimi" value="<?= $user->sukunimi ?>" id="snimi" pattern="[a-öA-Ö]{3,20}">
			<br><br>
			<label for="puh">Puhelin</label>
			<input type="tel" name="puh" value="<?= $user->puhelin ?>" id="puh"
			       pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
			<br><br>
			<span class="small_note"><span class="required"></span> = pakollinen kenttä</span>

			<div class="center">
				<input type="hidden" name="uudet_tiedot" value="<?= $user->id ?>">
				<input type="submit" value="Päivitä tiedot" class="nappi" >
			</div>
		</fieldset>
	</form>

	<br><br>

	<form method="post" accept-charset="utf-8">
		<fieldset><legend>Vaihda salasana</legend>
			<label for="old_ss" class="required">Vanha salasana</label>
			<input type="password" name="vanha_salasana" pattern=".{8,}" placeholder="Edellinen salasana"
			       id="old_ss" required>
			<br><br>
			<label for="uusi_salasana" class="required">Uusi salasana</label>
			<input type="password" name="uusi_salasana" pattern=".{8,}" placeholder="Pituus min 8 merkkiä"
				   id="uusi_salasana" required>
			<br><br>
			<label for="vahv_uusi_salasana" class="required">Vahvista uusi salasana</label>
			<input type="password" name="vahv_uusi_salasana" pattern=".{8,}" placeholder="Pituus min 8 merkkiä"
				   id="vahv_uusi_salasana" required>
			<br>
			<span id="check"></span> <!-- Js tarkistaa ovatko ss:t samat reaaliajassa -->
			<br>
			<span class="small_note"><span class="required"></span> = pakollinen kenttä</span>
			<div class="center">
				<input type="hidden" name="user_id" value="<?= $user->id ?>">
				<input type="submit" value="Vaihda salasana" class="nappi" id="pw_submit">
			</div>
		</fieldset>
	</form>

	<br><br>

	<fieldset class="muut_tiedot" style="display:inline-block; text-align:left;"><Legend>Osoitekirja</legend>
		<?php foreach ( $user->toimitusosoitteet as $key => $row ) : ?>
			<div> Osoite <?= $row->osoite_id ?><br>
				<br>
				<label>Nimi</label><?= "{$row->etunimi} {$row->sukunimi}" ?><br>
				<label>Sähköposti</label><?= $row->sahkoposti ?><br>
				<label>Puhelin</label><?= $row->puhelin ?><br>
				<label>Yritys</label><?= $row->yritys ?><br>
				<label>Katuosoite</label><?= $row->katuosoite ?><br>
				<label>Postinumero</label><?= $row->postinumero ?><br>
				<label>Postitoimipaikka</label><?= $row->postitoimipaikka ?><br><br>

				<input class="nappi" type="button" value="Muokkaa"
					   onClick="avaa_Modal_toimitusosoite_muokkaa(<?= $row->osoite_id ?>);">
				<input class="nappi red" type="button" value="Poista" style="background:#d20006; border-color:#b70004;"
					   onClick="vahvista_Osoitteen_Poistaminen(<?= $row->osoite_id ?>);">

				<form style="display:none;" id="<?="poista_Osoite_Form_{$row->osoite_id}"?>" name="poista_osoite" action="" method=post>
					<input type=hidden name=poista_osoite value="<?= $row->osoite_id ?>">
				</form>
			</div><hr>
		<?php endforeach; ?>
		<div class="center">
			<input class="nappi" type="button" value="Lisää uusi toimitusosoite"
				   onClick="avaa_Modal_toimitusosoite_lisaa_uusi();">
		</div>
	</fieldset>

    <br><br>
    <fieldset class="muut_tiedot" style="display:inline-block; text-align:left;"><legend>Yritys</legend>
        <label>Nimi</label><?=$yritys->nimi?><br>
        <label>Sähköposti</label><?=$yritys->sahkoposti?><br>
        <label>Puhelin</label><?=$yritys->puhelin?><br>
        <label>Katuosoite</label><?=$yritys->katuosoite?><br>
        <label>Postinumero</label><?=$yritys->postinumero?><br>
        <label>Postitoimipaikka</label><?=$yritys->postitoimipaikka?><br>
        <label>Maa</label><?=$yritys->maa?><br><br>
	    <span class="small_note">Vain ylläpitäjä voi muuttaa yritystietoja.</span>
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
			content: `
			<div>Muokkaa tietoja (Osoite ${osoite_id})</div>
			<br>
			<form action="#" method=post>
				<label>Etunimi</label>
					<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Edellinen etunimi"><br>
				<label>Sukunimi</label>
					<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Edellinen sukunimi"><br>
				<label>Sähköposti</label>
					<input name="sahkoposti" type="email" pattern=".{3,50}" placeholder="Edellinen sähköposti"><br>
				<label>Puhelin</label>
					<input name="puhelin" type="tel" pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" placeholder="Edellinen puhelinumero"><br>
				<label>Yritys</label>
					<input name="yritys" type="text" pattern="[a-öA-Ö0-9\\s]{3,50}" placeholder="Edellinen yritys"><br>
				<label>Katuosoite</label>
					<input name="katuosoite" type="text" pattern="[a-öA-Ö0-9\\s]{3,50}" placeholder="Edellinen katuosoite"><br>
				<label>Postinumero</label>
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="Edellinen postinumero"><br>
				<label>Postitoimipaikka</label>
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="Edellinen postitoimipaikka">
				<br><br>
				<input type="hidden" name="osoite_id" value="${osoite_id}">
				<input class="nappi" type="submit" name="muokkaa_vanha_osoite" value="Tallenna uudet tiedot (tyhjiä kenttiä ei oteta huomioon)">
				<br>
			</form>
			`,
			draggable: true
		} );
	}

	/**
	 * Modal-ikkuna uuden toimitusosoitteen lisäämistä varten
	 */
	function avaa_Modal_toimitusosoite_lisaa_uusi() {
		Modal.open( {
			content:  `
			<div>Lisää uuden toimitusosoitteen tiedot</div>
			<br>
			<form action="#" method=post>
				<label class="required">Etunimi</label>
					<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Etunimi" required><br>
				<label class="required">Sukunimi</label>
					<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Sukunimi" required><br>
				<label class="required">Sähköposti</label>
					<input name="sahkoposti" type="email" pattern=".{3,50}" placeholder="yourname@email.com" required><br>
				<label class="required">Puhelin</label>
					<input name="puhelin" type="tel" pattern="((\\+|00)?\\d{3,5}|)((\\s|-)?\\d){3,10}" placeholder="000 1234 789" required><br>
				<label>Yritys</label>
					<input name="yritys" type="text" pattern="[a-öA-Ö0-9\\s]{3,50}" placeholder="Yritys Oy"><br>
				<label class="required">Katuosoite</label>
					<input name="katuosoite" type="text" pattern="[a-öA-Ö0-9\\s]{3,50}" placeholder="Katu 42" required><br>
				<label class="required">Postinumero</label>
					<input name="postinumero" type="text" pattern="[0-9]{3,10}" placeholder="00001" required><br>
				<label class="required">Postitoimipaikka</label>
					<input name="postitoimipaikka" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="KAUPUNKI" required><br>
				<label>Maa</label>
					<input name="maa" type="text" pattern="[a-öA-Ö]{3,50}" placeholder="Maa">
				<br><br>
				<span class="small_note"><span class="required"></span> = pakollinen kenttä</span><br>
				<input class="nappi" type="submit" name="tallenna_uusi_osoite" value="Tallenna uusi osoite">
				<br>
			</form>
			`,
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
		let vahvistus = confirm( "Oletko varma, että haluat poistaa osoitteen?\n"
			+ "Tätä toimintoa ei voi perua jälkeenpäin.\n"
			+ "(Huom. Osoitetietoja ei poisteta mahdollisista tilaustiedoista.)");
		if ( vahvistus === true ) {
			document.getElementById("poista_Osoite_Form_" + osoite_id).submit();
		} else {
			return false;
		}
	}

	$(document).ready(function() {
		let pwSubmit = $('#pw_submit'); // Salasanan pituuden ja vahvistuksen tarkistusta varten
		let newPassword = $('#uusi_salasana'); // Ditto
		let pwCheck = $('#check'); // Ditto

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
