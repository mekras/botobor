<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/botobor.php';

class Botobor_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor::get
	 * @covers Botobor::set
	 */
	public function test_secrets()
	{
		$this->assertEquals(filemtime(SRC_ROOT . '/botobor.php'), Botobor::get('secret'));

		$secret = 'My secret';
		Botobor::set('secret', $secret);
		$this->assertEquals($secret, Botobor::get('secret'));
	}

	/**
	 * @covers Botobor::get
	 * @covers Botobor::set
	 */
	public function test_get_set()
	{
		Botobor::set('delay', 10);
		$this->assertEquals(10, Botobor::get('delay'));
	}

    /**
     * @covers Botobor::set
     * @expectedException InvalidArgumentException
     */
    public function test_set_invalid_option()
    {
        Botobor::set('foo', 'bar');
    }

    /**
     * @covers Botobor::set
     * @expectedException InvalidArgumentException
     */
    public function test_set_invalid_value()
    {
        Botobor::set('delay', 'foo');
    }
}