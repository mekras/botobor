<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/botobor.php';

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
		$checks = Botobor::getChecks();
		foreach ($checks as $check => $state)
		{
			Botobor::setCheck($check, true);
		}

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
		$meta->checks = Botobor::getChecks();
		$meta->aliases = array('aaa' => 'name');
		$data = array(
			Botobor::META_FIELD_NAME => $meta->getEncoded(),
			'name' => 'RobotName',
			'aaa' => 'HumanName',
		);
		Botobor_Keeper::handleRequest($data);
		$this->assertFalse(Botobor_Keeper::isHuman());
		$this->assertEquals('HumanName', $data['name']);


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$_POST[Botobor::META_FIELD_NAME] = $meta->getEncoded() . 'break_sign';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$meta->timestamp = time();
		$meta->delay = 10;
		$data = array(Botobor::META_FIELD_NAME => $meta->getEncoded());
		Botobor_Keeper::handleRequest($data);
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$meta->timestamp = time() - 11 * 60;
		$meta->lifetime = 10;
		$_POST[Botobor::META_FIELD_NAME] = $meta->getEncoded();
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->getEncoded();
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->getEncoded();
		$_SERVER['HTTP_REFERER'] = 'http://example.org/';
		Botobor_Keeper::handleRequest();
		$this->assertFalse(Botobor_Keeper::isHuman());


		$meta = new Botobor_MetaData();
		$meta->checks = Botobor::getChecks();
		$meta->timestamp = time() - 15;
		$meta->delay = 10;
		$meta->lifetime = 10;
		$meta->referer = 'http://example.org/index.php';
		$_POST[Botobor::META_FIELD_NAME] = $meta->getEncoded();
		$_SERVER['HTTP_REFERER'] = 'http://example.org/index.php';
		Botobor_Keeper::handleRequest();
		$this->assertTrue(Botobor_Keeper::isHuman());
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Keeper::handleRequest
	 */
	public function test_handleRequest_custom()
	{
		$req = array('name' => 'RobotName', 'aaa' => 'HumanName');
		$meta = new Botobor_MetaData();
		$meta->aliases = array('aaa' => 'name');
		$req[Botobor::META_FIELD_NAME] = $meta->getEncoded();
		Botobor_Keeper::handleRequest($req);
		$this->assertFalse(Botobor_Keeper::isHuman());
		$this->assertEquals('HumanName', $req['name']);
	}
	//-----------------------------------------------------------------------------

	/* */
}