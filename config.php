<?php

	if(is_file('../main.inc.php'))$dir = '../';
	else  if(is_file('../../../main.inc.php'))$dir = '../../../';
	else $dir = '../../';

	include($dir."main.inc.php");

if(!defined('INC_FROM_CRON_SCRIPT') && !defined('INC_FROM_DOLIBARR')) {

	if(!class_exists('modShip2bill')) dol_include_once('/ship2bill/core/modules/modShip2bill.class.php');
	checkVersion($db, 'modShip2bill');

}

function checkVersion(&$DoliDb, $moduleName) {
	global $conf;
	if(class_exists($moduleName)) {

		$conf_name = 'ATM_MODULE_VERSION_'.strtoupper($moduleName);

		$mod = new $moduleName($DoliDb);

		if(!empty($mod->version)) {
			$version = $mod->version;
			if($conf->global->$conf_name != $version) {

				$message = "Your module wasn't updated (v".$conf->global->$conf_name." != ".$version."). Please reload it or launch the update of database script";

				accessforbidden($message);
			}
		}
	}

}
