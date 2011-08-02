<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/libbotobor.php';

class Botobor_Keeper_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown()
	{
		$p_isHuman = new ReflectionProperty('Botobor_Keeper', 'isHuman');
		$p_isHuman->setAccessible(true);
		$p_isHuman->setValue('Botobor_Keeper', false);
	}
	//-----------------------------------------------------------------------------
	/**
	 * @covers Botobor_Keeper::isHuman
	 */
	public function test_isHuman()
	{
		$this->assertFalse(Botobor_Keeper::isHuman());

		$p_isHuman = new ReflectionProperty('Botobor_Keeper', 'isHuman');
		$p_isHuman->setAccessible(true);
		$p_isHuman->setValue('Botobor_Keeper', true);

		$this->assertTrue(Botobor_Keeper::isHuman());

	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Keeper::handleRequest
	 */
	public function test_handleRequest()
	{
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$_SERVER['REQUEST_METHOD'] = 'GET';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST[Botobor::META_FIELD_NAME] = true;
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->aliases = array('aaa' => 'name');
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		$_POST['name'] = 'RobotName';
		$_POST['aaa'] = 'HumanName';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());
		$this->assertEquals('HumanName', $_POST['name']);


		$meta = new Botobor_MetaData();
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode() . 'break_sign';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->timestamp = time();
		$meta->delay = 10;
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->timestamp = time() - 11 * 60;
		$meta->lifetime = 10;
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		$_SERVER['HTTP_REFERER'] = 'http://example.org/';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());



		$meta = new Botobor_MetaData();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->encode();
		$_SERVER['HTTP_REFERER'] = 'http://example.org/index.php';
		Botobor_Keeper::handleRequest();
		$this->assertTrue(Botobor_Keeper::isHuman());
	}
	//-----------------------------------------------------------------------------

	/* */
}