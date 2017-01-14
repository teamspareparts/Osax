<?php

function download_csv_results($name = NULL)
{
	if( ! $name)
	{
		$name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
	}

	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename='. $name);
	header('Pragma: no-cache');
	header("Expires: 0");

	$outstream = fopen("php://output", "w");


	fputcsv($outstream, array("asd", "asdasd"));

	fclose($outstream);
}

download_csv_results();
exit();