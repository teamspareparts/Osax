<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Yritykset</title>
</head>
<body>
<?php
    require '_start.php'; global $db, $user, $cart, $yritys;
    require 'header.php';
    if (!is_admin()) {
        header("Location:tuotehaku.php");
        exit();
    }
?>
<div id=asiakas>
    <h1 class="otsikko">Asiakasyritykset</h1>
    <div id="painikkeet">
        <a href="yp_lisaa_yritys.php"><span class="nappi">Lisää uusi Yritys</span></a>
    </div>
    <br><br><br>


    <div id="lista">

        <form action="yp_yritykset.php" method="post">
            <table class="asiakas_lista">
                <tr><th>Yritys</th><th>Y-tunnus</th><th>Osoite</th><th>Maa</th><th class="smaller_cell">Poista</th><th class=smaller_cell></th></tr>

                <?php



                $query = "SELECT * FROM yritys";
                $yritykset = $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);

                //listataan kaikki tietokannasta löytyvät yritykset
                //Yritystä painamalla pääsee yrityksen asiakkaisiin
                foreach ($yritykset as $y){
                    if ($y->aktiivinen == 1) {

                        echo '<tr data-val="' . $y->id . '">';
                        echo '<td class="cell">' . $y->nimi .
                            '</td><td class="cell">' . $y->y_tunnus .
                            '</td><td class="cell">' . $y->katuosoite . '<br>' . $y->postinumero . ' ' . $y->postitoimipaikka .
                            '</td><td class="cell">' . $y->maa .
                            '</td><td class="smaller_cell">' .
                            '<input type="checkbox" name="ids[]" value="' . $y->id . '">' .
                            '</td><td class="smaller_cell"><a href="yp_muokkaa_yritysta.php?id=' . $y->id . '"><span class="nappi">Muokkaa</span></a></td>';

                        echo '</tr>';
                    }
                }
                echo '</table>';

                ?>
                <br>
                <div id=submit>
                    <input type="submit" value="Poista valitut Yritykset">
                </div>
        </form>
    </div>

</div>
<?php
if (isset($_POST['ids'])){
    db_poista_yritys($_POST['ids']);

    header("Location:yp_yritykset.php");
    exit;
}



function db_poista_yritys($ids){
    global $db;

    foreach ($ids as $yritys_id) {
        $query = "UPDATE yritys
							SET aktiivinen=0
							WHERE id=?";
        $result = $db->query($query, [$yritys_id]);
        $query = "UPDATE kayttaja
							SET aktiivinen=0
							WHERE yritys_id=?";
        $result = $db->query($query, [$yritys_id]);
    }

    return;
}
?>


<script type="text/javascript">
    $(document).ready(function(){


        //painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
        $('.cell').click(function(){
            $('tr').click(function(){
                var id = $(this).attr('data-val');
                window.document.location = 'yp_asiakkaat.php?yritys_id='+id;
            });
        });

        $('.cell').css('cursor', 'pointer');

    });

</script>

</body>
</html>
