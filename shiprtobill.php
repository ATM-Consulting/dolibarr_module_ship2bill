<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/expedition/liste.php
 *      \ingroup    expedition
 *      \brief      Page to list all shipments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("sendings");
$langs->load('companies');

// Security check
$expeditionid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";
$limit = $conf->liste_limit;


/*
 * View
 */
 
echo '<form name="formAfficheListe" method="POST" action = shiprtobill.php?id='.$_REQUEST['id'].' />';
 
$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ListOfSendings'),$helpurl);

$sql = "SELECT e.rowid as id_exp, e.ref, e.date_delivery, e.date_expedition, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."expedition as e";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " WHERE e.entity = ".$conf->entity;
if(!empty($_REQUEST['id'])) {
	$sql.= " AND e.fk_soc = ".$_REQUEST['id'];
}
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if (GETPOST('sf_ref','alpha'))
{
	$sql.= " AND e.ref like '%".$db->escape(GETPOST('sf_ref','alpha'))."%'";
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$expedition = new Expedition($db);

	$param="&amp;socid=$socid";

	print_barre_liste($langs->trans('ListOfSendings'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);


	$i = 0;
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDeliveryPlanned"),"liste.php","e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateReceived"),"liste.php","e.date_expedition","",$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";
	$var=True;
	
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>";
		$shipment->id=$objp->rowid;
		$shipment->ref=$objp->ref;
		print $shipment->getNomUrl(1);
		print "</td>\n";
		// Third party
		print '<td>';
		$companystatic->id=$objp->socid;
		$companystatic->ref=$objp->socname;
		$companystatic->nom=$objp->socname;
		print $companystatic->getNomUrl(1);
		print '</td>';
		// Date delivery  planed
		print "<td align=\"center\">";
		print dol_print_date($db->jdate($objp->date_delivery),"day");
		/*$now = time();
		if ( ($now - $db->jdate($objp->date_expedition)) > $conf->warnings->lim && $objp->statutid == 1 )
		{
		}*/
		print "</td>\n";
		// Date real
		print "<td align=\"center\">";
		print dol_print_date($db->jdate($objp->date_expedition),"day");
		print "</td>\n";
		print "<td align=\"right\">";
		?>
			<input type="checkbox" name="TExpedition[<?=$objp->socid?>][<?=$objp->id_exp?>]" />
		<?
		
		print "</td>";

		print '<td align="right">'.$expedition->LibStatut($objp->fk_statut,5).'</td>';
		print "</tr>\n";

		$i++;
	}

	print "</table>";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

echo '<input class="butAction" text-align="right" type="submit" name="afficheListe" />';
echo '</form>';

if(isset($_REQUEST['afficheListe'])) {
	
	$TExpedition = $_REQUEST['TExpedition'];
	
	foreach($TExpedition as $client) {
		foreach($client as $expeditionCle => $expeditionValeur) {
			
			$sql = 'SELECT f.rowid as facnum';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f'; 
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_element as ee on f.rowid = ee.fk_target';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expedition as e on e.rowid = ee.fk_source';
			$sql .= ' WHERE ee.sourcetype = "shipping"';
			$sql .= ' AND ee.targettype = "facture"';
			$sql .= ' AND e.rowid = '.$expeditionCle;
			
			$resql = $db->query($sql);
			$objp = $db->fetch_object($resql);
			/*echo "exp num : ".$expeditionCle."<br />";
			var_dump($objp->facnum);
			/*exit;*/
			
			if($objp->facnum) {
				$fact = new Facture($db);
				$fact->fetch($objp->facnum);
				
				foreach($fact->lines as $line) {
					// Requête pour récupérer les produits de l'expédition pour par la suite les comparer aux produits de la facture pré-existante
					$sql = 'SELECT cd.fk_product as num_prod';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
					$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande as c on c.rowid = cd.fk_commande';
					$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_element as ee on c.rowid = ee.fk_source';
					$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expedition as e on e.rowid = ee.fk_target';
					$sql .= ' WHERE ee.sourcetype = "commande"';
					$sql .= ' AND ee.targettype = "shipping"';
					$sql .= ' AND e.rowid = '.$expeditionCle;
					
					$resql = $db->query($sql);
					$objp = $db->fetch_object($resql);
					if($line->fk_product == $objp->num_prod) {
						//function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_option=0)
						$fact->updateline($line->rowid, $line->$desc, $pu, $line->qty + $objp->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->txtva);
					}
				}
			} else {
				
			}
			
		}
	}
}

$db->close();

llxFooter();
?>
