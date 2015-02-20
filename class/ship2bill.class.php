<?php

class Ship2Bill {
	
	function generate_factures($TExpedition, $dateFact=0) {
		global $conf, $langs, $db, $user;
		
		// Inclusion des classes nécessaires
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/core/modules/facture/modules_facture.php');
		
		// Utilisation du module livraison
		if($conf->livraison_bon->enabled) {
			dol_include_once('/livraison/class/livraison.class.php');
		}
		// Utilisation du module sous-total si activé
		if($conf->subtotal->enabled) {
			dol_include_once('/subtotal/class/actions_subtotal.class.php');
			$langs->load("subtotal@subtotal");
			$sub = new ActionsSubtotal();
		}
		
		if(empty($dateFact)) {
			$dateFact = dol_now();
		}
		
		$nbFacture = 0;
		$TFiles = array();
		// Pour chaque id client
		foreach($TExpedition as $id_client => $Tid_exp){
			// Création d'une facture regroupant plusieurs expéditions (par défaut)
			if(empty($conf->global->SHIP2BILL_INVOICE_PER_SHIPMENT)) {
				$f = $this->facture_create($id_client, $dateFact);
				$nbFacture++;
			}
			
			// Pour chaque id expédition
			foreach($Tid_exp as $id_exp => $val) {
				// Chargement de l'expédition
				$exp = new Expedition($db);
				$exp->fetch($id_exp);
				
				// Création d'une facture par expédition si option activée
				if(!empty($conf->global->SHIP2BILL_INVOICE_PER_SHIPMENT)) {
					$f = $this->facture_create($id_client, $dateFact);
					$f->note_public = $exp->note_public;
					$f->note_private = $exp->note_private;
					$f->update($user);
					$nbFacture++;
				}
				
				// Lien avec la facture
				$f->add_object_linked('shipping', $exp->id);
				// Ajout du titre
				$this->facture_add_title($f, $exp, $sub);
				// Ajout des lignes
				$this->facture_add_line($f, $exp);
				// Ajout du sous-total
				$this->facture_add_subtotal($f, $sub);
				
				// Clôture de l'expédition
				if($conf->global->SHIP2BILL_CLOSE_SHIPMENT) $exp->set_billed();
			}
				
			// Validation de la facture
			if($conf->global->SHIP2BILL_VALID_INVOICE) $f->validate($user, '', $conf->global->SHIP2BILL_WARHOUSE_TO_USE);
			
			// Génération du PDF
			if(!empty($conf->global->SHIP2BILL_GENERATE_INVOICE_PDF)) $TFiles[] = $this->facture_generate_pdf($f);
		}
		
		if($conf->global->SHIP2BILL_GENERATE_GLOBAL_PDF) $this->generate_global_pdf($TFiles);

		return $nbFacture;
	}

	function facture_create($id_client, $dateFact) {
		global $user, $db, $conf;
		
		$f = new Facture($db);

		// Si le module Client facturé est activé et que la constante BILLANOTHERCUSTOMER_USE_PARENT_BY_DEFAULT est à 1, on facture la maison mère 
		if($conf->billanothercustomer->enabled && $conf->global->BILLANOTHERCUSTOMER_USE_PARENT_BY_DEFAULT) {
			$soc = new Societe($db);
			$soc->fetch($id_client);
			if($soc->parent > 0)
				$id_client = $soc->parent;
		}
		
		$f->socid = $id_client;
		$f->fetch_thirdparty();
		
		// Données obligatoires
		$f->date = $dateFact;
		$f->type = 0;
		$f->cond_reglement_id = (!empty($f->thirdparty->cond_reglement_id) ? $f->thirdparty->cond_reglement_id : 1);
		$f->mode_reglement_id = $f->thirdparty->mode_reglement_id;
		$f->modelpdf = !empty($conf->global->SHIP2BILL_GENERATE_INVOICE_PDF) ? $conf->global->SHIP2BILL_GENERATE_INVOICE_PDF : 'crabe';
		$f->statut = 0;
		$f->create($user);
		
		return $f;
	}
	
	function facture_add_line(&$f, &$exp) {
		global $conf, $db;
		
		// Pour chaque produit de l'expédition, ajout d'une ligne de facture
		foreach($exp->lines as $l){
			if($conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $l->qty == 0) continue;
			// Sélectionne uniquement les produits
			if ($l->fk_product_type == 0) {
				$orderline = new OrderLine($db);
				$orderline->fetch($l->fk_origin_line);
				
				if((float)DOL_VERSION <= 3.4)
					$f->addline($f->id, $l->description, $l->subprice, $l->qty, $l->tva_tx,$l->localtax1tx,$l->localtax2tx,$l->fk_product, $l->remise_percent,'','',0,0,'','HT',0,0,-1,0,'shipping',$exp->id,0,$orderline->fk_fournprice,$orderline->pa_ht);
				else
					$f->addline($l->description, $l->subprice, $l->qty, $l->tva_tx,$l->localtax1tx,$l->localtax2tx,$l->fk_product, $l->remise_percent,'','',0,0,'','HT',0,0,-1,0,'shipping',$exp->id,0,$orderline->fk_fournprice,$orderline->pa_ht);
			}
		}
		
		//Récupération des services de la commande si SHIP2BILL_GET_SERVICES_FROM_ORDER
		if($conf->global->SHIP2BILL_GET_SERVICES_FROM_ORDER && (float)DOL_VERSION >= 3.5){
			dol_include_once('/commande/class/commande.class.php');
			
			$commande = new Commande($db);
			$commande->fetch($exp->origin_id);
			
			foreach($commande->lines as $line){

				//Prise en compte des services et des lignes libre uniquement
				if($line->fk_product_type == 1 || (empty($line->fk_product_type) && empty($line->fk_product))){
					$f->addline(
							$line->desc,
							$line->price,
							$line->qty,
							$line->tva_tx,
							0,0,
							$line->fk_product,
							$line->remise_percent,
							$line->date_start,
							$line->date_end,
							0,0,
							$line->fk_remise_except,
							'HT',
							0,
							$line->fk_product_type,
							-1,
							$line->special_code,
							'shipping',
							$exp->id,
							$line->fk_parent_line,
							$line->fk_fournprice,
							$line->pa_ht,
							$line->libelle,
							$line->array_option
					);
				}
			}
		}
	}
	
	function facture_add_title (&$f, &$exp, &$sub) {
		global $conf, $langs, $db;
		
		// Affichage des références expéditions en tant que titre
		if($conf->global->SHIP2BILL_ADD_SHIPMENT_AS_TITLES) {
			$title = '';
			$exp->fetchObjectLinked('','commande');
			
			// Récupération des infos de la commande pour le titre
			if (! empty($exp->linkedObjectsIds['commande'][0])) {
				$ord = new Commande($db);
				$ord->fetch($exp->linkedObjectsIds['commande'][0]);
				$title.= $langs->transnoentities('Order').' '.$ord->ref;
				if(!empty($ord->ref_client)) $title.= ' / '.$ord->ref_client;
				if(!empty($ord->date_commande)) $title.= ' ('.dol_print_date($ord->date_commande,'day').')';
			}
			
			$title2 = $langs->transnoentities('Shipment').' '.$exp->ref;
			if(!empty($exp->date_delivery)) $title2.= ' ('.dol_print_date($exp->date_delivery,'day').')';
			if($conf->livraison_bon->enabled) {
				$exp->fetchObjectLinked('','','','delivery');
				
				// Récupération des infos du BL pour le titre, sinon de l'expédition
				if (! empty($exp->linkedObjectsIds['delivery'][0])) {
					$liv = new Livraison($db);
					$liv->fetch($exp->linkedObjectsIds['delivery'][0]);
					$title2 = $langs->transnoentities('Delivery').' '.$liv->ref;
					if(!empty($liv->date_delivery)) $title2.= ' ('.dol_print_date($liv->date_delivery,'day').')';
				}
			}
			
			$title.= ' - '.$title2;
			
			// Ajout du titre
			if($conf->subtotal->enabled) {
				if(method_exists($sub, 'addSubTotalLine')) $sub->addSubTotalLine($f, $title, 1);
				else {
					if((float)DOL_VERSION <= 3.4) $f->addline($f->id, $title, 0,1,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
					else $f->addline($title, 0,1,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
				}
			} else {
				if((float)DOL_VERSION <= 3.4) $f->addline($f->id, $title, 0, 1, 0);
				else $f->addline($title, 0, 1);
			}
		}
	}

	function facture_add_subtotal(&$f,&$sub) {
		global $conf, $langs;
		
		// Ajout d'un sous-total par expédition
		if($conf->global->SHIP2BILL_ADD_SHIPMENT_SUBTOTAL) {
			if($conf->subtotal->enabled) {
				if(method_exists($sub, 'addSubTotalLine')) $sub->addSubTotalLine($f, $langs->transnoentities('SubTotal'), 99);
				else {
					if((float)DOL_VERSION <= 3.4) $f->addline($f->id, $langs->transnoentities('SubTotal'), 0,99,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
					else $f->addline($langs->transnoentities('SubTotal'), 0,99,0,0,0,0,0,'','',0,0,'','HT',0,9,-1, 104777);
				}
			}
		}
	}
	
	function facture_generate_pdf(&$f) {
		global $conf, $langs, $db;
		
		// Il faut recharger les lignes qui viennent juste d'être créées
		$f->fetch($f->id);
		
		$outputlangs = $langs;
		if ($conf->global->MAIN_MULTILANGS) {$newlang=$f->client->default_lang;}
		if (! empty($newlang)) {
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$result=facture_pdf_create($db, $f, $f->modelpdf, $outputlangs);
		
		if($result > 0) {
			$objectref = dol_sanitizeFileName($f->ref);
			$dir = $conf->facture->dir_output . "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";
			return $file;
		}
		
		return '';
	}

	function generate_global_pdf($TFiles) {
		global $langs, $conf;
		
        // Create empty PDF
        $pdf=pdf_getInstance();
        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($langs));

        if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

		// Add all others
		foreach($TFiles as $file)
		{
			// Charge un document PDF depuis un fichier.
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++)
			{
				$tplidx = $pdf->importPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
			}
		}

		// Create output dir if not exists
		$diroutputpdf = $conf->ship2bill->multidir_output[$conf->entity];
		dol_mkdir($diroutputpdf);

		// Save merged file
		$filename=strtolower(dol_sanitizeFileName($langs->transnoentities("ShipmentBilled")));
		if ($pagecount)
		{
			$now=dol_now();
			$file=$diroutputpdf.'/'.$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			setEventMessage($langs->trans('NoPDFAvailableForChecked'),'errors');
		}
	}
}
