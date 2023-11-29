<?php
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include __DIR__ . "/../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include __DIR__ . "/../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

if(!defined('INC_FROM_CRON_SCRIPT') && !defined('INC_FROM_DOLIBARR')) {

	if(!class_exists('modShip2bill')) dol_include_once('/ship2bill/core/modules/modShip2bill.class.php');
	checkVersion($db, 'modShip2bill');

}

//Fonction reprise d'abricot, car module fonctionnant sans abricot
/**
 * @param DoliDB $DoliDb
 * @param string $moduleName
 */
function checkVersion(&$DoliDb, $moduleName) {
	global $conf;
	if(class_exists($moduleName)) {

		$conf_name = 'ATM_MODULE_VERSION_'.strtoupper($moduleName);

		$mod = new $moduleName($DoliDb);

		if(!empty($mod->version)) {
			$version = $mod->version;
			if(versionXY(getDolGlobalString($conf_name)) != versionXY($version)) {

				$message = "Your module wasn't updated (v" . getDolGlobalString($conf_name)." != ".$version."). Please reload it or launch the update of database script";

				accessforbidden($message);
			}
		}
	}

}

/**
 * @param string $version  A version number in the form X.Y.Z (ex: 1.21.9)
 *
 * @return string  The same version number with only X.Y (ex: 1.21)
 */
function versionXY($version) {
	return preg_replace('/^(\d+\.\d+)(\.\d+)?$/', '$1', $version);
}
