<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: jjarv
 * Date: 15/01/2018
 * Time: 15:50
 */

class OtkTuote extends Tuote {

	// Yhteiset (odottava ja arkisto):
	/** @var int $ostotilauskirja_id */
	public $ostotilauskirja_id;
	/** @var int $tuote_id */
	public $tuote_id;
	/** @var bool $automaatti */
	public $automaatti;
	/** @var bool $tilaustuote */
	public $tilaustuote;
	/** @var int $kpl */
	public $kpl;
	/** @var string $selite */
	public $selite;
	/** @var string $lisays_pvm Timestamp tietokannassa */
	public $lisays_pvm;
	/** @var int $lisays_kayttaja_id */
	public $lisays_kayttaja_id;

	// arkisto-kohtaiset:
	/** @var int $original_kpl */
	public $original_kpl;
	/** @var float $ostohinta */
	public $ostohinta;

	// Muuta:
	/** @var int $vuosimyynti_kpl */
	public $vuosimyynti_kpl;
	/** @var int $vuosimyynti_hylly_kpl */
	public $vuosimyynti_hylly_kpl;
	/** @var int $sisaanostohinta */
	public $sisaanostohinta;
	/** @var int $kokonaishinta */
	public $kokonaishinta;
}
