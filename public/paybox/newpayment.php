<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *     	\file       htdocs/public/paybox/newpayment.php
 *		\ingroup    paybox
 *		\brief      File to offer a way to make a payment for a particular Dolibarr entity
 *		\author	    Laurent Destailleur
 *		\version    $Id: newpayment.php,v 1.52 2010/12/01 21:42:11 eldy Exp $
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paybox/lib/paybox.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

// Security check
if (empty($conf->paybox->enabled)) accessforbidden('',1,1,1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("errors");
$langs->load("paybox");

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix=GETPOST("suffix",'alpha');
$amount=price2num(GETPOST("amount"));
if (! GETPOST("currency",'alpha')) $currency=$conf->global->MAIN_MONNAIE;
else $currency=GETPOST("currency",'alpha');

if (! GETPOST("action"))
{
    if (! GETPOST("amount") && ! GETPOST("source"))
    {
        dol_print_error('',$langs->trans('ErrorBadParameters')." - amount or source");
    	exit;
    }
    if (is_numeric($amount) && ! GETPOST("tag") && ! GETPOST("source"))
    {
        dol_print_error('',$langs->trans('ErrorBadParameters')." - tag or source");
    	exit;
    }
    if (GETPOST("source") && ! GETPOST("ref"))
    {
        dol_print_error('',$langs->trans('ErrorBadParameters')." - ref");
    	exit;
    }
}

$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',$dolibarr_main_url_root);
$urlok=$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/paymentok.php?';
$urlko=$urlwithouturlroot.DOL_URL_ROOT.'/public/paypal/paymentko.php?';

// Complete urls
$TAG=GETPOST("tag",'alpha');
$FULLTAG=GETPOST("fulltag",'alpha');  // fulltag is tag with more informations

if (!empty($TAG))
{
    $urlok.='tag='.urlencode($TAG).'&';
    $urlko.='tag='.urlencode($TAG).'&';
}
if (!empty($FULLTAG))
{
    $urlok.='fulltag='.urlencode($FULLTAG).'&';
    $urlko.='fulltag='.urlencode($FULLTAG).'&';
}
$urlok=preg_replace('/&$/','',$urlok);  // Remove last &
$urlko=preg_replace('/&$/','',$urlko);  // Remove last &


/*
 * Actions
 */
if (GETPOST("action") == 'dopayment')
{
    $PRICE=price2num(GETPOST("newamount"),'MT');
    $EMAIL=GETPOST("EMAIL");

	$mesg='';
	if (empty($PRICE) || ! is_numeric($PRICE)) $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount"));
	elseif (empty($EMAIL))          $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("YourEMail"));
	elseif (! isValidEMail($EMAIL)) $mesg=$langs->trans("ErrorBadEMail",$EMAIL);
	elseif (empty($FULLTAG))        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentCode"));

	if (empty($mesg))
	{
		dol_syslog("newpayment.php call paybox api and do redirect", LOG_DEBUG);

		print_paybox_redirect($PRICE, $conf->monnaie, $EMAIL, $urlok, $urlko, $TAG);

		session_destroy();
		exit;
	}
}



/*
 * View
 */

llxHeaderPayBox($langs->trans("PaymentForm"));


// Common variables
$creditor=$mysoc->nom;
$paramcreditor='PAYBOX_CREDITOR_'.$suffix;
if (! empty($conf->global->$paramcreditor)) $creditor=$conf->global->$paramcreditor;
else if (! empty($conf->global->PAYBOX_CREDITOR)) $creditor=$conf->global->PAYBOX_CREDITOR;

print '<span id="dolpaymentspan"></span>'."\n";
print '<center>';
print '<form id="dolpaymentform" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="dopayment">';
print '<input type="hidden" name="amount" value="'.GETPOST("amount",'int').'">';
print '<input type="hidden" name="tag" value="'.GETPOST("tag",'alpha').'">';
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix",'alpha').'">';
print "\n";
print '<!-- Form to send a Paybox payment -->'."\n";
print '<!-- PAYBOX_CREDITOR = '.$conf->global->PAYPAL_CREDITOR.' -->'."\n";
print '<!-- urlok = '.$urlok.' -->'."\n";
print '<!-- urlko = '.$urlko.' -->'."\n";
print "\n";

print '<table id="dolpaymenttable" style="font-size:14px;" summary="Payment form" width="80%">'."\n";

// Show logo (search order: logo defined by PAYBOX_LOGO_suffix, then PAYBOX_LOGO, then small company logo, large company logo, theme logo, common logo)
$width=0;
// Define logo and logosmall
$logosmall=$mysoc->logo_small;
$logo=$mysoc->logo;
$paramlogo='PAYBOX_LOGO_'.$suffix;
if (! empty($conf->global->$paramlogo)) $logosmall=$conf->global->$paramlogo;
else if (! empty($conf->global->PAYBOX_LOGO)) $logosmall=$conf->global->PAYBOX_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo='';
if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$logosmall);
}
elseif (! empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($logo);
	$width=96;
}
// Output html code for logo
if ($urllogo)
{
	print '<tr>';
	print '<td align="center"><img id="dolpaymentlogo" title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></td>';
	print '</tr>'."\n";
}

print '<tr><td align="center"><br>'.$langs->trans("WelcomeOnPaymentPage").'<br></td></tr>'."\n";

print '<tr><td align="center"><br>'.$langs->trans("ThisScreenAllowsYouToPay",$creditor).'<br><br></td></tr>'."\n";

print '<tr><td align="center">';
print '<table with="100%">';
print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("ThisIsInformationOnPayment").' :</td></tr>'."\n";

$found=false;
$error=0;
$var=false;



// Free payment
if (! GETPOST("source"))
{
	$found=true;
    $tag=GETPOST("tag");
    $fulltag=$tag;

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount").'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}

	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.GETPOST("EMAIL").'"></td></tr>'."\n";
}


// Payment on customer order
if (GETPOST("source") == 'order')
{
	$found=true;
	$langs->load("orders");

	require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

	$order=new Commande($db);
	$result=$order->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$order->error;
		$error++;
	}
	else
	{
		$result=$order->fetch_thirdparty($order->socid);
	}

	$amount=$order->total_ttc;
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='IR='.$order->ref.'.TPID='.$order->client->id.'.TP='.strtr($order->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $fulltag.='.TAG='.$_REQUEST["tag"]; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$order->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentOrderRef",$order->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$order->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount").'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
	$email=$order->client->email;
	$email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
}


// Payment on customer invoice
if (GETPOST("source") == 'invoice')
{
	$found=true;
	$langs->load("bills");

	require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

	$invoice=new Facture($db);
	$result=$invoice->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$invoice->error;
		$error++;
	}
	else
	{
		$result=$invoice->fetch_thirdparty($invoice->socid);
	}

	$amount=$invoice->total_ttc - $invoice->getSommePaiement();
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='IR='.$invoice->ref.'.TPID='.$invoice->client->id.'.TP='.strtr($invoice->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $fulltag.='.TAG='.$_REQUEST["tag"]; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$invoice->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentInvoiceRef",$invoice->ref).'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$invoice->ref.'">';
	print '</td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.GETPOST("newamount").'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$invoice->client->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
}

// Payment on contract line
if (GETPOST("source") == 'contractline')
{
	$found=true;
	$langs->load("contracts");

	require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

	$contractline=new ContratLigne($db);
	$result=$contractline->fetch('',$_REQUEST["ref"]);
	if ($result < 0)
	{
		$mesg=$contractline->error;
		$error++;
	}
	else
	{
		if ($contractline->fk_contrat > 0)
		{
			$contract=new Contrat($db);
			$result=$contract->fetch($contractline->fk_contrat);
			if ($result > 0)
			{
				$result=$contract->fetch_thirdparty($contract->socid);
			}
			else
			{
				$mesg=$contract->error;
				$error++;
			}
		}
		else
		{
			$mesg='ErrorRecordNotFound';
			$error++;
		}
	}

	$amount=$contractline->total_ttc;
	if ($contractline->fk_product)
	{
		$product=new Product($db);
		$result=$product->fetch($contractline->fk_product);

		// We define price for product (TODO Put this in a method in product class)
		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			$pu_ht = $product->multiprices[$contract->client->price_level];
			$pu_ttc = $product->multiprices_ttc[$contract->client->price_level];
			$price_base_type = $product->multiprices_base_type[$contract->client->price_level];
		}
		else
		{
			$pu_ht = $product->price;
			$pu_ttc = $product->price_ttc;
			$price_base_type = $product->price_base_type;
		}

		$amount=$pu_ttc;
		if (empty($amount))
		{
			dol_print_error('','ErrorNoPriceDefinedForThisProduct');
			exit;
		}
	}
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='CLR='.$contractline->ref.'.CR='.$contract->ref.'.TPID='.$contract->client->id.'.TP='.strtr($contract->client->nom,"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $fulltag.='.TAG='.$_REQUEST["tag"]; }
	$fulltag=dol_string_unaccent($fulltag);

	$qty=1;
	if (isset($_REQUEST["qty"])) $qty=$_REQUEST["qty"];

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$contract->client->nom.'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentRenewContractId",$contract->ref,$contractline->ref).'</b>';
	if ($contractline->fk_product)
	{
		$text.='<br>'.$product->ref.($product->libelle?' - '.$product->libelle:'');
	}
	if ($contractline->description) $text.='<br>'.dol_htmlentitiesbr($contractline->description);
	//if ($contractline->date_fin_validite) {
	//	$text.='<br>'.$langs->trans("DateEndPlanned").': ';
	//	$text.=dol_print_date($contractline->date_fin_validite);
	//}
	if ($contractline->date_fin_validite)
	{
		$text.='<br>'.$langs->trans("ExpiredSince").': '.dol_print_date($contractline->date_fin_validite);
	}

	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$contractline->ref.'">';
	print '</td></tr>'."\n";

	// Quantity
	$var=!$var;
	$label=$langs->trans("Quantity");
	$qty=1;
	$duration='';
	if ($contractline->fk_product)
	{
		if ($product->isservice() && $product->duration_value > 0)
		{
			$label=$langs->trans("Duration");

			// TODO Put this in a global method
			if ($product->duration_value > 1)
			{
				$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("DurationDays"),"w"=>$langs->trans("DurationWeeks"),"m"=>$langs->trans("DurationMonths"),"y"=>$langs->trans("DurationYears"));
			}
			else
			{
				$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("DurationDay"),"w"=>$langs->trans("DurationWeek"),"m"=>$langs->trans("DurationMonth"),"y"=>$langs->trans("DurationYear"));
			}
			$duration=$product->duration_value.' '.$dur[$product->duration_unit];
		}
	}
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$label.'</td>';
	print '<td class="CTableRow'.($var?'1':'2').'"><b>'.($duration?$duration:$qty).'</b>';
	print '<input type="hidden" name="newqty" value="'.dol_escape_htmltag($qty).'">';
	print '</b></td></tr>'."\n";

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$contract->client->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";

}

// Payment on member subscription
if (GETPOST("source") == 'membersubscription')
{
	$found=true;
	$langs->load("members");

	require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
	require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");

	$member=new Adherent($db);
	$result=$member->fetch('',GETPOST("ref"));
	if ($result < 0)
	{
		$mesg=$member->error;
		$error++;
	}
	else
	{
		$subscription=new Cotisation($db);
	}

	$amount=$subscription->total_ttc;
    if (GETPOST("amount",'int')) $amount=GETPOST("amount",'int');
    $amount=price2num($amount);

	$fulltag='MID='.$member->id.'.M='.strtr($member->getFullName($langs),"-"," ");
	if (! empty($_REQUEST["tag"])) { $tag=$_REQUEST["tag"]; $fulltag.='.TAG='.$_REQUEST["tag"]; }
	$fulltag=dol_string_unaccent($fulltag);

	// Creditor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Creditor");
    print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$creditor.'</b>';
    print '<input type="hidden" name="creditor" value="'.$creditor.'">';
    print '</td></tr>'."\n";

	// Debitor
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Member");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$member->getFullName($langs).'</b>';

	// Object
	$var=!$var;
	$text='<b>'.$langs->trans("PaymentSubscription").'</b>';
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Designation");
	print '</td><td class="CTableRow'.($var?'1':'2').'">'.$text;
	print '<input type="hidden" name="source" value="'.GETPOST("source",'alpha').'">';
	print '<input type="hidden" name="ref" value="'.$member->ref.'">';
	print '</td></tr>'."\n";

	if ($member->last_subscription_date || $member->last_subscription_amount)
	{
		// Last subscription date
		$var=!$var;
		print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionDate");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.dol_print_date($member->last_subscription_date,'day');
		print '</td></tr>'."\n";

		// Last subscription amount
		$var=!$var;
		print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("LastSubscriptionAmount");
		print '</td><td class="CTableRow'.($var?'1':'2').'">'.price($member->last_subscription_amount);
		print '</td></tr>'."\n";

		if (empty($amount) && ! GETPOST('newamount')) $_GET['newamount']=$member->last_subscription_amount;
	}

	// Amount
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("Amount");
	if (empty($amount)) print ' ('.$langs->trans("ToComplete").')';
	print '</td><td class="CTableRow'.($var?'1':'2').'">';
	if (empty($amount) || ! is_numeric($amount)) print '<input class="flat" size=8 type="text" name="newamount" value="'.$_REQUEST["newamount"].'">';
	else {
		print '<b>'.price($amount).'</b>';
        print '<input type="hidden" name="amount" value="'.$amount.'">';
		print '<input type="hidden" name="newamount" value="'.$amount.'">';
	}
	// Currency
	print ' <b>'.$langs->trans("Currency".$currency).'</b>';
	print '<input type="hidden" name="currency" value="'.$currency.'">';
	print '</td></tr>'."\n";

	// Tag
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("PaymentCode");
	print '</td><td class="CTableRow'.($var?'1':'2').'"><b>'.$fulltag.'</b>';
	print '<input type="hidden" name="tag" value="'.$tag.'">';
	print '<input type="hidden" name="fulltag" value="'.$fulltag.'">';
	print '</td></tr>'."\n";

	// EMail
	$var=!$var;
	print '<tr><td class="CTableRow'.($var?'1':'2').'">'.$langs->trans("YourEMail");
	print ' ('.$langs->trans("ToComplete").')';
    $email=$member->client->email;
    $email=(GETPOST("EMAIL")?GETPOST("EMAIL"):(isValidEmail($email)?$email:''));
	print '</td><td class="CTableRow'.($var?'1':'2').'"><input class="flat" type="text" name="EMAIL" size="48" value="'.$email.'"></td></tr>'."\n";
}




if (! $found && ! $mesg) $mesg=$langs->trans("ErrorBadParameters");

if ($mesg) print '<tr><td align="center" colspan="2"><br><div class="warning">'.$mesg.'</div></td></tr>'."\n";

print '</table>'."\n";
print "\n";

if ($found && ! $error)	// We are in a management option and no error
{
	print '<br><input class="button" type="submit" name="dopayment" value="'.$langs->trans("PayBoxDoPayment").'">';
	//print '<tr><td align="center" colspan="2">'.$langs->trans("YouWillBeRedirectedOnPayBox").'...</td></tr>';
}
else
{
	dol_print_error_email();
}

print '</td></tr>'."\n";

print '</table>'."\n";
print '</form>'."\n";
print '</center>'."\n";
print '<br>';


html_print_paybox_footer($mysoc,$langs);

$db->close();

llxFooterPayBox('$Date: 2010/12/01 21:42:11 $ - $Revision: 1.52 $');
?>
