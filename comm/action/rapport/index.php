<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/comm/action/rapport/index.php
 *      \ingroup    commercial
 *		\brief      Page with reports of actions
 *		\version    $Id: index.php,v 1.44 2010/11/20 13:08:49 eldy Exp $
 */

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/action/rapport.pdf.php");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datep";

// Security check
$socid = GETPOST("socid");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', $socid, '', 'myactions');



/*
 * Actions
 */
if ($_GET["action"] == 'builddoc')
{
	$cat = new CommActionRapport($db, $_GET["month"], $_GET["year"]);
	$result=$cat->write_file(GETPOST("id"));
	if ($result < 0)
	{
		$mesg=$cat->error;
	}
}


/*
 * View
 */

llxHeader();

$sql = "SELECT count(*) as cc,";
$sql.= " date_format(a.datep, '%m/%Y') as df,";
$sql.= " date_format(a.datep, '%m') as month,";
$sql.= " date_format(a.datep, '%Y') as year";
$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE a.fk_user_author = u.rowid";
if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
{
	$sql.= " AND u.entity in (0, ".$conf->entity.")";
}
$sql.= " AND percent = 100";
$sql.= " GROUP BY year, month, df";
$sql.= " ORDER BY year DESC, month DESC, df DESC";
$sql.= $db->plimit($limit+1,$offset);

//print $sql;
dol_syslog("select sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print_barre_liste($langs->trans("DoneActions"), $page, "index.php",'',$sortfield,$sortorder,'',$num);

	if ($mesg) print $mesg.'<br>';

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td align="center">'.$langs->trans("Nb").'</td>';
	print '<td>'.$langs->trans("Action").'</td>';
	print '<td align="center">'.$langs->trans("PDF").'</td>';
	print '<td align="center">'.$langs->trans("Date").'</td>';
	print '<td align="center">'.$langs->trans("Size").'</td>';
	print "</tr>\n";
	$var=true;
	while ($i < min($num,$limit))
	{
		$obj=$db->fetch_object($resql);

		if ($obj)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			print "<td>".$obj->df."</td>\n";
			print '<td align="center">'.$obj->cc.'</td>';

			print '<td>';
			print '<a href="index.php?action=builddoc&amp;page='.$page.'&amp;month='.$obj->month.'&amp;year='.$obj->year.'">'.img_file_new().'</a>';
			print '</td>';

			$name = "actions-".$obj->month."-".$obj->year.".pdf";
			$relativepath= $name;
			$file = $conf->agenda->dir_temp."/".$name;

			if (file_exists($file))
			{
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/document.php?page='.$page.'&amp;file='.urlencode($relativepath).'&amp;modulepart=actionsreport">'.img_pdf().'</a></td>';
				print '<td align="center">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
				print '<td align="center">'.dol_print_size(dol_filesize($file)).'</td>';
			}
			else {
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
			}

			print "</tr>\n";
		}
		$i++;
	}
	print "</table>";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter('$Date: 2010/11/20 13:08:49 $ - $Revision: 1.44 $');
?>
