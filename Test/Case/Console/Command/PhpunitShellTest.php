<?php

App::uses('PhpunitShell', 'Phpunit.Console/Command');

class PhpunitShellTest extends CakeTestCase {

	public $PhpunitShell;

	public function setUp() {
		parent::setUp();
		$this->PhpunitShell = new TestPhpunitShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->PhpunitShell));
		$this->assertIsA($this->PhpunitShell, 'PhpunitShell');
	}

	public function testInfo() {
		ob_start();
		$this->PhpunitShell->info();
		$res = ob_get_clean();
		debug($res); ob_flush();
		$this->assertTrue(!empty($res));
		$this->assertTextContains('# PHPUnit 3.7.', $res);
	}

	public function testVersions() {
		ob_start();
		$this->PhpunitShell->versions();
		$res = ob_get_clean();
		debug($res); ob_flush();
		$this->assertTrue(!empty($res));
		$this->assertTextContains('3.7 : v3.7.', $res);
	}

	public function testPackages() {
		ob_start();
		$this->PhpunitShell->packages();
		$res = ob_get_clean();
		debug($res); ob_flush();
		$this->assertTextContains('Please provide a version like so', $res);

		$this->PhpunitShell->args[0] = '3.7';
		$this->PhpunitShell->packages();
		$res = ob_get_clean();
		debug($res); ob_flush();
		$this->assertTextContains('PHPUnit 3.7.', $res);
	}

}

/**
 * Helper to directly display console output
 */
class TestPhpunitShell extends PhpunitShell {

	public function out($message = null, $newlines = 1, $level = 1) {
		echo $message . PHP_EOL;
	}

	/*
	public function error($message = null, $newlines = 1) {
		echo $message.PHP_EOL;
	}

	public function err($message = null, $newlines = 1) {
		echo $message.PHP_EOL;
	}
	*/

}
