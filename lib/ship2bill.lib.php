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
}