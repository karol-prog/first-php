<?php
class Prichody {
	public $pocetPrichodov;
	public $meskanie;

	public function __construct($pocetPrichodov, $meskanie) {
		$this->pocetPrichodov = $pocetPrichodov;
		$this->meskanie = $meskanie;
	}
}
?>