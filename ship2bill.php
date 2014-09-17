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

require 'config.php';
dol_include_once('/expedition/class/expedition.class.php');
dol_include_once('/ship2bill/class/ship2bill.class.php');

$langs->load("sendings");
$langs->load("deliveries");
$langs->load("orders");
$langs->load('companies');
$langs->load('ship2bill@ship2bill');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition');

$search_ref_exp = GETPOST("search_ref_exp");
$search_ref_liv = GETPOST('search_ref_liv');
$search_societe = GETPOST("search_societe");

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

if(isset($_REQUEST['subCreateBill'])){
	$TExpedition = $_REQUEST['TExpedition'];
	
	if(empty($TExpedition)) {
		setEventMessage($langs->trans('NoShipmentSelected'), 'warnings');
	} else {
		$ship2bill = new Ship2Bill();
		$nbFacture = $ship2bill->generate_factures($TExpedition);
	
		setEventMessage($langs->trans('InvoiceCreated', $nbFacture));
		header("Location: ".dol_buildpath('/compta/facture/list.php',2));
		exit;
	}
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_ref_exp='';
    $search_ref_liv='';
    $search_societe='';
}


/*
 * View
 */
 
$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ShipmentToBill'),$helpurl);
?>
<script type="text/javascript">
$(document).ready(function() {
	$("#checkall").click(function() {
		$(".checkforgen").attr('checked', true);
	});
	$("#checknone").click(function() {
		$(".checkforgen").attr('checked', false);
	});
});
</script>
<?php

$sql = "SELECT e.rowid, e.ref, e.date_delivery as date_expedition, l.date_delivery as date_livraison, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."expedition as e";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'shipping'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.rowid = ee.fk_target AND ee.targettype = 'delivery'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = ee.fk_target AND ee.targettype = 'facture'";
$sql.= " WHERE e.entity = ".$conf->entity;
$sql.= " AND e.fk_statut = 1";
$sql.= " AND f.rowid IS NULL";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if ($search_ref_exp) $sql .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

$resql=$db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

	$expedition = new Expedition($db);

	$param="&amp;socid=$socid";
	if ($search_ref_exp) $param.= "&amp;search_ref_exp=".$search_ref_exp;
	if ($search_ref_liv) $param.= "&amp;search_ref_liv=".$search_ref_liv;
	if ($search_societe) $param.= "&amp;search_societe=".$search_societe;

	print_barre_liste($langs->trans('ShipmentToBill'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);
	
	print '<form name="formAfficheListe" method="POST" action="ship2bill.php">';

	$i = 0;
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"ship2bill.php","e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"ship2bill.php","s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDeliveryPlanned"),"ship2bill.php","e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	if($conf->livraison_bon->enabled) {
		print_liste_field_titre($langs->trans("DeliveryOrder"),"ship2bill.php","e.date_expedition","",$param, '',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateReceived"),"ship2bill.php","e.date_expedition","",$param, 'align="center"',$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Status"),"ship2bill.php","e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ShipmentToBill"),"shiptobill.php","","",$param, 'align="center"',$sortfield,$sortorder);
	print "</tr>\n";
	
	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	// Ref
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
    print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="10" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	if($conf->livraison_bon->enabled) {
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
		print '<td class="liste_titre">&nbsp;</td>';
	}
	print '<td class="liste_titre" align="right">';
	// Développé dans la 3.7
	//print img_search();
	//print img_searchclear();
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
	print '</td>';
	
	print "</tr>\n";
	
	$var=True;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$checkbox = 'TExpedition['.$objp->socid.']['.$objp->rowid.']';

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
		print dol_print_date($db->jdate($objp->date_expedition),"day");
		/*$now = time();
		if ( ($now - $db->jdate($objp->date_expedition)) > $conf->warnings->lim && $objp->statutid == 1 )
		{
		}*/
		print "</td>\n";
		if($conf->livraison_bon->enabled) {
			$shipment->fetchObjectLinked($shipment->id,$shipment->element);
			$receiving=(! empty($shipment->linkedObjects['delivery'][0])?$shipment->linkedObjects['delivery'][0]:'');
			
			// Ref
			print '<td>';
			print !empty($receiving) ? $receiving->getNomUrl($db) : '';
			print '</td>';
			
			// Date real
			print "<td align=\"center\">";
			print dol_print_date($db->jdate($objp->date_livraison),"day");
			print "</td>\n";
		}

		print '<td align="right">'.$expedition->LibStatut($objp->fk_statut,5).'</td>';
		
		// Sélection expé à facturer
		print '<td align="center">';
		print '<input type="checkbox" checked="checked" name="'.$checkbox.'" class="checkforgen" />';
		print "</td>\n";
		
		print "</tr>\n";

		$i++;
	}

	print "</table>";
	if($num > 0) {
		print '<br /><input style="float:right" class="butAction" type="submit" name="subCreateBill" value="'.$langs->trans('CreateInvoiceButton').'" />';
	}
	print '</form>';
	
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
