<form name="ostoskorilomake" method="post" style="display: none;">
	<input id="ostoskori_toiminto" type="hidden" name="ostoskori_toiminto" value="">
	<input id="ostoskori_tuote" type="hidden" name="ostoskori_tuote">
	<input id="ostoskori_maara" type="hidden" name="ostoskori_maara">
</form>
<script>

//
// Lisää annetun tuotteen ostoskoriin
//
function addToShoppingCart(id) {
	var count = document.getElementById('maara_' + id).value;
	document.getElementById('ostoskori_toiminto').value = 'lisaa';
	document.getElementById('ostoskori_tuote').value = id;
	document.getElementById('ostoskori_maara').value = count;
	document.ostoskorilomake.submit();
}

//
// Muokkaa annettua tuotetta ostoskorissa
//
function modifyShoppingCart(id) {
	var count = document.getElementById('maara_' + id).value;
	document.getElementById('ostoskori_toiminto').value = 'muokkaa';
	document.getElementById('ostoskori_tuote').value = id;
	document.getElementById('ostoskori_maara').value = count;
	document.ostoskorilomake.submit();
}

//
// Poistaa annetun tuotteen ostoskorista
//
function removeFromShoppingCart(id) {
	document.getElementById('ostoskori_toiminto').value = 'poista';
	document.getElementById('ostoskori_tuote').value = articleNo;
	document.ostoskorilomake.submit();
}

//
// Tyhjentää koko ostoskorin
//
function emptyShoppingCart() {
	document.getElementById('ostoskori_toiminto').value = 'tyhjenna';
	document.ostoskorilomake.submit();
}

</script>
