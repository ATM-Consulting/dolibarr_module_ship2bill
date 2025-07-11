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
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/expedition/class/expedition.class.php');
dol_include_once('/ship2bill/class/ship2bill.class.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/core/lib/files.lib.php');

set_time_limit(0);
ini_set('memory_limit','2048M');

$langs->load("sendings");
$langs->load("deliveries");
$langs->load("orders");
$langs->load('companies');
$langs->load('ship2bill@ship2bill');

// Security check
if (!empty($user->societe_id)) $socid=$user->societe_id;
else $socid=0;
$result = restrictedArea($user, 'expedition');

$hookmanager->initHooks(array('invoicecard'));

$action = GETPOST('action','alpha');
$search_ref_exp = GETPOST("search_ref_exp", 'none');
$search_start_delivery_date = GETPOST('search_start_delivery_date', 'none');
$search_end_delivery_date = GETPOST('search_end_delivery_date', 'none');
if(!empty($search_start_delivery_date))$tmp_date_start = str_replace('/', '-', $search_start_delivery_date);
if(!empty($search_end_delivery_date))$tmp_date_end = str_replace('/', '-', $search_end_delivery_date);
$search_ref_client = GETPOST("search_ref_client", 'none');
$search_ref_cde = GETPOST("search_ref_cde", 'none');
$search_ref_liv = GETPOST('search_ref_liv', 'none');
$search_societe = GETPOST("search_societe", 'none');
$search_status = GETPOST("search_status", 'int');
$search_order_statut=GETPOST('search_order_statut','int');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
$diroutputpdf=$conf->ship2bill->multidir_output[$conf->entity];
if (getDolGlobalString('SHIP2BILL_LIST_LENGTH')) $conf->liste_limit = getDolGlobalString('SHIP2BILL_LIST_LENGTH');
$offset = 0;
if ($page == -1 || empty($page)){ $page = 0; }
if(!empty($page)) {
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
}
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";
$limit = $conf->liste_limit;


$confirm = GETPOST('confirm', 'none');
$formconfirm = '';
$form=new Form($db);

if(isset($_REQUEST['subCreateBill'])){
	$TExpedition = $_REQUEST['TExpedition'];
	$dateFact = GETPOST('dtfact', 'alphanohtml');
	if(empty($dateFact)) {
		$dateFact = dol_now();
	} else {
		$dateFact = dol_mktime(0, 0, 0, GETPOST('dtfactmonth', 'int'), GETPOST('dtfactday', 'int'), GETPOST('dtfactyear', 'int'));
	}
	if(empty($TExpedition)) {
		setEventMessage($langs->trans('NoShipmentSelected'), 'warnings');
	} else {
		$ship2bill = new Ship2Bill();
		$nbFacture = $ship2bill->generate_factures($TExpedition, $dateFact,true);

		setEventMessage($langs->trans('InvoiceCreated', $nbFacture));
		/*header("Location: ".dol_buildpath('/ship2bill/ship2bill.php',1));*/

	}

	exit;
}

// Remove file
if ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$langs->load("other");
	$upload_dir = $diroutputpdf;
	$file = $upload_dir . '/' . GETPOST('file', 'alpha' );
	$ret=dol_delete_file($file,0,0,0,'');
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile', 'alpha')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile', 'alpha')), 'errors');
	$action='';
}
else if($action=='delete_all_pdf_files') {
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('DeleteAllFiles'), $langs->trans('ConfirmDeleteAllFiles'), 'confirm_delete_all_pdf_files', '', 'no', 1);
}
else if($action=='confirm_delete_all_pdf_files' && $confirm == 'yes') {

	$order = new Ship2Bill($db);
	$order->removeAllPDFFile();

	setEventMessage($langs->trans("FilesWereRemoved"));


}

else if($action=='archive_files') {

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('ArchiveFiles'), $langs->trans('ConfirmArchiveFiles'), 'confirm_archive_files', '', 'no', 1);

}

else if($action=='confirm_archive_files' && $confirm == 'yes') {

	$order = new Ship2Bill($db);
	$order->zipFiles();

}



// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none'))
{

    $search_ref_exp='';
    $search_start_delivery_date='';
    $search_end_delivery_date='';
	$search_ref_client='';
	$search_ref_cde='';
    $search_ref_liv='';
    $search_societe='';
	$search_status='';
}


/*
 * View
 */

$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ShipmentToBill'),$helpurl);

$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

echo $formconfirm;
?>
<script type="text/javascript">
$(document).ready(function() {
	$("#checkall").click(function() {
		$(".checkforgen:not(:checked)").trigger('click');
	});
	$("#checknone").click(function() {
        $(".checkforgen:checked").trigger('click');
	});
});
</script>
<?php

$deliveryTableName = 'delivery';

$sql = "SELECT e.rowid, e.ref, e.date_delivery as date_expedition, l.date_delivery as date_livraison, e.fk_statut
		, s.nom as socname, s.rowid as socid, c.rowid as cdeid, c.ref as cderef, c.ref_client
		FROM ".MAIN_DB_PREFIX."expedition as e";
if (!$user->hasRight('societe', 'client', 'voir') && !$socid)	// Internal user with no permission to see all
{
	$sql.= "INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
}
$sql.= "
		INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee3
 			ON
 		((e.rowid = ee3.fk_target AND ee3.sourcetype = 'commande' AND ee3.targettype = 'shipping')
 			)
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee2
 			ON
 		((e.rowid = ee2.fk_source AND ee2.sourcetype = 'shipping' AND ee2.targettype = 'facture')
			)
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee
 			ON
 		((e.rowid = ee.fk_source AND ee.sourcetype = 'shipping' AND ee.targettype = 'delivery')
 			)
		LEFT JOIN ".MAIN_DB_PREFIX.$deliveryTableName." as l ON l.rowid = ee.fk_target
		LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = ee2.fk_target
		LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.rowid = ee3.fk_source
		WHERE e.entity = ".$conf->entity."
		AND e.fk_statut >= 1
		AND f.rowid IS NULL AND c.facture = 0";
if (!empty($socid))
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if ($search_ref_exp) $sql .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_client) $sql .= natural_search('c.ref_client', $search_ref_client);
if ($search_ref_cde) $sql .= natural_search('c.ref', $search_ref_cde);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_status != -1 && $search_status != '')  $sql .= " AND e.fk_statut = ".$search_status;
if ($search_order_statut != -4 && $search_order_statut != -1 && $search_order_statut != '')  $sql .= " AND c.fk_statut = ".intval($search_order_statut) ;
if(!empty($search_start_delivery_date) && !empty($search_end_delivery_date)) {

	$sql .= " AND e.date_delivery BETWEEN '".date('Y-m-d',strtotime($tmp_date_start))."' AND '".date('Y-m-d',strtotime($tmp_date_end))."'";
}
else if(!empty($search_start_delivery_date) && empty($search_end_delivery_date)) {
	$sql .= " AND DATE(e.date_delivery) >= '".date('Y-m-d',strtotime($tmp_date_start))."'";
}
else if(!empty($search_end_delivery_date) && empty($search_start_delivery_date)) {
	$sql .= " AND DATE(e.date_delivery) <= '".date('Y-m-d',strtotime($tmp_date_end))."'";
}


$sql2 = "SELECT e.rowid, e.ref, e.date_delivery as date_expedition, l.date_delivery as date_livraison, e.fk_statut
		, s.nom as socname, s.rowid as socid, c.rowid as cdeid, c.ref as cderef, c.ref_client
		FROM ".MAIN_DB_PREFIX."expedition as e";
if (!$user->hasRight('societe', 'client', 'voir') && !$socid)	// Internal user with no permission to see all
{
	$sql2.= "INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
}
$sql2.= "
		INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee3
 			ON
 		(
 		(e.rowid = ee3.fk_source AND ee3.targettype = 'commande' AND ee3.sourcetype = 'shipping'))
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee2
 			ON
 		(
		(e.rowid = ee2.fk_target AND ee2.targettype = 'shipping' AND ee2.sourcetype = 'facture'))
		LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee
 			ON
 		(
 		(e.rowid = ee.fk_target AND ee.targettype = 'shipping' AND ee.sourcetype = 'delivery'))
		LEFT JOIN ".MAIN_DB_PREFIX.$deliveryTableName." as l ON l.rowid = ee.fk_target
		LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = ee2.fk_target
		LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.rowid = ee3.fk_source
		WHERE e.entity = ".$conf->entity."
		AND e.fk_statut >= 1
		AND f.rowid IS NULL AND c.facture = 0";
if (!empty($socid))
{
	$sql2.= " AND e.fk_soc = ".$socid;
}
if ($search_ref_exp) $sql2 .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_client) $sql2 .= natural_search('c.ref_client', $search_ref_client);
if ($search_ref_cde) $sql2 .= natural_search('c.ref', $search_ref_cde);
if ($search_ref_liv) $sql2 .= natural_search('l.ref', $search_ref_liv);
if ($search_societe) $sql2 .= natural_search('s.nom', $search_societe);
if ($search_status != -1 && $search_status != '')  $sql2 .= " AND e.fk_statut = ".$search_status;
if(!empty($search_start_delivery_date) && !empty($search_end_delivery_date)) {

	$sql2 .= " AND e.date_delivery BETWEEN '".date('Y-m-d',strtotime($tmp_date_start))."' AND '".date('Y-m-d',strtotime($tmp_date_end))."'";
}
else if(!empty($search_start_delivery_date) && empty($search_end_delivery_date)) {
	$sql2 .= " AND DATE(e.date_delivery) >= '".date('Y-m-d',strtotime($tmp_date_start))."'";
}
else if(!empty($search_end_delivery_date) && empty($search_start_delivery_date)) {
	$sql2 .= " AND DATE(e.date_delivery) <= '".date('Y-m-d',strtotime($tmp_date_end))."'";
}

$r = $db->query("CREATE TEMPORARY TABLE ".MAIN_DB_PREFIX."ship2bill_view ".$sql." UNION ".$sql2 );
$sortfieldClean = $sortfield;
if(strpos($sortfieldClean,'.')!==false) {
	list($dummy,$sortfieldClean) = explode('.', $sortfield);
}

$sqlView="SELECT * FROM ".MAIN_DB_PREFIX."ship2bill_view ";

$sqlView.= $db->order($sortfieldClean,$sortorder);
$sqlView.= $db->plimit($limit + 1,$offset);

$resql=$db->query($sqlView);

if ($resql)
{
	$num = $db->num_rows($resql);
	$colspan = 7;

	$param="&amp;socid=$socid";

	if ($search_ref_exp) $param.= "&amp;search_ref_exp=".$search_ref_exp;
	if ($search_ref_client) $param.= "&amp;search_ref_client=".$search_ref_client;
	if ($search_ref_cde) $param.= "&amp;search_ref_cde=".$search_ref_cde;
	if ($search_ref_liv) $param.= "&amp;search_ref_liv=".$search_ref_liv;
	if ($search_societe) $param.= "&amp;search_societe=".$search_societe;
	if ($search_status)  $param.= "&amp;search_status=".$search_status;
	if ($search_start_delivery_date)  $param.= "&amp;search_start_delivery_date=".$search_start_delivery_date;
	if ($search_end_delivery_date)  $param.= "&amp;search_end_delivery_date=".$search_end_delivery_date;
	print_barre_liste($langs->trans('ShipmentToBill').(getDolGlobalString('SHIP2BILL_GET_SERVICES_FROM_ORDER') ? ' ('.$langs->trans('TotalHTShippingAndTotalHTBillCanBeDifferent').')' : ''), $page, "ship2bill.php",$param,$sortfield,$sortorder,'',$num);

	print '<form name="formAfficheListe" id="formShip2Bill" method="POST" action="ship2bill.php">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	$i = 0;
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"ship2bill.php","e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("RefOrder"),"ship2bill.php","c.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Réf. Client"),"ship2bill.php","c.ref_client","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"ship2bill.php","socname", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDeliveryPlanned"),"ship2bill.php","date_expedition","",$param, 'align="center"',$sortfield,$sortorder);

	// Utilisation du sous-module livraison activable dans expedition
	if (isModEnabled('delivery_note')) {
		print_liste_field_titre($langs->trans("DeliveryOrder"),"ship2bill.php","e.ref","",$param, '',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateReceived"),"ship2bill.php","e.date_expedition","",$param, 'align="center"',$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Stateshipment"),"ship2bill.php","e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("StateOrder"),"ship2bill.php","c.status","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),"ship2bill.php","","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ShipmentToBill"),"shiptobill.php","","",$param, 'align="center"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref_cde" value="'.$search_ref_cde.'">';
	print '</td>';
    print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref_client" value="'.$search_ref_client.'">';
    print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="10" name="search_societe" value="'.dol_escape_htmltag($search_societe).'">';
	print '</td>';
	print '<td class="liste_titre" align="center">'.$form->selectDate(!empty($search_start_delivery_date)?strtotime($tmp_date_start):'','search_start_delivery_date',0,0,1).'
			</br>'.$form->selectDate(!empty($search_end_delivery_date)?strtotime($tmp_date_end):'','search_end_delivery_date',0,0,1).'</td>';

	// Utilisation du sous-module livraison activable dans expedition
	if(isModEnabled('delivery_note')) {
		$colspan += 2;
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
		print '<td class="liste_titre">&nbsp;</td>';
	}

	//print '<td class="liste_titre" align="right">';
	print '<td class="liste_titre" align="right">';
	$TStatus[0] = $langs->trans('StatusSendingDraftShort');
	$TStatus[1] = $langs->trans('StatusSendingValidatedShort');
	$TStatus[2] = $langs->trans('StatusSendingProcessedShort');
	$f = new Form($db);
	print $f->selectarray('search_status', $TStatus, $search_status, true);
	print '</td>';
	print '<td class="liste_titre"  align="right" >';
	$liststatus=array(
			Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"),
			Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
			Commande::STATUS_ACCEPTED=>$langs->trans("StatusOrderSentShort"),
			Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
			Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
	);
	print $form->selectarray('search_order_statut', $liststatus, $search_order_statut, -4);
	print '</td>';
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
	$total = 0;
	$checked = (!getDolGlobalString('SHIP2BILL_CHECKED_BY_DEFAULT')) ? '' : ' checked="checked"';

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$checkbox = 'TExpedition['.$objp->socid.']['.$objp->rowid.']';
		$shipment->fetch($objp->rowid);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>";
		print $shipment->getNomUrl(1);
		print "</td>\n";
		// Order
		print "<td>";
		$commande = new Commande($db);
		//$commande->fetch($shipment->origin_id);
		/*$commande->id = $objp->cdeid;
		$commande->ref = $objp->cderef;*/
		$commande->fetch($objp->cdeid); // Plus propre
		print $commande->getNomUrl(1);
		print "</td>\n";

		// Order ref client
		print "<td>";
		print $objp->ref_client;
		print "</td>\n";

		// Third party
		print '<td>';
		/*$companystatic->id=$objp->socid;
		$companystatic->ref=$objp->socname;
		$companystatic->nom=$objp->socname;*/
		$companystatic->fetch($objp->socid); // Plus propre
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

		// Utilisation du sous-module livraison activable dans expedition
		if(isModEnabled('delivery_note')) {
			$shipment->fetchObjectLinked($shipment->id,$shipment->element);
			if(!empty($shipment->linkedObjects['delivery'])) $receiving=(! empty($shipment->linkedObjects['delivery'][key($shipment->linkedObjects['delivery'])])?$shipment->linkedObjects['delivery'][key($shipment->linkedObjects['delivery'])]:'');
			else $receiving = '';

			// Ref
			print '<td>';
			print !empty($receiving) ? $receiving->getNomUrl($db) : '';
			print '</td>';

			// Date real
			print "<td align=\"center\">";
			print dol_print_date($db->jdate($objp->delivery_date ?? null),"day");
			print "</td>\n";
		}

		print '<td align="right">'.$shipment->getLibStatut(5).'</td>'."\n";
		print '<td align="right">'.$commande->getLibStatut(5).'</td>'."\n";

		print '<td align="right" class="totalShipment">'.price($shipment->total_ht).'</td>';

		// Sélection expé à facturer
		print '<td align="center">';
		print '<input type="checkbox"'.$checked.' name="'.$checkbox.'" class="checkforgen" price="'.price2num($shipment->total_ht).'" />';
		print "</td>\n";

		print "</tr>\n";

		$total += $shipment->total_ht;

		$i++;
	}

	if ($total>0)
	{
		print '<tr class="liste_total">';
		if($num<$limit){
			print '<td align="left">'.$langs->trans("TotalHT").'</td>';
		}
		else
		{
			print '<td align="left">'.$langs->trans("TotalHTforthispage").'</td>';
		}

		print '<td colspan="'.$colspan.'" align="right">'.price($total).'<td align="center"><span id="totalExpeditionChecked"></span></td>';
		print '</tr>';
	}

	print "</table>";

	echo "
		<script type='text/javascript'>
			$(function() {

				function calculTotalExpeditionChecked()
				{
					var totalPriceChecked = 0;
					$('form[name=formAfficheListe] tr input.checkforgen:checked').each(function(index) {
						var price = $(this).attr('price');
						totalPriceChecked += parseFloat(price);
					});

					if (typeof totalPriceChecked.toFixed == 'function') totalPriceChecked = totalPriceChecked.toFixed(2);
					totalPriceChecked = String(totalPriceChecked).replace('.', ',');

					$('#totalExpeditionChecked').html(totalPriceChecked);
				}

				calculTotalExpeditionChecked();

				$('form[name=formAfficheListe] tr input.checkforgen').unbind().click(function() {
					calculTotalExpeditionChecked();
				});
			})
		</script>
	";

	if($num > 0 && $user->hasRight('facture', 'creer')) {
		$f = new Form($db);
		print '<br><div style="text-align: right;">';
		print $langs->trans('Date').' : ';
		$f->select_date('', 'dtfact');
		print '<input class="butAction" type="button" id="subCreateBill" name="subCreateBill" value="'.$langs->trans('CreateInvoiceButton').'"  />';
		print '</div>';

		?>
		<div id="pop-wait" style="display:none;text-align:center;"><?php echo img_picto('','ajax-loader.gif@ship2bill'); ?><br /><span class="info"></span></div>
		<script type="text/javascript">
		$('#subCreateBill').click(function() {

			$('#pop-wait').dialog({
				'modal':true
				,title:"<?php echo $langs->transnoentities('GenerationInProgress') ?>"
				,open: function(event, ui) {
			        $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
			    }
		    	,closeOnEscape: false
			});

			var data = $("#formShip2Bill").serialize();

			$.ajax({
				url:"<?php echo $_SERVER['PHP_SELF'] ?>"
				,data:data+"&subCreateBill=1"
				,method:"post"
				,xhr: function() {
			        var xhr = new window.XMLHttpRequest();

			       // Download progress
			       xhr.addEventListener("progress", function(evt){
			    	   console.log('evt',evt);
			           if (evt.lengthComputable) {
			               var percentComplete = Math.round(evt.loaded / evt.total);
			               // Do something with download progress
			               $('#pop-wait span.info').html(percentComplete);
			           }
			       }, false);

			       return xhr;
			    }

			}).done(function(result) {

				//console.log(result);
				document.location.href="<?php echo dol_buildpath('/ship2bill/ship2bill.php',1); ?>";
			});

			return false;

		});
		</script>

		<?php

	}
	print '</form>';

	if(getDolGlobalString('SHIP2BILL_GENERATE_GLOBAL_PDF')) {
		print '<br><br>';
		$formfile = new FormFile($db);
		$urlsource = isset($urlsource) ?? '';
		print $formfile->showdocuments('ship2bill','',$diroutputpdf,$urlsource,false,true,'',1,1,0,481,$param,$langs->trans("GlobalGeneratedFiles"));

		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="?action=archive_files&token='.$newToken.'">'.$langs->trans('ArchiveFiles').'</a>';
		echo '<a class="butAction" href="?action=delete_all_pdf_files&token='.$newToken.'">'.$langs->trans('DeleteAllFiles').'</a>';
		echo '</div>';
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
