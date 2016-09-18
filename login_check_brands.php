<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Toimittajat</title>
</head>
<body>
<?php
require 'tietokanta.php';
require 'tecdoc.php';
session_start();
if (!(isset($_SESSION['admin']) && $_SESSION['admin'] == 1)){header("Location:index.php?redir=4");exit();}
?>
<h1 class="otsikko">Brandit</h1><br>
<div class="container">
    <p>Anna valmistajille uniikit ID:t (001-999)</p>
    <form action="" method="post" name="valmistajan_id" id="valmistaja_id_form">


<?php

function save_new_brands(DByhteys $db){
    $brandIds = $_POST["brandId"];  //Tecdocin antamat id:t
    $valmistajaIds = $_POST["valmistajaId"];
    $brandName = $_POST["brandName"];
    $query = "INSERT INTO valmistaja (brandId, brandName, valmistajan_id) VALUES ( ?, ?, ? )  ";
    for ($i = 0; $i<count($valmistajaIds); $i++) {
        $db->query($query, [$brandIds[$i], $brandName[$i], $valmistajaIds[$i]]);
    }
}

function get_new_brands( DByhteys $db ){
    $new_brands = [];
    $brandsInTecdoc = getAmBrands();
    $query = "  SELECT * FROM valmistaja";
    $currentBrands = $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);
    if (!$currentBrands) return $brandsInTecdoc;
    foreach ($brandsInTecdoc as $tbrand){
        foreach ($currentBrands as $cbrand){
            if ($tbrand->brandId == $cbrand->brandId){
                continue 2;
            }
        }
        $new_brands[] = $tbrand;
    }
    return $new_brands;
}

function get_existing_ids( DByhteys $db ){
    $query = "  SELECT LPAD(`valmistajan_id`,3,'0') AS id FROM valmistaja";
    $currentBrands = $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);
    $ids=[];
    foreach ($currentBrands as $brand) {
        $ids[] = $brand->id;
    }
    if (!$currentBrands) return array();
    return $ids;
}

if (isset($_POST["submit"])){
    save_new_brands( $db );
    header("Location:tuotehaku.php");
    exit();
}

$brands = get_new_brands( $db );
$existing_ids = get_existing_ids( $db );
if ($existing_ids) {
    echo "<p>Varatut IDt:</p>";
    foreach ($existing_ids as $id) {
        echo "<span>". $id . " </span>";
    }
    echo "<br><br>";
}


foreach ($brands as $brand) : ?>
    <label style="display:block"><?= $brand->brandName?></label>
    <input name="valmistajaId[]" type="text" pattern="00[1-9]|0[1-9][0-9]|[1-9][0-9]{2}" title="Numero väliltä 001-999" required>
    <input name="brandId[]" type="hidden" value="<?= $brand->brandId?>">
    <input name="brandName[]" type="hidden" value="<?= $brand->brandName?>">
    <br>
<?php endforeach; if ($brands) :?>
    <input type="submit" name="submit">
<?php endif; ?>


    </form>
</div>

<script type="text/javascript">
    $('#valmistaja_id_form').submit(function( event ) {
        var duplicate = [];
        var ids = <?php echo json_encode($existing_ids); ?>;
        var new_ids = $('#valmistaja_id_form').serializeArray();
        //Tarkastetaan onko id jo tietokannassa
        if (ids.length > 0) {
            //Tarkastetaan onko id jo tietokannassa
            for (var i = 0; i < new_ids.length; i++) {
                for (var j = 0; j < ids.length; j++) {
                    if (new_ids[i].value == ids[j]) {
                        if (duplicate.indexOf(new_ids[i].value) == -1) {
                            duplicate.push(new_ids[i].value);
                        }
                    }
                }
            }
        }
        //Tarkastetaan, että ei duplikaatteja input-kentissä
        for(i=0; i<new_ids.length; i++){
            for(j=0; j<new_ids.length; j++){
                if(i==j) continue;
                if (new_ids[i].value == new_ids[j].value){
                    if(duplicate.indexOf(new_ids[i].value) == -1) {
                        duplicate.push(new_ids[j].value);
                    }
                }
            }
        }
        if(duplicate.length != 0){
            event.preventDefault();
            alert("ID oltava uniikki.\nVirheelliset id:t:\n\n" + duplicate.join("\n"));
        }
    });
</script>
</body>
</html>




