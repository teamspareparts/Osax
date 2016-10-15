
<?php
require '_start.php'; global $db, $user, $cart, $yritys;

$yritys = new Yritys( $db, (!empty($_GET['id']) ? (int)$_GET['id'] : null));
if ( !$user->isAdmin() || !$yritys->isValid() ) {
    header("Location:etusivu.php"); exit();
}

/**
 * @param $yritys_nimi
 * @param $y_tunnus
 * @param $email
 * @param $puh
 * @param $osoite
 * @param $postinumero
 * @param $postitoimipaikka
 * @param $maa
 * @return array|bool|stdClass
 */
function db_muokkaa_yritysta(DByhteys $db, $yritys_nimi, $y_tunnus, $email,
                             $puh, $osoite, $postinumero, $postitoimipaikka, $maa){

    //Tarkastetaan onko samannimistä käyttäjätunnusta
    $query = "UPDATE yritys 
                  SET sahkoposti= ?, puhelin = ?, katuosoite = ?, 
                      postinumero = ?, postitoimipaikka= ?, maa = ?
                  WHERE y_tunnus= ? AND nimi= ?";
    return $db->query($query, [$email, $puh, $osoite, $postinumero, $postitoimipaikka, $maa, $y_tunnus, $yritys_nimi]);
}


function muuta_rahtimaksu(DByhteys $db, array $values){
    $query = "	UPDATE	yritys 
					SET 	rahtimaksu = ?, ilmainen_toimitus_summa_raja = ?
					WHERE	id= ?";
    return $db->query($query, [$values["rahtimaksu"], $values["ilmainen_toimitus"], $values["id"]]);
}

$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION["feedback"]);

if (isset($_POST['muokkaa_yritysta'])) {
    $result = db_muokkaa_yritysta($db, $_POST['nimi'], $_POST['y_tunnus'], $_POST['email'], $_POST['puh'],
        $_POST['osoite'], $_POST['postinumero'], $_POST['postitoimipaikka'], $_POST['maa']);
    $_SESSION["feedback"] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}
elseif (isset($_POST['muokkaa_rahtimaksu'])) {
    muuta_rahtimaksu($db, $_POST);
    $_SESSION['feedback'] = "<p class='success'>Rahtimaksu ja ilmaisen toimituksen raja päivitetty.</p>";
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}


?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Yritykset</title>
</head>
<body>
<?php require 'header.php'?>

<main class="main_body_container lomake">
    <?= $feedback ?>
    <a class="nappi" href="yp_yritykset.php" style="color:#000; background-color:#c5c5c5; border-color:#000;">
        Takaisin</a><br><br>
    <form action="" name="muokkaa_yritysta" method="post" accept-charset="utf-8">
        <fieldset><legend>Muokkaa yrityksen tietoja</legend>
            <br>
            <label><span>Yritys</span></label>
            <p style="display: inline; font-size: 16px; font-weight: bold"><?= $yritys->nimi?></p>
            <br><br>
            <label><span>Y-tunnus</span></label>
            <p style="display: inline; font-size: 16px; font-weight: bold"><?= $yritys->y_tunnus?></p>
            <br><br>
            <label><span>Sähköposti</span></label>
            <input name="email" type="email" value="<?= $yritys->sahkoposti?>" />
            <br><br>
            <label><span>Puhelin</span></label>
            <input name="puh" type="tel" value="<?= $yritys->puhelin?>"
                   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
            <br><br>
            <label><span>Katuosoite</span></label>
            <input name="osoite" type="text" pattern=".{1,50}" value="<?= $yritys->katuosoite?>" />
            <br><br>
            <label><span>Postinumero</span></label>
            <input name="postinumero" type="text" value="<?= $yritys->postinumero?>" />
            <br><br>
            <label><span>Postitoimipaikka</span></label>
            <input name="postitoimipaikka" type="text" value="<?= $yritys->postitoimipaikka?>" />
            <br><br>
            <label><span>Maa</span></label>
            <input name="maa" type="text" value="<?= $yritys->maa?>" />
            <input name="nimi" type="hidden" value="<?= $yritys->nimi?>" />
            <input name="y_tunnus" type="hidden" value="<?= $yritys->y_tunnus?>" />
            <br><br>
            <div class="center">
                <input class="nappi" name="muokkaa_yritysta" value="Muokkaa yritystä" type="submit">
            </div>
        </fieldset>
    </form>
    <br><br>
    <form action="#" method="post">
        <fieldset><legend>Yrityksen rahtimaksu</legend>
            <span>Kumpikin arvo euroina (€). <br> Nollan kohdalla ilmainen toimitus aina.</span>
            <br><br>
            <label><span>Rahtimaksu:</span></label>
            <input name="rahtimaksu" type="number" step="0.01" min="0" pattern=".{1,10}" value="<?= $yritys->rahtimaksu; ?>" title="Anna käyttäjäkohtainen rahtimaksu euroina (€).">
            <br>
            <label><span>Ilmaisen toimitus:</span></label>
            <input name="ilmainen_toimitus" type="number" step="0.01" min="0" pattern=".{1,10}" value="<?= $yritys->ilmainen_toimitus_summa_raja; ?>" title="Ilmaisen toimituksen raja euroina (€).">
            <br>
            <div class="center">
                <input name="muokkaa_rahtimaksu" value="Muokkaa rahtimaksua" type="submit" class="nappi">
            </div>
            <input name="id" value="<?= $id?>" type="hidden"/>
        </fieldset>
    </form>

</main>
</body>
</html>
