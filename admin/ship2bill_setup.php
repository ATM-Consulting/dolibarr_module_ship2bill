<?php

$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load('ship2bill@ship2bill');

global $db;

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action');
$id=GETPOST('id');

/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */

llxHeader('',$langs->trans("Ship2BillSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Ship2BillSetup"),$linkback,'ship2bill@ship2bill');

print '<br>';

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Add shipment as titles in invoice
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AddShipmentAsTitles").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_ADD_SHIPMENT_AS_TITLES">';
print $form->selectyesno("SHIP2BILL_ADD_SHIPMENT_AS_TITLES",$conf->global->SHIP2BILL_ADD_SHIPMENT_AS_TITLES,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if($conf->subtotal->enabled) {
	// Add subtotal
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AddShipmentSubtotal").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_SHIP2BILL_ADD_SHIPMENT_SUBTOTAL">';
	print $form->selectyesno("SHIP2BILL_ADD_SHIPMENT_SUBTOTAL",$conf->global->SHIP2BILL_ADD_SHIPMENT_SUBTOTAL,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

// Close automatically shipments
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CloseShipment").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_CLOSE_SHIPMENT">';
print $form->selectyesno("SHIP2BILL_CLOSE_SHIPMENT",$conf->global->SHIP2BILL_CLOSE_SHIPMENT,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Validate automatically invoice
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ValidInvoice").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_VALID_INVOICE">';
print $form->selectyesno("SHIP2BILL_VALID_INVOICE",$conf->global->SHIP2BILL_VALID_INVOICE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
