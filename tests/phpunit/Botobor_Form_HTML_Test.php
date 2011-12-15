<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once SRC_ROOT . '/botobor.php';

class Botobor_Form_HTML_Test extends PHPUnit_Framework_TestCase
{
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
			setMethods(array('createInput', 'createHoneypots'))->
			setConstructorArgs(array('<form></form>'))->getMock();
		$form->expects($this->once())->method('createHoneypots')->
			will($this->returnValue('<form><honeypots></form>'));
		$form->expects($this->once())->method('createInput')->
			with('hidden', 'botobor_meta_data', '[metadata]')->will($this->returnValue('<meta>'));

		$meta = $this->getMock('Botobor_MetaData', array('encode'));
		$meta->expects($this->once())->method('encode')->will($this->returnValue('[metadata]'));

		$p_meta = new ReflectionProperty('Botobor_Form', 'meta');
		$p_meta->setAccessible(true);
		$p_meta->setValue($form, $meta);

		$this->assertEquals('<form><div style="display: none;"><meta></div><honeypots></form>',
			$form->getCode());
	}
	//-----------------------------------------------------------------------------

	/* */
}