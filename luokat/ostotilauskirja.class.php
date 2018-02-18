<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: jjarv
 * Date: 15/01/2018
 * Time: 15:50
 */

class Ostotilauskirja {

	// Yhteiset (odottava ja arkisto):
	/** @var int $id */
	public $id;
	/** @var int $hankintapaikka_id */
	public $hankintapaikka_id;
	/** @var string $tunniste */
	public $tunniste;
	/** @var double $rahti */
	public $rahti;
	/** @var string $oletettu_saapumispaiva Timestamp tietokannassa */
	public $oletettu_saapumispaiva;

	// Odottava-kohtainen:
	/** @var string $oletettu_lahetyspaiva Timestamp tietokannassa */
	public $oletettu_lahetyspaiva;
	/** @var int $toimitusjakso Tilauksen toimitusväli viikkoina, 0: erikoistilaus */
	public $toimitusjakso;

	// arkisto-kohtaiset:
	/** @var double $original_rahti */
	public $original_rahti;
	/** @var string $lahetetty Timestamp tietokannassa */
	public $lahetetty;
	/** @var int $lahettaja Käyttäjä-ID */
	public $lahettaja;
	/** @var string $saapumispaiva Timestamp tietokannassa */
	public $saapumispaiva;
	/** @var bool $hyvaksytty */
	public $hyvaksytty;
	/** @var int $vastaanottaja Käyttäjä-ID */
	public $vastaanottaja;
	/** @var int $ostotilauskirja_id WAT?? */
	public $ostotilauskirja_id;

}
