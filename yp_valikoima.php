<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Valikoima</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/css/bootstrap.min.css" integrity="sha384-MIwDKRSSImVFAZCVLtU0LMDdON6KVCrZHyVQQj6e8wIEJkW4tvwqXrbMIya1vriY" crossorigin="anonymous">
</head>
<body>
<?php
require 'header.php';
require 'tietokanta.php';


if ( !is_admin() ) { header("Location:etusivu.php"); exit(); }
//if ( !isset($_GET['brand']) ) { header("Location:yp_toimittajat.php"); exit(); }
$brand = isset($_GET['brand']) ? (int)$_GET['brand'] : 1; //TODO: Temp solution
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Mikä sivu tuotelistauksessa
$products_per_page = 20; // Miten monta tuotetta per sivu näytetään. Ei aikomusta olla käyttäjän muutettavissa.
$offset = ($page-1) * $products_per_page; // SQL-lausetta varten; kertoo monennestako tuloksesta aloitetaan haku

$query = "	SELECT *, COUNT(id) as row_count
			FROM tuote 
			WHERE brandNo = ?
			LIMIT {$products_per_page} OFFSET {$offset}";

//echo "<pre>";
//echo $query;
$products = $db->query( $query, [$brand], FETCH_ALL, PDO::FETCH_OBJ );
?>

<main>
	Pagination not implemented yet
	<nav aria-label="Page navigation">
		<ul class="pagination">
			<li class="page-item disabled">
				<a class="page-link" href="?brand=<?=$brand?>" aria-label="Previous">
					<span aria-hidden="true">&LeftArrowBar;</span>
					<span class="sr-only">Previous</span>
				</a>
			</li>
			<li class="page-item disabled">
				<a class="page-link" href="?brand=<?=$brand?>&page=<?=$page-1?>" aria-label="Previous">
					<span aria-hidden="true">&laquo;</span>
					<span class="sr-only">Previous</span>
				</a>
			</li>

			<li class="page-item active"><span class="page-link">
					[DisplayedItemRange] of [TotalItems]</span></li>

			<li class="page-item">
				<a class="page-link" href="?brand=<?=$brand?>&page=<?=$page+1?>" aria-label="Next">
					<span aria-hidden="true">&raquo;</span>
					<span class="sr-only">Next</span>
				</a>
			</li>
			<li class="page-item">
				<a class="page-link" href="?brand=<?=$brand?>&page=<?=$page?>" aria-label="Next">
					<span aria-hidden="true">&RightArrowBar;</span>
					<span class="sr-only">Next</span>
				</a>
			</li>
		</ul>
	</nav>

	<table>
		<thead>
		<tr><td>ID</td>
			<td>ArtNo</td>
			<td>BrandNo</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td>
			<td>#</td></tr>
		</thead>

		<tbody>
		<?php foreach ( $products as $p ) : ?>
		<tr><td><?= $p->id ?></td>
			<td><?= $p->articleNo ?></td>
			<td><?= $p->brandNo ?></td>
			<td><?= $p->hinta_ilman_ALV ?></td>
			<td><?= $p->ALV_kanta ?></td>
			<td><?= $p->varastosaldo ?></td>
			<td><?= $p->minimimyyntiera ?></td>
			<td><?= $p->sisaanostohinta ?></td>
			<td><?= $p->yhteensa_kpl ?></td>
			<td><?= $p->keskiostohinta ?></td>
			<td><?= $p->alennusera_kpl ?></td>
			<td><?= $p->alennusera_prosentti ?></td>
			<td><?= $p->aktiivinen ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</main>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/js/bootstrap.min.js" integrity="sha384-ux8v3A6CPtOTqOzMKiuo3d/DomGaaClxFYdCu2HPMBEkf6x2xiDyJ7gkXU0MWwaD" crossorigin="anonymous"></script>
</body>
</html>
