<?php
require_once SRC_ROOT . '/libbotobor.php';

class Botobor_Validator_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor_Validator::importMetaData
	 */
	public function test_importMetaData()
	{
		$m_importMetaData = new ReflectionMethod('Botobor_Validator', 'importMetaData');
		$m_importMetaData->setAccessible(true);
		$this->assertNull($m_importMetaData->invoke('Botobor_Validator', 'test'));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['test'] = 'test';
		$this->assertNull($m_importMetaData->invoke('Botobor_Validator', 'test'));

		$data = array(
			'aliases' => array(),
			'timestamp' => time(),
			'delay' => 10,
			'lifetime' => 30,
			'referer' => 'http://example.org/index.php'
		);
		Botobor::setSecret('secret');
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);

		$_POST['test'] = $meta;
		$this->assertEquals($data, $m_importMetaData->invoke('Botobor_Validator', 'test'));

		$_SERVER['REQUEST_METHOD'] = 'get';
		$_GET['test'] = $meta;
		$this->assertEquals($data, $m_importMetaData->invoke('Botobor_Validator', 'test'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Validator::isHuman
	 */
	public function test_isHuman()
	{
		Botobor::setSecret('secret');
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$this->assertFalse(Botobor_Validator::isHuman());

		$data = array(
			'aliases' => array(),
			'timestamp' => time(),
			'delay' => 10,
			'lifetime' => 30,
			'referer' => 'http://example.org/index.php'
		);
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);
		$_POST['botobor_meta_data'] = $meta;
		$this->assertFalse(Botobor_Validator::isHuman());


		$data = array(
			'aliases' => array(),
			'timestamp' => time() - 35 * 60,
			'delay' => 10,
			'lifetime' => 30,
			'referer' => 'http://example.org/index.php'
		);
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);
		$_POST['botobor_meta_data'] = $meta;
		$this->assertFalse(Botobor_Validator::isHuman());


		$data = array(
			'aliases' => array(),
			'timestamp' => time() - 15,
			'delay' => 10,
			'lifetime' => 30,
			'referer' => 'http://example.org/index.php'
		);
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);
		$_POST['botobor_meta_data'] = $meta;
		$this->assertFalse(Botobor_Validator::isHuman());

		$_SERVER['HTTP_REFERER'] = 'http://example.org/index.php';
		$this->assertTrue(Botobor_Validator::isHuman());


		$data = array(
			'aliases' => array('a' => 'name'),
			'timestamp' => time() - 15,
			'delay' => 10,
			'lifetime' => 30
		);
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);
		$_POST['botobor_meta_data'] = $meta;
		$_POST['a'] = 'value';
		$this->assertTrue(Botobor_Validator::isHuman());


		$_SERVER['REQUEST_METHOD'] = 'GET';
		$data = array(
					'aliases' => array('a' => 'name'),
					'timestamp' => time() - 15,
					'delay' => 10,
					'lifetime' => 30
		);
		$meta = serialize($data);
		if (function_exists('gzdeflate'))
		{
			$meta = gzdeflate($meta);
		}
		$meta = base64_encode($meta);
		$meta = Botobor::sign($meta);
		$_GET['botobor_meta_data'] = $meta;
		$_GET['a'] = 'value';
		$_GET['name'] = 'value';
		$this->assertFalse(Botobor_Validator::isHuman());
	}
	//-----------------------------------------------------------------------------

	/* */
}