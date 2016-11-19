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

	/**
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function a_hinta_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false ) {
		$hinta = $ilman_alv ? $this->a_hinta_ilman_alv : $this->a_hinta;
		return number_format( (double)$hinta, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}

	/**
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function summa_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false ) {
		$hinta = $ilman_alv ? ($this->a_hinta_ilman_alv*$this->kpl_maara) : $this->summa;
		return number_format( (double)$hinta, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}
}
