<?php
	/**
	 * This is the default Frawst application bootstrap. It initializes DataPane,
	 * Corelativ, and SimpleCache with the configuration files.
	 * 
	 * To overwrite this bootstrap (if, for example, you want to use another ORM)
	 * simply place a new bootstrap.php in your application root. If you simply
	 * wish to extend this bootstrap, simply import the core bootstrap within
	 * your custom bootstrap:
	 * 
	 *   \Frawst\Loader::import('Frawst\bootstrap', 'core');
	 */

	namespace Frawst;
		
	if(($dpCfg = Config::read('DataPane')) && $dpCfg['enable']) {
		\DataPane\Data::init($dpCfg);
	}
	if(($corCfg = Config::read('Corelativ')) && $corCfg['enable']) {
		\Corelativ\Mapper::init($corCfg);
		Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'Model', 'Corelativ\Model', 'app');
	}
	if(($scCfg = Config::read('SimpleCache')) && $scCfg['enable']) {
		\SimpleCache\Cache::init($scCfg);
	}