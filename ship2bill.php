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
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

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

if(isset($_REQUEST['subCreateBill'])){
	$TExpedition = $_REQUEST['TExpedition'];
	
	if(empty($TExpedition)) {
		setEventMessage($langs->trans('NoShipmentSelected'), 'warnings');
	} else {
		//Pour chaque ligne du tableau (Chaque client)
		$nbFacture = 0;
		foreach($TExpedition as $id_client => $Tid_exp){
			
			$facture = new Facture($db);
			$facture->socid = $id_client;
			$facture->fetch_thirdparty();
			
			//Données obligatoires
			$facture->date = dol_now();
			$facture->type = 0;
			$facture->cond_reglement_id = (!empty($facture->thirdparty->cond_reglement_id) ? $facture->thirdparty->cond_reglement_id : 1);
			$facture->mode_reglement_id = $facture->thirdparty->mode_reglement_id;
			$facture->modelpdf = 'crabe';
			$facture->statut = 0;
			$facture->create($user);
			$nbFacture++;
			
			//Pour chaque id expédition
			foreach($Tid_exp as $id_exp => $val) {
				
				// Chargement de l'expédition
				$exp = new Expedition($db);
				$exp->fetch($id_exp);
				
				// Lien avec la facture
				$facture->add_object_linked('shipping', $exp->id);
				
				// Regroupement des lignes par expédition via titre
				$title = $langs->trans('Shipment').' '.$exp->ref.' ('.dol_print_date($exp->date_delivery,'day').')';
				
				if($conf->livraison_bon->enabled) {
					$exp->fetchObjectLinked('','','delivery');
					
					// Récupération des infos du BL pour le titre, sinon de l'expédition
					if (! empty($exp->linkedObjectsIds['delivery'][0])) {
						dol_include_once('/livraison/class/livraison.class.php');
						$langs->load("deliveries");
						
						$liv = new Livraison($db);
						$liv->fetch($exp->linkedObjectsIds['delivery'][0]);
						$title = $langs->trans('Delivery').' '.$liv->ref.' ('.dol_print_date($liv->date_delivery,'day').')';
					}
				}
				
				// Affichage des références expéditions en tant que titre
				if($conf->subtotal->enabled) {
					dol_include_once('/subtotal/class/actions_subtotal.class.php');
					$langs->load("subtotal@subtotal");
					$sub = new ActionsSubtotal();
					if(method_exists($sub, 'addSubTotalLine')) $sub->addSubTotalLine($facture, $title, 1);
					else $facture->addline($title, 0,1,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
				} else {
					$facture->addline($title, 0, 1);
				}
	
				//Pour chaque produit de l'expédition, ajout d'une ligne de facture
				foreach($exp->lines as $l){
					if($conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $l->qty == 0) continue;
					$orderline = new OrderLine($db);
					$orderline->fetch($l->fk_origin_line);
					$facture->addline($l->description, $l->subprice, $l->qty, $l->tva_tx,$l->localtax1tx,$l->localtax2tx,$l->fk_product, $l->remise_percent,'','',0,0,'','HT',0,$facture::TYPE_STANDARD,-1,0,'',0,0,$orderline->fk_fournprice,$orderline->pa_ht);
				}
				
				// Affichage d'un sous-total par expédition
				if($conf->subtotal->enabled) {
					if(method_exists($sub, 'addSubTotalLine')) $sub->addSubTotalLine($facture, $langs->trans('SubTotal'), 99);
					else $facture->addline($langs->trans('SubTotal'), 0,99,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
				}
				
				// Clôture de l'expédition
				$exp->set_billed();
			}
		}
	
		setEventMessage($langs->trans('InvoiceCreated', $nbFacture));
		header("Location: ".dol_buildpath('/compta/facture/list.php',2));
		exit;
	}
}


/*
 * View
 */
 
$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ShipmentToBill'),$helpurl);

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

	print_barre_liste($langs->trans('ShipmentToBill'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);

	print '<form name="formAfficheListe" method="POST" action="ship2bill.php">';

	$i = 0;
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDeliveryPlanned"),"liste.php","e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	if($conf->livraison_bon->enabled) {
		print_liste_field_titre($langs->trans("DateReceived"),"liste.php","e.date_expedition","",$param, 'align="center"',$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Status"),"liste.php","e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ShipmentToBill"),"shiptobill.php","","",$param, 'align="center"',$sortfield,$sortorder);
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
			// Date real
			print "<td align=\"center\">";
			print dol_print_date($db->jdate($objp->date_livraison),"day");
			print "</td>\n";
		}

		print '<td align="right">'.$expedition->LibStatut($objp->fk_statut,5).'</td>';
		
		// Sélection expé à facturer
		print '<td align="center">';
		print '<input type="checkbox" checked="checked" name="'.$checkbox.'" />';
		print "</td>\n";
		
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

print '<br /><input style="float:right" class="butAction" type="submit" name="subCreateBill" value="'.$langs->trans('CreateInvoiceButton').'" />';
print '</form>';

$db->close();

llxFooter();
?>
