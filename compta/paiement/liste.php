<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/paiement/liste.php
 *  \ingroup    compta
 *  \brief      Page liste des paiements des factures clients
 *  \version    $Id: liste.php,v 1.47 2010/11/20 13:08:46 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

$langs->load("bills");

// Security check
$facid = isset($_GET["facid"])?$_GET["facid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture',$facid,'');

$paymentstatic=new Paiement($db);
$accountstatic=new Account($db);
$companystatic=new Societe($db);

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";




/*
 * 	View
 */

llxHeader('',$langs->trans("ListPayment"));

$form=new Form($db);

$sql = "SELECT DISTINCT p.rowid, p.datep as dp, p.amount,";
$sql.= " p.statut, p.num_paiement,";
//$sql.= " c.libelle as paiement_type,";
$sql.= " c.code as paiement_code,";
$sql.= " ba.rowid as bid, ba.label,";
$sql.= " s.rowid as socid, s.nom";
//$sql.= " f.facnumber";
$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid)
{
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
}
$sql.= " WHERE p.fk_paiement = c.id";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid)
{
    $sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
    $sql.= " AND f.fk_soc = ".$socid;
}
// Search criteria
if ($_REQUEST["search_ref"])
{
    $sql .=" AND p.rowid=".$_REQUEST["search_ref"];
}
if ($_REQUEST["search_account"])
{
    $sql .=" AND b.fk_account=".$_REQUEST["search_account"];
}
if ($_REQUEST["search_paymenttype"])
{
    $sql .=" AND c.code='".$_REQUEST["search_paymenttype"]."'";
}
if ($_REQUEST["search_amount"])
{
    $sql .=" AND p.amount=".price2num($_REQUEST["search_amount"]);
}
if ($_REQUEST["search_company"])
{
    $sql .=" AND s.nom LIKE '%".addslashes($_REQUEST["search_company"])."%'";
}

if ($_GET["orphelins"])     // Option for debugging purpose only
{
    // Paiements lies a aucune facture (pour aide au diagnostic)
    $sql = "SELECT p.rowid, p.datep as dp, p.amount,";
    $sql.= " p.statut, p.num_paiement,";
    //$sql.= " c.libelle as paiement_type";
    $sql.= " c.code as paiement_code,";
    $sql.= " s.rowid as socid, s.nom";
    $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p,";
    $sql.= " ".MAIN_DB_PREFIX."c_paiement as c";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
    $sql.= " WHERE p.fk_paiement = c.id";
    $sql.= " AND s.entity = ".$conf->entity;
    $sql.= " AND pf.rowid IS NULL";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit( $limit+1 ,$offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $paramlist='';
    $paramlist.=($_REQUEST["orphelins"]?"&orphelins=1":"");
    $paramlist.=($_REQUEST["search_ref"]?"&search_ref=".$_REQUEST["search_ref"]:"");
    $paramlist.=($_REQUEST["search_company"]?"&search_company=".$_REQUEST["search_company"]:"");
    $paramlist.=($_REQUEST["search_amount"]?"&search_amount=".$_REQUEST["search_amount"]:"");

    print_barre_liste($langs->trans("ReceivedCustomersPayments"), $page, "liste.php",$paramlist,$sortfield,$sortorder,'',$num);

    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("RefPayment"),"liste.php","p.rowid","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),"liste.php","dp","",$paramlist,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ThirdParty"),"liste.php","s.nom","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),"liste.php","c.libelle","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Account"),"liste.php","ba.label","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Invoices"),"","","",$paramlist,'align="left"',$sortfield,$sortorder);
    if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
    {
        print_liste_field_titre($langs->trans("Status"),"liste.php","p.statut","",$paramlist,'align="right"',$sortfield,$sortorder);
    }
    print "</tr>\n";

    // Lines for filters fields
    print '<tr class="liste_titre">';
    print '<td align="left">';
    print '<input class="fat" type="text" size="4" name="search_ref" value="'.$_REQUEST["search_ref"].'">';
    print '</td>';
    print '<td>&nbsp;</td>';
    print '<td align="left">';
    print '<input class="fat" type="text" size="6" name="search_company" value="'.$_REQUEST["search_company"].'">';
    print '</td>';
    print '<td>';
    $form->select_types_paiements($_REQUEST["search_paymenttype"],'search_paymenttype','',2,1,1);
    print '</td>';
    print '<td>';
    $form->select_comptes($_REQUEST["search_account"],'search_account',0,'',1);
    print '</td>';
    print '<td align="right">';
    print '<input class="fat" type="text" size="4" name="search_amount" value="'.$_REQUEST["search_amount"].'">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '</td>';
    if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
    {
        print '<td align="right">';
        print '</td>';
    }
    print "</tr>\n";

    $var=true;
    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);
        $var=!$var;
        print "<tr $bc[$var]>";

        print '<td width="40">';
        $paymentstatic->id=$objp->rowid;
        $paymentstatic->ref=$objp->rowid;
        print $paymentstatic->getNomUrl(1);
        print '</td>';

        print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day').'</td>';

        // Company
        print '<td>';
        if ($objp->socid)
        {
            $companystatic->id=$objp->socid;
            $companystatic->nom=$objp->nom;
            print $companystatic->getNomUrl(1,'',24);
        }
        else print '&nbsp;';
        print '</td>';

        print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).' '.$objp->num_paiement.'</td>';
        print '<td>';
        if ($objp->bid)
        {
            $accountstatic->id=$objp->bid;
            $accountstatic->label=$objp->label;
            print $accountstatic->getNomUrl(1);
        }
        else print '&nbsp;';
        print '</td>';
        print '<td align="right">'.price($objp->amount).'</td>';

        if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
        {
            print '<td align="right">';
            if ($objp->statut == 0) print '<a href="fiche.php?id='.$objp->rowid.'&amp;action=valide">';
            print $paymentstatic->LibStatut($objp->statut,5);
            if ($objp->statut == 0) print '</a>';
            print '</td>';
        }

        print '</tr>';

        $i++;
    }
    print "</table>\n";
    print "</form>\n";
}
else
{
    dol_print_error($db);
}

$db->close();

llxFooter('$Date: 2010/11/20 13:08:46 $ - $Revision: 1.47 $');
?>
