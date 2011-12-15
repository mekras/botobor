<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/botobor.php';

class Botobor_Form_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor_Form::__construct
	 * @covers Botobor_Form::setDelay
	 * @covers Botobor_Form::setLifetime
	 */
	public function test_construct()
	{
		$form = $this->getMockBuilder('Botobor_Form')->
			setMethods(array('setDelay', 'setLifetime', 'getCode'))->
			disableOriginalConstructor()->getMock();
		$options = array('delay' => 123, 'lifetime' => 456);
		$form->expects($this->once())->method('setDelay')->with(123);
		$form->expects($this->once())->method('setLifetime')->with(456);
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_SERVER['HTTP_HOST'] = 'example.org';
		Botobor::setDefault('honeypots', array('name', 'mail'));
		$form->__construct('[form]', $options);

		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$this->assertEquals('http://example.org/index.php', $p_meta->getValue($form)->referer);

		$p_honeypots = new ReflectionProperty('Botobor_Form', 'honeypots');
		$p_honeypots->setAccessible(true);
		$this->assertEquals(array('name', 'mail'), $p_honeypots->getValue($form));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setCheck
	 */
	public function test_setCheck()
	{
		$form = $this->getMockBuilder('Botobor_Form')->setConstructorArgs(array('<foo>'))->
			setMethods(array('getCode'))->getMock();
		$form->setCheck('referer', false);

		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$this->assertFalse($p_meta->getValue($form)->checks['referer']);
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setCheck
	 * @expectedException InvalidArgumentException
	 */
	public function test_setCheck_arg_1()
	{
		$form = $this->getMockBuilder('Botobor_Form')->disableOriginalConstructor()->
			setMethods(array('getCode'))->getMock();
		$form->setCheck(array(), null);
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setCheck
	 * @expectedException InvalidArgumentException
	 */
	public function test_setCheck_arg_2()
	{
		$form = $this->getMockBuilder('Botobor_Form')->disableOriginalConstructor()->
			setMethods(array('getCode'))->getMock();
		$form->setCheck('foo', null);
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
	 * @covers Botobor_Form::setDelay
	 */
	public function test_setDelay()
	{
		$form = $this->getMockForAbstractClass('Botobor_Form', array('[form]'));
		$form->setDelay(123);
		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$this->assertEquals(123, $p_meta->getValue($form)->delay);
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form::setLifetime
	 */
	public function test_setLifetime()
	{
		$form = $this->getMockForAbstractClass('Botobor_Form', array('[form]'));
		$form->setLifetime(123);
		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$this->assertEquals(123, $p_meta->getValue($form)->lifetime);
	}
	//-----------------------------------------------------------------------------

	/* */
}