<?php

#needs our own bugfixed Folder class (can merge recursivly as one might expect from a copy command)
App::uses('FolderExt', 'Phpunit.Utility');

App::uses('File', 'Utility');
App::uses('HttpSocket', 'Network/Http');

if (!defined('WINDOWS')) {
	if (DS == '\\' || substr(PHP_OS, 0, 3) == 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

/**
 * Install PHPUnit for the CakePHP 2.x Test-Framework
 * PHPUnit Plugin
 * Place it in your app/Plugin/ directory and open a shell inside your app folder
 * 
 * - supports windows, linux, mac
 * - select Vendor path dynamically
 * - select PHPUnit version dynamically
 * - get package info and a list of supported versions
 * 
 * TODO'S:
 * - params (windows, override, ...)
 * - tests on more OS's
 * - update functionality for PHPUnit
 * 
 * @original Stef van den Ham
 * @modified Mark Scherer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @CakePHP 2.0
 *
 * @changelog:
 * 2011-11-29 Stef van den Ham
 * - Upgraded to 3.6.10
 * 2011-11-29 Mark Scherer
 * - Added Windows compatibility
 * - Added Version select dynamically
 * - Added Path select dynamically
 */

class PhpunitShell extends AppShell {

	const PHPUNIT_VERSION = '3.6.10';

	public function main() {
		$this->out(__('Hi There. To install PHPUnit %s, run `Phpunit.Phpunit install [version]`', self::PHPUNIT_VERSION));
		$this->out('Possible versions:');
		$this->versions();
	}

	/**
	 * list all supported versions
	 * 
	 * 2011-11-29 ms
	 */		
	public function versions() {
		$c = 0;
		foreach ($this->versions as $key => $version) {
			$default = '';
			if ($c === 0) {
				$default = "\t".'['.__('default').']';
			}
			$c++;
			$this->out($key.' : v'.$version.$default);
		}
	}

	/**
	 * list all packages to a specific version
	 * you can pass the version yuo want to see (3.5, 3.6, ...) as first param:
	 * "... packages 3.5" for example
	 * 
	 * 2011-11-29 ms
	 */	
	public function packages() {
		if (empty($this->args[0])) {
			$this->out(__('Please provide a version like so:'));
			$this->out('`Phpunit.Phpunit packages 3.x`');
			$this->out();
			$this->versions();
			exit();
		}
		$packages = $this->_getDependencies($this->args[0]);
		
		foreach ($packages as $package) {
			$this->out($package['name'].' ['.$package['folder'].']');
		}
	}

	/**
	 * main installer
	 * you can pass the version yuo want to install (3.5, 3.6, ...) as first param:
	 * "... install 3.5" for example
	 * 
	 * 2011-11-29 ms
	 */
	public function install() {
		$v = $this->_getVersion(isset($this->args[0]) ? $this->args[0] : null);
		
		$this->out(__('Installing PHPUnit %s ...', $v));
		
		$Http = new HttpSocket();
		
		$path = $this->_getPath();
		$tmpPath = $path . '_TMP' . DS;
		
		# Create the _TMP folder to put the files
		$Folder = new FolderExt($tmpPath, true);
		$Folder->create($tmpPath . '_target');
		
		# Download all files to a temporary location
		$files = $this->_getDependencies($v);
		
		foreach($files as $file) {
			if (!file_exists($tmpPath . $file['file']) || !empty($this->params['override'])) {
				# Download the file
				$this->out(__('Downloading <info>%s</info> .. ', $file['name']), 0);
				$data = $Http->get($file['url']);
				
				# Write it to the tmp folder
				$NewFile = new File($tmpPath . $file['file'], true);
				if (!$NewFile->write($data)) {
					$this->error(__('Writing failed'), __('Cannot create tmp files. Aborting.'));
				}
				$NewFile->close();
				$this->out(__('done.'));
			}
			
			
			# Extract the file to the folders
			$this->out(__('Extracting ..'), 0);
			$this->_extract($tmpPath . $file['file']);
			$this->out('done.');
			
			# Copy the contents to the target folder
			# Uses the bugfix from my ticket (otherwise you end up with missing folders!!!)
			# http://cakephp.lighthouseapp.com/projects/42648-cakephp/tickets/2314-folder-class-should-merge-by-default
			$this->out(__('Adding to Vendors ..'), 0);
			$Folder->move(array('to'=>$tmpPath . '_target'.DS.$file['folder'].DS, 'from'=>$tmpPath.(str_replace('.tgz', '', $file['file'])).DS.$file['folder'].DS));
			$this->out('done.');
			
			$this->hr();
		}

		$this->out(__('Cleaning up install files.'));
		$this->hr();

		$Folder->move(array('to'=>$path, 'from'=>$tmpPath.'_target'.DS, 'merge'=>true));
		
		# Clean up
		$Folder->delete($path . '_TMP'.DS);

		$this->out();
		$this->out(__('<info>PHPUnit %s</info> <warning>has been successfully installed to your Vendor folder!</warning>', $this->versions[$v]));
	}

	protected function _getVersion($v, $detailed = false) {
		if (strlen($v) > 3) {
			$v = substr($v, 0, 3);
		}
		if (empty($v) || !array_key_exists($v, $this->versions)) {
			$versions = array_keys($this->versions);
			$v = array_shift($versions);
		}
		if ($detailed) {
			return $this->versions[$v];
		}
		return $v;
	}
	
	protected function _extract($file) {
		chdir(dirname($file));
	
		if (WINDOWS && empty($this->params['os']) || !empty($this->params['os']) && $this->params['os'] == 'w') {
			$exePath = App::pluginPath('Phpunit').'Vendor'.DS.'exe'.DS;
			exec($exePath.'gzip -dr '.$file);
			$tarFile = str_replace('.tgz', '.tar', $file);
			exec($exePath.'tar -xvf '.$tarFile);
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

	/**
	 * get specific version or the latest if not specified
	 */
	protected function _getDependencies($v = null) {
		$v = $this->_getVersion($v);
		$files = $this->files[$v];
		foreach ($files as $key => $value) {
			if (!isset($value['file'])) {
				$files[$key]['file'] = basename($value['url']);
			}
			if (!isset($value['name'])) {
				$files[$key]['name'] = str_replace(array('_', '-'), ' ', basename($files[$key]['file'], '.tgz'));
			}
		}
		return $files; 
	}
	
	protected $versions = array(
		//'3.7' => '3.7.0',
		'3.6' => '3.6.10',
		'3.5' => '3.5.15',
	);
	
	protected $files = array(
			'3.6' => array(
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit-3.6.10.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/File_Iterator-1.3.1.tgz',
					'folder' => 'File'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/Text_Template-1.1.1.tgz',
					'folder' => 'Text'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_CodeCoverage-1.1.1.tgz',
					'folder' => 'PHP'
				),
				array(
					'name' => 'PHP Timer 1.0.2',
					'file' => 'PHP_Timer-1.0.2.tgz',
					'url' => 'http://pear.phpunit.de/get/PHP_Timer-1.0.2.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_MockObject-1.1.1.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_TokenStream-1.1.2.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/DbUnit-1.1.2.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Story-1.0.0.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Selenium-1.1.3.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_TicketListener_GitHub-1.0.0.tgz',
					'folder' => 'PHPUnit'
				),		
			),
			'3.5' => array(
				array(
					'name' => 'PHPUnit 3.5.15',
					'file' => 'PHPUnit-3.5.15.tgz',
					'url' => 'http://pear.phpunit.de/get/PHPUnit-3.5.15.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'name' => 'DB Unit 1.0',
					'file' => 'DbUnit-1.0.0.tgz',
					'url' => 'http://pear.phpunit.de/get/DbUnit-1.0.0.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'name' => 'File Iterator 1.2.3',
					'file' => 'File_Iterator-1.2.3.tgz',
					'url' => 'http://pear.phpunit.de/get/File_Iterator-1.2.3.tgz',
					'folder' => 'File'
				),
				array(
					'name' => 'Text Template 1.0',
					'file' => 'Text_Template-1.0.0.tgz',
					'url' => 'http://pear.phpunit.de/get/Text_Template-1.0.0.tgz',
					'folder' => 'Text'
				),
				array(
					'name' => 'PHP Code Coverage 1.0.2',
					'file' => 'PHP_CodeCoverage-1.0.2.tgz',
					'url' => 'http://pear.phpunit.de/get/PHP_CodeCoverage-1.0.2.tgz',
					'folder' => 'PHP'
				),
				array(
					'name' => 'PHP Timer 1.0',
					'file' => 'PHP_Timer-1.0.0.tgz',
					'url' => 'http://pear.phpunit.de/get/PHP_Timer-1.0.0.tgz',
					'folder' => 'PHP'
				),
				array(
					'name' => 'PHPUnit MockObject 1.0.3',
					'file' => 'PHPUnit_MockObject-1.0.3.tgz',
					'url' => 'http://pear.phpunit.de/get/PHPUnit_MockObject-1.0.3.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'name' => 'PHPUnit Selenium 1.0.1',
					'file' => 'PHPUnit_Selenium-1.0.1.tgz',
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Selenium-1.0.1.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'name' => 'PHPUnit TokenStream 1.1.0',
					'file' => 'PHP_TokenStream-1.1.0.tgz',
					'url' => 'http://pear.phpunit.de/get/PHP_TokenStream-1.1.0.tgz',
					'folder' => 'PHP'
				),
				array(
					'name' => 'YAML 1.0.2',
					'file' => 'YAML-1.0.2.tgz',
					'url' => 'http://pear.symfony-project.com/get/YAML-1.0.2.tgz',
					'folder' => 'lib'
				),
				array(
					'name' => 'XML RPC2 1.1.1',
					'file' => 'XML_RPC2-1.1.1.tgz',
					'url' => 'http://download.pear.php.net/package/XML_RPC2-1.1.1.tgz',
					'folder' => 'XML'
				),
			),
		);

}