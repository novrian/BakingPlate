<?php
/**
* BakingPlate Class
*/
class PlateShell extends Shell {

	var $tasks = array('Project', 'DbConfig');
	
	/**
	 * _load() populated list of submodules
	 *
	 * @var array
	 */
	var $submodules = array();
	
	/**
	 * _load() populated list of submodules groups
	 *
	 * @var array
	 */
	var $groups = array();

	/**
	 * Loads the list of submodules from config
	 *
	 * @return array submodules
	 * @author Dean Sofer
	 */
	function _load() {
		if (Configure::read('BakingPlate'))
			return;
		Configure::load('BakingPlate.submodules');
		
		$this->submodules = Configure::read('BakingPlate');
	}

	/**
	 * Overridden method so the heading message stops getting spit out
	 *
	 * @return void
	 * @author Dean Sofer
	 */
	function _welcome() {}

	/**
	 * Shows a list of available commands
	 */
	function main() {
		$this->out("\nAvailable Commands:\n");
		$this->out('bake	- Generates a new app using bakeplate');
		$this->out('browse	- List available submodules');
		$this->out('add <#|submodule_name>	- Add a specific submodule');
		$this->out('all	- Add all available submodules');
		$this->out("\nAll commands take a -group param to narrow the list of submodules to a specific group");
	}

	function bake() {
		if (!isset($this->params['group'])) {
			$this->params['group'] = 'core';
		}
		$this->params['skel'] = $this->_pluginPath('BakingPlate') . 'vendors' . DS . 'shells' . DS . 'skel ' . implode(' ', $this->args);
		if (!is_dir($this->DbConfig->path)) {
			if ($this->Project->execute()) {
				$this->DbConfig->path = $this->params['working'] . DS . 'config' . DS;
			} else {
				return false;
			}
		}

		if (!config('database')) {
			$this->out(__("Your database configuration was not found. Take a moment to create one.", true));
			$this->args = null;
			$this->DbConfig->execute();
		}
		$this->out(passthru('git init ' . $this->params['app']));
		chdir($this->params['app']);
		$this->all();
		
	}

	/**
	 * Add a specific submodule/plugin
	 *
	 * @return void
	 * @author Dean Sofer
	 */
	function add() {
		$this->_load();
		$keys = array_keys($this->submodules);
		if (!isset($this->args[0])) {
			$this->browse();
			$this->out($this->nl());
			$plugin = $this->in('Specify a # or submodule_name');
		} else {
			$plugin = $this->args[0];
		}
		if (is_numeric($plugin)) {
			$path = $keys[$plugin-1];
		} else {
			$path = Inflector::underscore($plugin);
		}
		$this->_addSubmodule($path);
	}

	/**
	 * Render a list of submodules
	 */
	function browse() {
		$this->_load();
		$this->out("\nAvailable Plugins:\n");
		$i = 0;
		foreach ($this->submodules as $path => $url) {
			$i++;
			$this->out($i . ') ' . Inflector::humanize($path));
		}
	}

	/**
	 * Add all submodules
	 */
	function all() {
		if (isset($this->args[0])) {
			$this->params['group'] = $this->args[0];
		}
		if (!isset($this->params['group'])) {
			$this->params['group'] = 'all';
		}
		$this->_load();
		$this->out("\nAdding {$this->params['group']} git submodules...\n");
		foreach ($this->submodules as $group => $list) {
			if (!empty($this->params['group']) && ($this->params['group'] != $group || $this->params['group'] != 'all'))
				continue;
			foreach (array_keys($list) as $path) {
				$this->_addSubmodule($path);
			}
		}
	}
	
	/**
	 * Adds a submodule via git
	 *
	 * @param string $path 
	 * @param string $url 
	 * @return void
	 * @author Dean Sofer
	 */
	private function _addSubmodule($path) {
		foreach ($this->submodules as $group => $list) {
			if (isset($list[$path])) {
				$folder = $group;
				$url = $list[$path];
				break;
			}
		}
		if (!isset($url)) {
			$this->out('Submodule not found');
			return false;
		}
		$folder = (isset($this->submodules['vendors'][$path])) ? 'vendors': 'plugins';
		$this->out($this->nl().'=======================================================');
		$this->out('Adding ' . Inflector::humanize($path));
		$this->out($this->hr());
		exec("git submodule add {$url} {$folder}/{$path}");
	}
}