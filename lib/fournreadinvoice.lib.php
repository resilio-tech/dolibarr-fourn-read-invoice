<?php
/* Copyright (C) 2024 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    fournreadinvoice/lib/fournreadinvoice.lib.php
 * \ingroup fournreadinvoice
 * \brief   Library files with common functions for FournReadInvoice
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function fournreadinvoiceAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("fournreadinvoice@fournreadinvoice");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/fournreadinvoice/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/fournreadinvoice/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['myobject']['label']) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/fournreadinvoice/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@fournreadinvoice:/fournreadinvoice/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@fournreadinvoice:/fournreadinvoice/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'fournreadinvoice@fournreadinvoice');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'fournreadinvoice@fournreadinvoice', 'remove');

	return $head;
}

function fournreadinvoiceSendMail($subject, $message)
{
	global $conf, $user, $langs;
	$to = getDolGlobalString('FOURNREADINVOICE_MAILREPORT');
	if ($message == '') {
		dol_syslog("FournReadInvoice html mail is empty, do not send email");
		return;
	}
	if (empty($to)) {
		dol_syslog("FournReadInvoice html mail can't be send by email : destination mail is empty, please update this module settings");
		return;
	}
	include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
	$subjecttosend = 'Dolibarr - FournReadInvoice ' . $langs->trans($subject);
	$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
	$mailfile = new CMailFile(
		$subjecttosend,
		$to,
		$from,
		$message,
		null,
		null,
		null,
		'',
		null,
		null,
		1,
		'',
		'',
		null,
		null
	);
	$mailfile->sendfile();
}

function extract_invoice_data($text) {
	$data = [];

	$text = preg_replace('/\s+/', ' ', $text);
	$text = str_replace(['&#039;', "\n"], ["'", ' '], $text);
	$lines = array_filter(array_map('trim', explode("\n", strip_tags($text))), 'strlen');
	$text = implode("\n", $lines); // Reconstruire le texte filtré

	// 1. Extraction du numéro de facture
	if (preg_match('/Numéro de facture.*?#([A-Z0-9]+)/i', $text, $match)) {
		$data['fact'] = $match[1];
	}

	// 2. Extraction du numéro de commande (PO-xxxx-xxxx)
	if (preg_match('/PO\d{4}-\d{4}/', $text, $match)) {
		$data['command'] = $match[0];
	}

	// 3. Extraction du fournisseur (première ligne contenant un indice)
	if (preg_match('/^(.*?)\s+(Date|Adresse|Référence)/mi', $text, $match)) {
		$data['fourn'] = trim($match[1]);
	}

	// 4. Extraction des produits et montants
	$product_pattern = '/([\w\s\-]+)\s+(\d{1,3}(?:[.,]\d{2})?)\s+(\d+)\s+(\d{1,3}(?:[.,]\d{2})?)/m';
	if (preg_match_all($product_pattern, $text, $matches, PREG_SET_ORDER)) {
		$products = [];
		foreach ($matches as $match) {
			$products[] = [
				'product' => trim($match[1]),
				'price' => floatval(str_replace(',', '.', $match[2])),
				'quantity' => intval($match[3]),
				'total' => floatval(str_replace(',', '.', $match[4])),
			];
		}
		$data['products'] = $products;
	}

	// 5. Extraction du montant total
	if (preg_match('/Total\s*\(HT\)?.*?(\d{1,3}(?:[.,]\d{2})?)/i', $text, $match)) {
		$data['total'] = floatval(str_replace(',', '.', $match[1]));
	}

	return $data;
}

function PDFtoText($filename)
{
	$folders = explode("/", $filename);
	$outputDir = implode("/", array_slice($folders, 0, count($folders) - 1)) . '/output';

	if (!is_dir($outputDir)) {
		mkdir($outputDir, 0755, true);
	}
	// For Debian/Ubuntu:
	// - apt update
	// - apt install tesseract-ocr tesseract-ocr-fra imagemagick ghostscript -y
	//
	// For alpine:
	// - apk add --update tesseract-ocr tesseract-ocr-dev imagemagick ghostscript -y
	//
	// - vim /etc/ImageMagick-6/policy.xml
	//   <policy domain="coder" rights="read|write" pattern="PDF" />
	//
	// convert -density 300 /var/www/html/documents/scaninvoices/uploads/now/Facture-2024-41.pdf -depth 8 -strip -background white -alpha off /var/www/html/documents/scaninvoices/uploads/now/page-%04d.png
	$convertCommand = "convert -density 300 $filename -depth 8 -strip -background white -alpha off $outputDir/page-%04d.png";
	exec($convertCommand, $output, $returnVar);

	$images = glob("$outputDir/*.png");
	$text = '';

	foreach ($images as $image) {
		$outputTxt = tempnam(sys_get_temp_dir(), 'ocr') . '.txt';
		$tesseractCommand = "tesseract $image $outputTxt";
		exec($tesseractCommand);

		// Lire le contenu du fichier texte généré
		$text .= file_get_contents($outputTxt . '.txt') . "\n";

		// Nettoyer les fichiers temporaires
		unlink($outputTxt);
		unlink($outputTxt . '.txt');
	}

	array_map('unlink', glob("$outputDir/*.png"));
	rmdir($outputDir);

	return extract_invoice_data(htmlspecialchars($text));
}

function fournreadinvoiceCreateInvoice($command_ref, $file_path)
{
	if (empty($command_ref)) {
		return -1;
	}

	global $db, $langs, $user, $conf;

	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

	$command = new CommandeFournisseur($db);
	// Load command by ref
	$command->fetch(0, $command_ref);

	if (empty($command->id)) {
		var_dump("Command not found with ref : ".$command_ref);
		return -1;
	}

	// Load supplier by name
	$supplier = new Fournisseur($db);
	$supplier->fetch($command->socid);

	if (empty($supplier->id)) {
		var_dump("Supplier not found");
		return -1;
	}

	try {
		// Load supplier invoice
		$invoice = new FactureFournisseur($db);
		$invoice->ref = null;
		$invoice->ref_supplier = $supplier->ref;
		$invoice->socid = $supplier->id;
		$invoice->date = dol_now(); // TODO : set date
		$invoice->date_echeance = null; // TODO : set due date
		$invoice->cond_reglement_id = $supplier->cond_reglement_id;
		$invoice->mode_reglement_id = $supplier->mode_reglement_id;
		$invoice->fk_account = $supplier->fk_account;
		$invoice->note_public = $command->note_public;
		$invoice->fk_multicurrency = $supplier->fk_multicurrency;
		$invoice->multicurrency_code = $supplier->multicurrency_code;
		$invoice->multicurrency_tx = $supplier->multicurrency_tx;
		$invoice->fk_incoterms = $supplier->fk_incoterms;
		$invoice->location_incoterms = $supplier->location_incoterms;

		$invoice->create($user);

		if (empty($invoice->id)) {
			throw new Exception($invoice->error);
		}

		$invoice->fetch($invoice->id);

		// Add products
		foreach ($command->lines as $line) {
			$invoice->addline(
				$line->desc,
				$line->subprice,
				$line->tva_tx,
				$line->localtax1_tx,
				$line->localtax2_tx,
				$line->qty,
				$line->fk_product,
				$line->remise_percent,
				$line->date_start,
				$line->date_end,
				0,
				$line->info_bits
			);
		}

		// Add linked file
		$ref = dol_sanitizeFileName($invoice->ref);
		$upload_dir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier') . $ref;

		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0755, true);
		}

		if (is_dir($upload_dir) && file_exists($file_path)) {
			$dest = $upload_dir . '/' . basename($file_path);
			if (!dol_copy($file_path, $dest)) {
				dol_syslog("Error when linking file to invoice", LOG_ERR);
			}
		}

		// Add linked command
		$invoice->add_object_linked('order_supplier', $command->id);

	} catch (Exception $e) {
		return -1;
	}

	return 0;
}
