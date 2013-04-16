<?php
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('Xml', 'Utility');
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
 * - does NOT require pear package to be installed
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
 * - Upgraded to 3.6.11
 * 2011-11-29 Mark Scherer
 * - Added Windows compatibility
 * - Added Version select dynamically
 * - Added Path select dynamically
 * 2012-07-21
 * - non-pear-fallback for info() and minor improvements, test case added
 * 2012-09-21 ms
 * - Upgraded to 3.7.9 and Cake2.3
 * 2013-04-16 ms
 * - Upgraded to 3.7.19
 */
class PhpunitShell extends AppShell {

	public function main() {
		$this->out(__('Hi There. To install PHPUnit, run `Phpunit.Phpunit install [version]`'));
		$this->out('Possible versions:');
		$this->versions();

		$this->out();
		$this->out(__('Additional info via'));
		$this->out(__('- packages [version] (pear packages including version numbers)'));
		$this->out(__('- info [-v] (comparison to current versions on the pear network)'));
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
			return;
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
		$Folder = new Folder($tmpPath, true);
		$Folder->create($tmpPath . '_target');

		# Download all files to a temporary location
		$files = $this->_getDependencies($v);

		foreach ($files as $file) {
			if (!file_exists($tmpPath . $file['file']) || !empty($this->params['override'])) {
				# Download the file
				$this->out(__('Downloading <info>%s</info> .. ', $file['name']), 0);

				$data = $Http->get($file['url']);
				if (!$data->body) {
					throw new RuntimeException('Could not download file. Aborting!');
				}
				$Http->reset();
				# Write it to the tmp folder
				$NewFile = new File($tmpPath . $file['file'], true);
				if (!$NewFile->write($data->body)) {
					$this->error(__('Writing failed'), __('Cannot create tmp files. Aborting.'));
				}
				$NewFile->close();

				$this->out(__('Download finished.'));
			}

			# Extract the file to the folders
			$this->out(__('Extracting ..'), 0);
			$this->_extract($tmpPath . $file['file']);

			$this->out('Extracting done.');

			# Copy the contents to the target folder
			$this->out(__('Adding to Vendors ..'), 0);
			if (!$Folder->move(array('to'=>$tmpPath . '_target'.DS.$file['folder'].DS, 'from'=>$tmpPath.(str_replace('.tgz', '', $file['file'])).DS.$file['folder'].DS))) {
				$this->err($Folder->errors());
			}
			$this->out('Adding done.');

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

	/**
	 * get a list of the current versions of all used pear packages via phpunit channel
	 * needs pear package to be installed
	 * you can easily check that typing `pear` in CLI.
	 *
	 * It will also display if there is an update to one of our head packages.
	 *
	 * You can manually update those version numbers in the $files array below to get those new versions.
	 * Or you can issue a ticket or a pull request for this plugin at github for us to update it.
	 *
	 * possible params:
	 * -v: verbose output (display the description for each package, as well)
	 *
	 * 2012-02-26 ms
	 */
	public function info() {
		$officialList = $this->_pearInfo();

		# our list of packages
		$packages = $this->_getDependencies();

		# lets match them
		$result = array();

		foreach ($packages as $package) {
			list($identifier, $version) = explode('-', $package['file'], 2);
			$version = substr($version, 0, strrpos($version, '.'));

			if (!isset($officialList[$identifier])) {
				$this->error(__('Missing package').': '.$identifier);
			}
			$pearPackage = $officialList[$identifier];
			unset($officialList[$identifier]);

			$package['description'] = $pearPackage[2];
			$package['head'] = $pearPackage[1];
			$package['current'] = $version;

			$result[] = $package;
		}

		$this->out('');
		foreach ($result as $row) {
			$this->out('# '.$row['name']. '');
			if ($row['current'] == $row['head']) {
				$this->out("\t" . 'OK (v'.$row['current'].')');
			} else {
				$this->out("\t" . __('UPDATE to v%s available (currently v%s)', $row['head'], $row['current']));
			}
			if (!empty($this->params['verbose'])) {
				$this->out($this->wrapText($row['description'], array('indent'=>"\t")));
			}
		}

		$this->hr();
		$this->out(__('Unused pear packages') . ':');
		foreach ($officialList as $key => $val) {
			$this->out('# '.$key.' (v'.$val[1].')');
			if (!empty($this->params['verbose'])) {
				$this->out($this->wrapText($val[2], array('indent'=>"\t")));
			}
		}

	}

	protected function _pearInfo() {
		if (WINDOWS) {
			$officialList = $this->_pearInfoXml();
		} else {
			try {
				$officialList = $this->_pearInfoConsole();
			} catch (Exception $e) {
				$officialList = $this->_pearInfoXml();
			}
		}
		$officialYamlList = $this->_pearInfoXml('http://pear.symfony.com/feed.xml');
		$officialList['Yaml'] = $officialYamlList['Yaml'];
		return $officialList;
	}

	/**
	 * @return array
	 */
	protected function _pearInfoConsole() {
		exec('pear list-channels', $output, $ret);
		if ($ret !== 0) {
			throw new CakeException(__('Pear package not available. Please install using `apt-get install php-pear`'));
		}
		/*
		$phpunitChannel = false;
		foreach ($output as $row) {
			if (strpos($row, 'pear.phpunit.de') !== false) {
				$phpunitChannel = true;
			}
		}
		if (!$phpunitChannel) {
			exec('pear list-channels', $output, $ret);
			if ($ret !== 0) {
				throw new CakeException(__('Pear channel `%s` cannot be discovered.', 'pear.phpunit.de'));
			}
			$phpunitChannel = true;
		}
		*/

		exec('pear list-all -c phpunit', $output, $ret);

		# pear list of current packages
		$res = array();
		foreach ($output as $row) {
			if (!isset($packages) && strpos($row, 'PACKAGE') === 0) {
				$packages = true;
				continue;
			}
			if (!isset($packages)) {
				continue;
			}
			preg_match('/^(.*)\b\s+\b([0-9\.]*)\b\s+\b(.*)$/', $row, $tmp);
			array_shift($tmp);
			foreach ($tmp as $key => $val) {
				$tmp[$key] = trim($val);
				$name = substr($tmp[0], strrpos($tmp[0], '/')+1);
			}
			$res[$name] = $tmp;

		}
		return $res;
	}

	/**
	 * @return array
	 */
	protected function _pearInfoXml($feed = null) {
		if ($feed === null) {
			$feed = 'http://pear.phpunit.de/feed.xml';
		}
		$Xml = Xml::build($feed);
		$packages = Xml::toArray($Xml);
		if (empty($packages['feed']['entry'])) {
			throw new CakeException('Could not read xml feed');
		}
		$packages = $packages['feed']['entry'];
		$res = array();
		foreach ($packages as $package) {
			$p = array();
			$package['title'] = str_replace(array('(', ')'), '', $package['title']);
			preg_match('/^(.+)\b\s+\b([0-9\.]+)\b\s+\b(.+)$/', $package['title'], $tmp);
			if (empty($tmp)) {
				continue;
			}
			$name = $tmp[1];
			$version = $tmp[2];
			if (isset($res[$name])) {
				continue;
			}
			$p = array(
				0 => 'phpunit/'.$name,
				1 => $version,
				2 => $package['content']
			);
			$res[$name] = $p;
		}
		return $res;
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
			$exePath = App::pluginPath('Phpunit') . 'Vendor' . DS . 'exe' . DS;
			exec($exePath.'gzip -dr '.$file);
			$tarFile = str_replace('.tgz', '.tar', $file);
			exec($exePath . 'tar -xvf ' . $tarFile);
		} else {
			exec('tar -xzf ' . $file);
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

		$res = $this->in('Select VENDOR path to install into', array_merge(array('q'), array_keys($list)), 'q');
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
		'3.7' => '3.7.19',
		'3.6' => '3.6.11',
		'3.5' => '3.5.15',
	);

	protected $files = array(
			'3.7' => array(
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit-3.7.19.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/File_Iterator-1.3.3.tgz',
					'folder' => 'File'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/Text_Template-1.1.4.tgz',
					'folder' => 'Text'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_CodeCoverage-1.2.9.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_Timer-1.0.4.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_MockObject-1.2.3.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_TokenStream-1.1.5.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_Invoker-1.1.2.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/DbUnit-1.2.3.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Story-1.0.2.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Selenium-1.2.12.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_TicketListener_GitHub-1.0.0.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.symfony.com/get/Yaml-2.2.1.tgz',
					'folder' => 'Symfony'
				),
			),
			'3.6' => array(
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit-3.6.11.tgz',
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
					'url' => 'http://pear.phpunit.de/get/PHP_CodeCoverage-1.1.3.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_Timer-1.0.2.tgz',
					'folder' => 'PHP'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHPUnit_MockObject-1.1.1.tgz',
					'folder' => 'PHPUnit'
				),
				array(
					'url' => 'http://pear.phpunit.de/get/PHP_TokenStream-1.1.3.tgz',
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
					'url' => 'http://pear.phpunit.de/get/PHPUnit_Selenium-1.2.7.tgz',
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
