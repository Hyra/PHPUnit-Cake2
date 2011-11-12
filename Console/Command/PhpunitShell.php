<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('HttpSocket', 'Network/Http');

if (!defined('WINDOWS')) {
	if (substr(PHP_OS, 0, 3) == 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * Install PHPUnit for the CakePHP 2.x Test-Framework
 * Phpunit Plugin
 * Place it in your app/Plugin/ dir and open a shell inside your app folder
 * 
 * - supports windows, linux, mac
 * - select vendor path dynamically
 * 
 * TODOS: 
 * - params (windows, override, ...)
 * - tests on more OS
 * - update functionality for PHPUnit
 * 
 * @original Stef van den Ham
 * @modified Mark Scherer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @cakephp 2.0
 */
class PhpunitShell extends AppShell {

	const PHPUNIT_VERSION = '3.5.15';

	public function main() {
		$this->out(__('Hai There. To install PHPUnit %s, run `Phpunit.Phpunit install`'), self::PHPUNIT_VERSION);
	}


	public function install() {
		$this->out(__('Installing PHPUnit ..'));
		
		$Http = new HttpSocket();
		
		$path = $this->_getPath();
		$tmpPath = $path . '_TMP' . DS;
		
		// Create the _TMP folder to put the files
		$Folder = new Folder($tmpPath, true);
		$Folder->create($tmpPath . '_target');
		
		// Download all files to a temporary location
		$files = $this->_getDependencies();
		
		foreach($files as $file) {
			if (!file_exists($tmpPath . $file['file']) || !empty($this->params['override'])) {
				// Download the file
				$this->out(__('Downloading <info>%s</info> .. ', $file['name']), 0);
				$data = $Http->get($file['url']);
				
				// Write it to the tmp folder
				$NewFile = new File($tmpPath . $file['file'], true);
				if (!$NewFile->write($data)) {
					$this->error(__('Writing failed'), __('Cannot create tmp files. Aborting.'));
				}
				$this->out(__('done.'));
			}
			
			// Extract the file to the folders
			$this->out(__('Extracting ..'), 0);
			$this->_extract($tmpPath . $file['file']);
			$this->out('done.');
			
			// Copy the contents to the target folder
			$this->out(__('Adding to Vendors ..'), 0);
			$Folder->move(array('to'=>$tmpPath . '_target', 'from'=>$tmpPath.(str_replace('.tgz', '', $file['file'])).DS.$file['vendor_folder']));
			$this->out('done.');
			
			$this->hr();
		}
		
		$this->out(__('Cleaning up install files.'));
		
		$this->hr();
		
		$Folder->move(array('to'=>$path, 'from'=>$tmpPath.'_target'));
			
		// Clean up
		$Folder->delete($path . DS . '_TMP');

		$this->out();
		$this->out(__('<info>PHPUnit %s</info> <warning>has been successfully installed to your Vendor folder!</warning>'), self::PHPUNIT_VERSION);
		$this->out();
		
		$this->hr();
	}

	public function clear() {
		$path = $this->_getPath();
		$Folder = new Folder($path . '_TMP');
		$Folder->delete();
		$this->out('Tmp content deleted');
	}

	
	protected function _extract($file) {
		chdir(dirname($file));
	
		if (WINDOWS && empty($this->params['os']) || !empty($this->params['os']) && $this->params['os'] == 'w') {
			$exePath = App::pluginPath('Phpunit').'Vendor'.DS.'exe'.DS;
			//die($exePath);
			exec($exePath.'gzip -dr '.$file);
			//unlink($file);
			$file = str_replace('.tgz', '.tar', $file);
			exec($exePath.'tar -xvf '.$file);
		} else {
			exec('tar -xzf '.$file);
		}
	}
	
	
	protected function _getPath() {
		$paths = App::path('Vendor');
		$pathNames = $paths;
		
		$list = array(); 
		$i = 0;
		foreach ($paths as $path) {
			$i++;
			$list[$i] = $i . ". " . str_replace(ROOT, '', $path);
		}
		$this->out($list);

		$res = $this->in('Select VENDOR path to install into', am(array('q'), array_keys($list)), 'q');
		if ($res == 'q') {
			return $this->_stop();
		}
		
		$path = $paths[$res-1];
		return $path;
	}


	protected function _getDependencies() {
		return array(
			array(
				'name' => 'PHPUnit 3.5.15',
				'file' => 'PHPUnit-3.5.15.tgz',
				'url' => 'http://pear.phpunit.de/get/PHPUnit-3.5.15.tgz',
				'vendor_folder' => 'PHPUnit'
			),
			array(
				'name' => 'DB Unit 1.0',
				'file' => 'DbUnit-1.0.0.tgz',
				'url' => 'http://pear.phpunit.de/get/DbUnit-1.0.0.tgz',
				'vendor_folder' => 'PHPUnit'
			),
			array(
				'name' => 'File Iterator 1.2.3',
				'file' => 'File_Iterator-1.2.3.tgz',
				'url' => 'http://pear.phpunit.de/get/File_Iterator-1.2.3.tgz',
				'vendor_folder' => 'File'
			),
			array(
				'name' => 'Text Template 1.0',
				'file' => 'Text_Template-1.0.0.tgz',
				'url' => 'http://pear.phpunit.de/get/Text_Template-1.0.0.tgz',
				'vendor_folder' => 'Text'
			),
			array(
				'name' => 'PHP Code Coverage 1.0.2',
				'file' => 'PHP_CodeCoverage-1.0.2.tgz',
				'url' => 'http://pear.phpunit.de/get/PHP_CodeCoverage-1.0.2.tgz',
				'vendor_folder' => 'PHP'
			),
			array(
				'name' => 'PHP Timer 1.0',
				'file' => 'PHP_Timer-1.0.0.tgz',
				'url' => 'http://pear.phpunit.de/get/PHP_Timer-1.0.0.tgz',
				'vendor_folder' => 'PHP'
			),
			array(
				'name' => 'PHPUnit MockObject 1.0.3',
				'file' => 'PHPUnit_MockObject-1.0.3.tgz',
				'url' => 'http://pear.phpunit.de/get/PHPUnit_MockObject-1.0.3.tgz',
				'vendor_folder' => 'PHPUnit'
			),
			array(
				'name' => 'PHPUnit Selenium 1.0.1',
				'file' => 'PHPUnit_Selenium-1.0.1.tgz',
				'url' => 'http://pear.phpunit.de/get/PHPUnit_Selenium-1.0.1.tgz',
				'vendor_folder' => 'PHPUnit'
			),
			array(
				'name' => 'PHPUnit TokenStream 1.1.0',
				'file' => 'PHP_TokenStream-1.1.0.tgz',
				'url' => 'http://pear.phpunit.de/get/PHP_TokenStream-1.1.0.tgz',
				'vendor_folder' => 'PHP'
			),
			array(
				'name' => 'YAML 1.0.2',
				'file' => 'YAML-1.0.2.tgz',
				'url' => 'http://pear.symfony-project.com/get/YAML-1.0.2.tgz',
				'vendor_folder' => 'lib'
			),
			array(
				'name' => 'XML RPC2 1.1.1',
				'file' => 'XML_RPC2-1.1.1.tgz',
				'url' => 'http://download.pear.php.net/package/XML_RPC2-1.1.1.tgz',
				'vendor_folder' => 'XML'
			),
		);
	}

}