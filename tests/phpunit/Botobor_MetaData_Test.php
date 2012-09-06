<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/botobor.php';

class Botobor_MetaData_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor_MetaData::__get
	 * @covers Botobor_MetaData::__set
	 */
	public function test_get_set()
	{
		$meta = new Botobor_MetaData();
		$meta->a = 'b';
		$this->assertEquals('b', $meta->a);
		$this->assertNull($meta->b);

		$meta->arr = array();
		$meta->arr['a'] = 'b';
		$this->assertEquals('b', $meta->arr['a']);
	}

	/**
	 * @covers Botobor_MetaData::isValid
	 */
	public function test_isValid()
	{
		$meta = new Botobor_MetaData();
		$p_isValid = new ReflectionProperty('Botobor_MetaData', 'isValid');
		$p_isValid->setAccessible(true);
		$p_isValid->setValue($meta, true);
		$this->assertTrue($meta->isValid());
	}

	/**
	 * @covers Botobor_MetaData::__construct
	 * @covers Botobor_MetaData::getEncoded
	 * @covers Botobor_MetaData::import
	 * @covers Botobor_MetaData::signature
	 */
	public function test_encoding_decoding()
	{
		$meta = new Botobor_MetaData();
		$meta->param1 = 'value1';
		$meta->param2 = 'value2';
		$encoded = $meta->getEncoded();

		$meta = new Botobor_MetaData($encoded);
		$this->assertTrue($meta->isValid());
		$this->assertEquals('value1', $meta->param1);
		$this->assertEquals('value2', $meta->param2);
	}

	/* */
}