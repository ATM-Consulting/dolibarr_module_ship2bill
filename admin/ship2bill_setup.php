<?php

$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory
dol_include_once('/ship2bill/lib/ship2bill.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load('ship2bill@ship2bill');

$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

global $db;

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');

/*
 * Action
 */
if($action == 'set_SHIP2BILL_LIST_LENGTH'){
    $length = GETPOST('SHIP2BILL_LIST_LENGTH', 'int');
    if (dolibarr_set_const($db, 'SHIP2BILL_LIST_LENGTH', $length, 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
    $codeValue = GETPOST($code, 'none');
	if (dolibarr_set_const($db, $code, $codeValue, 'chaine', 0, '', $conf->entity) > 0)
    {
        if($code === 'SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD') {
            if(!empty($codeValue)) setExtraVisibility($codeValue, 's2b_bill_management', 'societe');
            else setExtraVisibility($codeValue, 's2b_bill_management', 'societe');
        }
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
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_ADD_SHIPMENT_AS_TITLES">';
print $form->selectyesno("SHIP2BILL_ADD_SHIPMENT_AS_TITLES",!empty($conf->global->SHIP2BILL_ADD_SHIPMENT_AS_TITLES)?$conf->global->SHIP2BILL_ADD_SHIPMENT_AS_TITLES:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if(!empty($conf->subtotal->enabled)) {
	// Add subtotal
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AddShipmentSubtotal").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print '<input type="hidden" name="action" value="set_SHIP2BILL_ADD_SHIPMENT_SUBTOTAL">';
	print $form->selectyesno("SHIP2BILL_ADD_SHIPMENT_SUBTOTAL",!empty($conf->global->SHIP2BILL_ADD_SHIPMENT_SUBTOTAL)?$conf->global->SHIP2BILL_ADD_SHIPMENT_SUBTOTAL:'',1);
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
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_CLOSE_SHIPMENT">';
print $form->selectyesno("SHIP2BILL_CLOSE_SHIPMENT",!empty($conf->global->SHIP2BILL_CLOSE_SHIPMENT)?$conf->global->SHIP2BILL_CLOSE_SHIPMENT:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

/* Select invoice management
 * 0 => Une facture par tiers
 * 1 => Une facture par expédition
 * 2 => Une facture par commande
 */
$TBillingType = array(0 => $langs->trans('OneBillByThirdparty'), 1 => $langs->trans('OneBillByShipment'), 2 => $langs->trans('OneBillByOrder'));
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("BillingManagement").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="320">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_INVOICE_PER_SHIPMENT">';
print $form->selectarray("SHIP2BILL_INVOICE_PER_SHIPMENT", $TBillingType, !empty($conf->global->SHIP2BILL_INVOICE_PER_SHIPMENT)?$conf->global->SHIP2BILL_INVOICE_PER_SHIPMENT:'');
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Get services from order
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("GetServicesFromOrder").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_GET_SERVICES_FROM_ORDER">';
print $form->selectyesno("SHIP2BILL_GET_SERVICES_FROM_ORDER",!empty($conf->global->SHIP2BILL_GET_SERVICES_FROM_ORDER)?$conf->global->SHIP2BILL_GET_SERVICES_FROM_ORDER:'',1);
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
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_VALID_INVOICE">';
print $form->selectyesno("SHIP2BILL_VALID_INVOICE",!empty($conf->global->SHIP2BILL_VALID_INVOICE)?$conf->global->SHIP2BILL_VALID_INVOICE:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Classified payed order automatically
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ClassifiedPayedOrder").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_CLASSYFIED_PAYED_ORDER">';
print $form->selectyesno("SHIP2BILL_CLASSYFIED_PAYED_ORDER",!empty($conf->global->SHIP2BILL_CLASSYFIED_PAYED_ORDER)?$conf->global->SHIP2BILL_CLASSYFIED_PAYED_ORDER:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';


if(!empty($conf->global->SHIP2BILL_VALID_INVOICE) && !empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
	// Define warehouse to use if stock movement is after invoice validation
	dol_include_once('/product/class/html.formproduct.class.php');
	$formproduct = new FormProduct($db);

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("WarehouseToUseAfterInvoiceValidation").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print '<input type="hidden" name="action" value="set_SHIP2BILL_WARHOUSE_TO_USE">';
	print $formproduct->selectWarehouses(!empty($conf->global->SHIP2BILL_WARHOUSE_TO_USE)?$conf->global->SHIP2BILL_WARHOUSE_TO_USE:'ifone', 'SHIP2BILL_WARHOUSE_TO_USE', '', 1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

// Generate automatically invoice pdf
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("GenerateInvoicePDF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_GENERATE_INVOICE_PDF">';
//print $form->selectyesno("SHIP2BILL_GENERATE_INVOICE_PDF",$conf->global->SHIP2BILL_GENERATE_INVOICE_PDF,1);
dol_include_once('/core/modules/facture/modules_facture.php');
$liste = ModelePDFFactures::liste_modeles($db);
print $form->selectarray('SHIP2BILL_GENERATE_INVOICE_PDF', $liste, !empty($conf->global->SHIP2BILL_GENERATE_INVOICE_PDF)?$conf->global->SHIP2BILL_GENERATE_INVOICE_PDF:'', 1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if(!empty($conf->global->SHIP2BILL_GENERATE_INVOICE_PDF) && $conf->global->SHIP2BILL_GENERATE_INVOICE_PDF != -1 && strpos($conf->global->SHIP2BILL_GENERATE_INVOICE_PDF, 'generic_invoice_odt') === false) {
	// Generate global PDF containing all PDF
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("GenerateGlobalPDF").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print '<input type="hidden" name="action" value="set_SHIP2BILL_GENERATE_GLOBAL_PDF">';
	print $form->selectyesno("SHIP2BILL_GENERATE_GLOBAL_PDF",!empty($conf->global->SHIP2BILL_GENERATE_GLOBAL_PDF)?$conf->global->SHIP2BILL_GENERATE_GLOBAL_PDF:'',1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseDefaultBankAccountInInvoiceModule").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_USE_DEFAULT_BANK_IN_INVOICE_MODULE">';
print $form->selectyesno("SHIP2BILL_USE_DEFAULT_BANK_IN_INVOICE_MODULE",!empty($conf->global->SHIP2BILL_USE_DEFAULT_BANK_IN_INVOICE_MODULE)?$conf->global->SHIP2BILL_USE_DEFAULT_BANK_IN_INVOICE_MODULE:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DisplayCustomerInTitle").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_DISPLAY_ORDERCUSTOMER_IN_TITLE">';
print $form->selectyesno("SHIP2BILL_DISPLAY_ORDERCUSTOMER_IN_TITLE",!empty($conf->global->SHIP2BILL_DISPLAY_ORDERCUSTOMER_IN_TITLE)?$conf->global->SHIP2BILL_DISPLAY_ORDERCUSTOMER_IN_TITLE:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CheckedByDefault").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_CHECKED_BY_DEFAULT">';
print $form->selectyesno("SHIP2BILL_CHECKED_BY_DEFAULT",!empty($conf->global->SHIP2BILL_CHECKED_BY_DEFAULT)?$conf->global->SHIP2BILL_CHECKED_BY_DEFAULT:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SHIP2BILL_LIST_LENGTH").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_LIST_LENGTH">';
print '<input type="text" name="SHIP2BILL_LIST_LENGTH" size="5" '.(!empty($conf->global->SHIP2BILL_LIST_LENGTH) ? 'value="' .$conf->global->SHIP2BILL_LIST_LENGTH . '"' : '').'>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD">';
print $form->selectyesno("SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD",!empty($conf->global->SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD)?$conf->global->SHIP2BILL_MULTIPLE_EXPED_ON_BILL_THIRDPARTY_CARD:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
