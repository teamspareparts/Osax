<?php
/**
 * Created by PhpStorm.
 * User: jjarv
 * Date: 23/10/2016
 * Time: 11:03
 */
class Tuote {
	public $id = NULL;
	public $tuotekoodi = '[Tuotekoodi]';
	public $tuotenimi = '[Tuotteen nimi]';
	public $valmistaja = '[Tuotteen valmistaja]';
	public $a_hinta = 0.00;
	public $a_hinta_ilman_alv = 0.00;
	public $alv_prosentti = 0;
	public $alennus = 0.00;
	public $kpl_maara = 0;
	public $summa = 0.00;

	/** */
	function a_hinta_toString () {
		return number_format ( (double)$this->a_hinta, 2, ',', '.' );
	}

	/** */
	function summa_toString () {
		return number_format( (double)$this->summa, 2, ',', '.' );
	}
}
