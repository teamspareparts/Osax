<!DOCTYPE html>
<html>
	<head>
		<script src="lodash.js"></script>
		<script src="jszip.js"></script>
		<script src="xlsx.js"></script>
		<script src="xlsx-reader.js"></script>
	</head>

	<body>
		
		<input type="file" id="myFile" accept=".xlsx" required><br />
		<br />
		
		<button onclick="readFile()"> Read file (and print) </button>
		
		<p id="demo"> --Test printing here!-- </p>
		
		<script>
			function readFile() {
				//Check if File API is supported on current browser
				if (!window.File ||
					!window.FileReader ||
					!window.FileList || 
					!window.Blob ) {
					alert('The File APIs are not fully supported in this browser.');
				
				} else { //File API supported
					var x = document.getElementById("myFile");
					var file = x.files[0];
					var txt = x.files.length + "<br>" +
						file.name + "<br>" +
						file.size + "<br>";
					
					document.getElementById("demo").innerHTML = txt;
					
					XLSXReader(file, true, true, function(data) {
						// print data in console.
						output = JSON.stringify(data, null, 2);
						alert(output);
					});
				}
			}
		</script>
	</body>
</html>