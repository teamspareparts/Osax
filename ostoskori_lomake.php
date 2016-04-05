<form name="ostoskorilomake" method="post">
	<input id="ostoskori_toiminto" type="hidden" name="ostoskori_toiminto" value="">
	<input id="ostoskori_tuote" type="hidden" name="ostoskori_tuote">
	<input id="ostoskori_maara" type="hidden" name="ostoskori_maara">
</form>
<script>

//
// Lisää annetun tuotteen ostoskoriin
//
function addToShoppingCart(articleId) {
	var count = document.getElementById('maara_' + articleId).value;
	document.getElementById('ostoskori_toiminto').value = 'lisaa';
	document.getElementById('ostoskori_tuote').value = articleId;
	document.getElementById('ostoskori_maara').value = count;
	document.ostoskorilomake.submit();
}

//
// Muokkaa annettua tuotetta ostoskorissa
//
function modifyShoppingCart(articleId) {
	var count = document.getElementById('maara_' + articleId).value;
	document.getElementById('ostoskori_toiminto').value = 'muokkaa';
	document.getElementById('ostoskori_tuote').value = articleId;
	document.getElementById('ostoskori_maara').value = count;
	document.ostoskorilomake.submit();
}

//
// Poistaa annetun tuotteen ostoskorista
//
function removeFromShoppingCart(articleId) {
	document.getElementById('ostoskori_toiminto').value = 'poista';
	document.getElementById('ostoskori_tuote').value = articleId;
	document.ostoskorilomake.submit();
}

//
// Tyhjentää koko ostoskorin
//
function emptyShoppingCart(articleId) {
	document.getElementById('ostoskori_toiminto').value = 'tyhjenna';
	document.ostoskorilomake.submit();
}

</script>
