<?php

/**
 * //TODO: PhpDoc
 */
class Tuoteryhma {

	public $id;
	/**
	 * @var int <p> Puun toimintaan
	 */
	public $parentID;
	/**
	 * @var int <p> Kyseisen tuoteryhmän nykyinen taso puussa.
	 */
	public $omaTaso;
	/**
	 * @var array Tuoteryhmän lapset puussa.
	 */
	public $children = array();

	/**
	 * @var string <p> Tuoteryhmän nimi.
	 */
	public $nimi;
	/**
	 * @var float <p> Hinnoittelukerroin tuoteryhmän tuotteille.
	 */
	public $hinnoittelukerroin;

	function __construct( $id = null, $parentID = null, $oTaso = null, $nimi = null ) {
		if ( $id != null ) {
			$this->id = $id;
			$this->parentID = $parentID;
			$this->omaTaso = $oTaso;
			$this->nimi = $nimi;
		}
	}
}
