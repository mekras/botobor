<?php
require_once SRC_ROOT . '/libbotobor.php';

class Botobor_Form_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor_Form::__construct
	 */
	public function test_construct()
	{
		$form = $this->getMockBuilder('Botobor_Form')->setMethods(array('setOptions', 'getCode'))->
			disableOriginalConstructor()->getMock();
		$options = array('delay' => 123);
		$form->expects($this->once())->method('setOptions')->with($options);
		$form->__construct('[form]', $options);
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setHoneypot
	 */
	public function test_setHoneypot()
	{
		$form = $this->getMockBuilder('Botobor_Form')->setMethods(array('getCode'))->
			disableOriginalConstructor()->getMock();
		$form->setHoneypot('foo');
		$p_honeypots = new ReflectionProperty('Botobor_Form', 'honeypots');
		$p_honeypots->setAccessible(true);
		$this->assertEquals(array('foo'), $p_honeypots->getValue($form));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setOptions
	 */
	public function test_setOptions()
	{
		$form = $this->getMockBuilder('Botobor_Form')->setMethods(array('getCode'))->
			disableOriginalConstructor()->getMock();
		$m_setOptions = new ReflectionMethod('Botobor_Form', 'setOptions');
		$m_setOptions->setAccessible(true);
		$m_setOptions->invoke($form, array('delay' => 123, 'lifetime' => 456));
		$p_delay = new ReflectionProperty('Botobor_Form', 'delay');
		$p_delay->setAccessible(true);
		$p_lifetime = new ReflectionProperty('Botobor_Form', 'lifetime');
		$p_lifetime->setAccessible(true);
		$this->assertEquals(123, $p_delay->getValue($form));
		$this->assertEquals(456, $p_lifetime->getValue($form));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::prepareMetaData
	 */
	public function test_prepareMetaData()
	{
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_SERVER['HTTP_HOST'] = 'example.org';
		$form = $this->getMock('Botobor_Form', array('getCode'), array('[form]'));
		$m_prepareMetaData = new ReflectionMethod('Botobor_Form', 'prepareMetaData');
		$m_prepareMetaData->setAccessible(true);
		$m_prepareMetaData->invoke($form);
		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$meta = $p_meta->getValue($form);
		$this->assertArrayHasKey('timestamp', $meta);
		$this->assertArrayHasKey('delay', $meta);
		$this->assertArrayHasKey('lifetime', $meta);
		$this->assertArrayHasKey('referer', $meta);
		$this->assertEquals('http://example.org/index.php', $meta['referer']);
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::encodeMetaData
	 */
	public function test_encodeMetaData()
	{
		$form = $this->getMock('Botobor_Form', array('getCode'), array('[form]'));
		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$p_meta->setValue($form, array(
			'aliases' => array(),
			'timestamp' => 1312204585,
			'delay' => 10,
			'lifetime' => 30,
			'referer' => 'http://example.org/index.php'
		));
		Botobor::setSecret('secret');

		$m_encodeMetaData = new ReflectionMethod('Botobor_Form', 'encodeMetaData');
		$m_encodeMetaData->setAccessible(true);
		$data = $m_encodeMetaData->invoke($form);
		if (function_exists('gzdeflate'))
		{
			$this->assertEquals('HY1LDoMwDETv4gOQEBqVOqexhCmWQhvFWVAh7o7T5bz5EUY8FZ8IlIWUFRKhx/NSfCE02Vkb7QWS4DiNIfhHnGNSa8HCmX5/wxuYEbKs3BudTZ3ZauWVK1cwFSyytVbQOT5sM/PwrW8nn4WPoWz2cd0=b1c8c8f7103f63b257ba24edfd769630', $data);
		}
		else
		{
			$this->assertEquals('YTo1OntzOjc6ImFsaWFzZXMiO2E6MDp7fXM6OToidGltZXN0YW1wIjtpOjEzMTIyMDQ1ODU7czo1OiJkZWxheSI7aToxMDtzOjg6ImxpZmV0aW1lIjtpOjMwO3M6NzoicmVmZXJlciI7czoyODoiaHR0cDovL2V4YW1wbGUub3JnL2luZGV4LnBocCI7fQ==20d0b8875c44c340d7f597d481ee2aeb', $data);
		}
	}
	//-----------------------------------------------------------------------------

	/* */
}