<?php
require_once(dirname(dirname(__FILE__)) . '/src/willgriffin/MariaInterface/MariaInterface.php');
use willgriffin\MariaInterface\MariaInterface as myClass;

class MariaInterfaceTest extends PHPUnit_Framework_TestCase
{
	public function testCanBeNegated () {
		$a = new myClass();
		$a->increase(9)->increase(8);
		$b = $a->negate();
		$this->assertEquals(0, $b->myParam);
	}

}
?>