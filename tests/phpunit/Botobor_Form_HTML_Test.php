<?php
require_once SRC_ROOT . '/libbotobor.php';

class Botobor_Form_HTML_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Botobor_Form_HTML::__construct
	 */
	public function test_construct()
	{
		$options = array('delay' => 123);
		$form = new Botobor_Form_HTML('<form>', $options);
		$p_delay = new ReflectionProperty('Botobor_Form_HTML', 'delay');
		$p_delay->setAccessible(true);
		$this->assertEquals(123, $p_delay->getValue($form));
		$p_html = new ReflectionProperty('Botobor_Form_HTML', 'html');
		$p_html->setAccessible(true);
		$this->assertEquals('<form>', $p_html->getValue($form));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form_HTML::createInput
	 */
	public function test_createInput()
	{
		$form = new Botobor_Form_HTML('<form>');
		$m_createInput = new ReflectionMethod('Botobor_Form_HTML', 'createInput');
		$m_createInput->setAccessible(true);
		$this->assertEquals('<input type="a" name="b">', $m_createInput->invoke($form, 'a', 'b'));
		$this->assertEquals('<input type="a" name="b" value="c">',
			$m_createInput->invoke($form, 'a', 'b', 'c'));
		$this->assertEquals('<input type="a" name="b" value="c" d>',
			$m_createInput->invoke($form, 'a', 'b', 'c', 'd'));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form_HTML::createHoneypots
	 */
	public function test_createHoneypots()
	{
		$form = new Botobor_Form_HTML('<form>');
		$m_createHoneypots = new ReflectionMethod('Botobor_Form_HTML', 'createHoneypots');
		$m_createHoneypots->setAccessible(true);
		$this->assertRegExp(
			'~<form><div style="display: none;"><input type="text" name="a"></div><input name=".+"></form>~',
			$m_createHoneypots->invoke($form, '<form><input name="a"></form>', array('a', 'b')));
	}
	//-----------------------------------------------------------------------------

	/**
	 * @covers Botobor_Form_HTML::getCode
	 */
	public function test_getCode()
	{
		$form = $this->getMockBuilder('Botobor_Form_HTML')->
			setMethods(array('prepareMetaData', 'createHoneypots', 'createInput', 'encodeMetaData'))->
			setConstructorArgs(array('<form></form>'))->getMock();
		$form->expects($this->once())->method('prepareMetaData');
		$form->expects($this->once())->method('createHoneypots')->with('<form></form>', array())->
			will($this->returnValue('<form><honeypots></form>'));
		$form->expects($this->once())->method('encodeMetaData')->will($this->returnValue('[metadata]'));
		$form->expects($this->once())->method('createInput')->
			with('hidden', 'botobor_meta_data', '[metadata]')->will($this->returnValue('<meta>'));
		$this->assertEquals('<form><div style="display: none;"><meta></div><honeypots></form>',
			$form->getCode());
	}
	//-----------------------------------------------------------------------------

	/* */
}