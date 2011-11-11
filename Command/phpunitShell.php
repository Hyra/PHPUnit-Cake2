<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('HttpSocket', 'Network/Http');

class PhpunitShell extends AppShell {

	public function main() {
		$this->out('Hai There. To install PHPUnit, run `phpunit install`');
	}

	private function getDependencies() {
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
	public function install() {
		$this->out('Installing PHPUnit ..');
		
		$http = new HttpSocket();
		
		// Create the _TMP folder to put the files
		$folder = new Folder('./vendors/_TMP');
		$folder->create('./vendors/_TMP/_target');
		
		// Download all files to a temporary location
		$files = $this->getDependencies();
		
		$folder = new Folder('./vendors/_TMP');
		
		foreach($files as $file) {
			// Download the file
			$this->out('Downloading <info>' . $file['name'] . '</info> .. ', 0);
			$data = $http->get($file['url']);
			
			// Write it to the tmp folder
			$new_file = new File($folder->path . DS . $file['file']);
			$new_file->write($data);
			$this->out('done.');
			
			// Extract the file to the folders
			$this->out('Extracting .. ', 0);
			exec('cd '.$folder->path.' && tar -xzf '.$folder->path.'/'.$file['file']);
			$this->out('done.');
			
			// Copy the contents to the target folder
			$this->out('Adding to Vendors .. ', 0);
			exec('cp -R '.$folder->path.'/'.(str_replace('.tgz', '', $file['file'])).'/'.$file['vendor_folder']. ' ' .$folder->path.'/_target');
			$this->out('done.');
			
			$this->hr();
		}
		
		$this->out('Cleaning up install files.');
		
		$this->hr();
		
		// Copy all the result files to vendors
		$folder->cd('./vendors/_TMP/');
		exec('cp -R '.$folder->path.'/_target/* '.$folder->path.'/../../vendors/');
		
		// Clean up
		$folder->delete('./vendors/_TMP');

		$this->out();
		$this->out('<info>PHPUnit 3.5.15</info> <warning>has been succesfully installed to your Vendors folder!</warning>');
		$this->out();
		
		$this->hr();
	}

}