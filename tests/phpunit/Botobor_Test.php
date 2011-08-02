<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/libbotobor.php';

class Botobor_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor::secret
	 * @covers Botobor::setSecret
	 */
	public function test_secrets()
	{
		$this->assertEquals(filemtime(SRC_ROOT . '/libbotobor.php'), Botobor::secret());

		$secret = 'My secret';
		Botobor::setSecret($secret);
		$this->assertEquals($secret, Botobor::secret());
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor::sign
	 * @covers Botobor::signature
	 * @covers Botobor::verify
	 */
	public function test_signing()
	{
		$data = 'Form data';
		$signedData = Botobor::sign($data);
		$this->assertEquals($data, Botobor::verify($signedData));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor::getDefault
	 * @covers Botobor::setDefault
	 */
	public function test_defaults()
	{
		Botobor::setDefault('delay', 10);
		$this->assertEquals(10, Botobor::getDefault('delay'));
	}
	//-----------------------------------------------------------------------------

	/* */
}