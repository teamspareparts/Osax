
<?php
require '_start.php'; global $db, $user, $cart, $yritys;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$user->isAdmin() || !hae_yritys($db, $id)) {
    header("Location:etusivu.php");
    exit();
}


/**
 * @param DByhteys $db
 * @param $id
 * @return bool
 */
function hae_yritys(DByhteys $db, /*int*/ $id){
    //Haetaan yrityksen tiedot
    $query = "	SELECT *
			FROM yritys 
			WHERE id= ? ";
    $yritys = $db->query( $query, [$id], FETCH_ALL, PDO::FETCH_OBJ );
    if (count($yritys) == 1) {
        return $yritys[0];
    } else {
        return false;
    }
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

$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "<p></p>";
unset($_SESSION["feedback"]);


if (isset($_POST['submit'])) {
    $result = db_muokkaa_yritysta($db, $_POST['nimi'], $_POST['y_tunnus'], $_POST['email'], $_POST['puh'],
        $_POST['osoite'], $_POST['postinumero'], $_POST['postitoimipaikka'], $_POST['maa']);
    $_SESSION["feedback"] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
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

<h1 class="otsikko">Muokkaa yritystä</h1>
<main id="lomake">
    <?= $feedback ?>
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
            <input name="puh" type="text" pattern=".{4,20}" value="<?= $yritys->puhelin?>" />
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
                <input class="nappi" name="submit" value="Lisää yritys" type="submit">
            </div>
        </fieldset>

    </form>
</main>
</body>
</html>
