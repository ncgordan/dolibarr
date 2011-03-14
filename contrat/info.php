<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/contrat/info.php
        \ingroup    contrat
		\brief      Page des informations d'un contrat
		\version    $Id: info.php,v 1.24 2010/04/28 08:35:58 grandoc Exp $
*/

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$langs->load("contracts");

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');


/*
* View
*/

llxHeader();

$contrat = new Contrat($db);
$contrat->fetch($_GET["id"]);
$contrat->info($_GET["id"]);

$head = contract_prepare_head($contrat);

dol_fiche_head($head, 'info', $langs->trans("Contract"), 0, 'contract');


print '<table width="100%"><tr><td>';
dol_print_object_info($contrat);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date: 2010/04/28 08:35:58 $ - $Revision: 1.24 $');
?>
