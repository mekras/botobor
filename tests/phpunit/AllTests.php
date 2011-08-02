<?php

require_once 'bootstrap.php';

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__);
PHP_CodeCoverage_Filter::getInstance()->addDirectoryToWhitelist('../../src');

require_once 'Botobor_Test.php';
require_once 'Botobor_MetaData_Test.php';
require_once 'Botobor_Form_Test.php';
require_once 'Botobor_Form_HTML_Test.php';
require_once 'Botobor_Keeper_Test.php';

class AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$suite->addTestSuite('Botobor_Test');
		$suite->addTestSuite('Botobor_MetaData_Test');
		$suite->addTestSuite('Botobor_Form_Test');
		$suite->addTestSuite('Botobor_Form_HTML_Test');
		$suite->addTestSuite('Botobor_Keeper_Test');

		return $suite;
	}
}
