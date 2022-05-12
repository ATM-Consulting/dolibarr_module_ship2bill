<?php
/**
 * Created by PhpStorm.
 * User: quentin
 * Date: 19/02/19
 * Time: 14:13
 */

function setExtraVisibility($value, $name, $elementtype){
    global $db;

    $sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET list='.$value.' WHERE name="'.$name.'" AND elementtype="'.$elementtype.'"';

    $resql = $db->query($sql);

    if(!empty($resql))return true;
    else return $db->lasterror();

	/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
}

	/**
	 *	\file		lib/ship2bill.lib.php
	 *	\ingroup	ship2bill
	 *	\brief		This file is an example module library
	 *				Put some comments here
	 */

	function ship2billAdminPrepareHead()
	{
		global $langs, $conf;

		$langs->load("ship2bill@ship2bill");

		$h = 0;
		$head = array();

		$head[$h][0] = dol_buildpath("/ship2bill/admin/ship2bill_setup.php", 1);
		$head[$h][1] = $langs->trans("Parameters");
		$head[$h][2] = 'settings';
		$h++;
		$head[$h][0] = dol_buildpath("/ship2bill/admin/ship2bill_about.php", 1);
		$head[$h][1] = $langs->trans("About");
		$head[$h][2] = 'about';
		$h++;

		// Show more tabs from modules
		// Entries must be declared in modules descriptor with line
		//$this->tabs = array(
		//	'entity:+tabname:Title:@postit:/postit/mypage.php?id=__ID__'
		//); // to add new tab
		//$this->tabs = array(
		//	'entity:-tabname:Title:@postit:/postit/mypage.php?id=__ID__'
		//); // to remove a tab
		complete_head_from_modules($conf, $langs, $object, $head, $h, 'ship2bill');

		return $head;
	}

