# Change Log
All notable changes to this project will be documented in this file.

## Unreleased



## RELEASE 1.9

- NEW : Dolibarr compatibility V19 - *04/12/2023* - 1.9.0  
  Changed Dolibarr compatibility range to 15 min - 19 max  
  Change PHP compatibility range to 7.0 min - 8.2 max

## RELEASE 1.8

- FIX : DA023854 - missing include facture class - *27/09/2023* - 1.8.5
- FIX : mising parameters for addline txtva and add condition to test if table of command is empty  - *21/09/2023* - 1.8.4
- FIX : [hregis] Conf saved to the wrong entity - *02/06/2023* - 1.8.3
- FIX : set_billed() function replaced by setBilled() *15/02/2023* - 1.8.2
- FIX : Missing module icon  *17/10/2022* 1.8.1
- FIX : Conf permettant de choisir la date de création plutôt que la date de livraison pour les titres dans les factures (DA022055) *14/10/2022* 1.8.1
- NEW : Ajout de la class TechATM pour l'affichage de la page "A propos" *11/05/2022* 1.8.0

## RELEASE 1.7

- FIX: [thomas-Ngr] Dolibarr log Warning (`run_trigger` renamed to `runTrigger`, alias kept for backward 
  compatibility) - *02/06/2023* - 1.7.7
- FIX: PHP 8 - *04/08/2022* - 1.7.6
- FIX: Compatibility V16 - *07/06/2022* - 1.7.5
- FIX: doublon dans le PDF de la facture générée lorsque ligne libre de produit dans la commande - *2022-06-28* - 1.7.4
- FIX: date facturation n'était jamais mise à jour - *2021-12-09* - 1.7.3
- FIX: compliance with dolistore rules for `main.inc.php` inclusion - *2021-10-20* - 1.7.2
- FIX: incomplete v13 compatibility - *2021-10-07* - 1.7.1
- NEW: Add shipment extrafields values on the created invoice - *2021-02-11* - 1.7.0
- NEW: Add shipment origin (order) as a linked object of the invoice - *2021-02-11* - 1.7.0
