<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/lib/images.lib.php
 *  \brief		Ensemble de fonctions de base de traitement d'images
 * 	\version	$Id: images.lib.php,v 1.19 2010/08/20 19:19:24 eldy Exp $
 */


/**
 *    	\brief     	Return size of image file on disk (Supported extensions are gif, jpg, png and bmp)
 * 		\param		$file		Full path name of file
 * 		\return		Array		array('width'=>width, 'height'=>height)
 */
function dol_getImageSize($file)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

	$ret=array();

	if (image_format_supported($file) < 0) return $ret;

	$fichier = realpath($file); 	// Chemin canonique absolu de l'image
	$dir = dirname($file); 			// Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$ret['width']=$infoImg[0]; // Largeur de l'image
	$ret['height']=$infoImg[1]; // Hauteur de l'image

	return $ret;
}


/**
 *    	\brief     	Resize or crop an image file (Supported extensions are gif, jpg, png and bmp)
 *    	\param     	file           	Path of file to resize/crop
 * 		\param		mode			0=Resize, 1=Crop
 *    	\param     	newWidth       	Largeur maximum que dois faire l'image destination (0=keep ratio)
 *    	\param     	newHeight      	Hauteur maximum que dois faire l'image destination (0=keep ratio)
 * 		\param		src_x			Position of croping image in source image (not use if mode=0)
 * 		\param		src_y			Position of croping image in source image (not use if mode=0)
 *		\return		int				File name if OK, error message if KO
 */
function dol_imageResizeOrCrop($file, $mode, $newWidth, $newHeight, $src_x=0, $src_y=0)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

	global $conf,$langs;

	dol_syslog("dol_imageResizeOrCrop file=".$file." mode=".$mode." newWidth=".$newWidth." newHeight=".$newHeight." src_x=".$src_x." src_y=".$src_y);

	// Clean parameters
	$file=trim($file);

	// Check parameters
	if (! $file)
	{
		// Si le fichier n'a pas ete indique
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passe en parametre n'existe pas
		return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
		return 'This filename '.$file.' does not seem to be an image filename.';
	}
	elseif(!is_numeric($newWidth) && !is_numeric($newHeight))
	{
		return 'Wrong value for parameter newWidth or newHeight';
	}
	elseif ($mode == 0 && $newWidth <= 0 && $newHeight <= 0)
	{
		return 'At least newHeight or newWidth must be defined for resizing';
	}
	elseif ($mode == 1 && ($newWidth <= 0 || $newHeight <= 0))
	{
		return 'Both newHeight or newWidth must be defined for croping';
	}

	$fichier = realpath($file); 	// Chemin canonique absolu de l'image
	$dir = dirname($file); 			// Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	if ($mode == 0)	// If resize, we check parameters
	{
		if ($newWidth  <= 0)
		{
			$newWidth=intval(($newHeight / $imgHeight) * $imgWidth);	// Keep ratio
		}
		if ($newHeight <= 0)
		{
			$newHeight=intval(($newWidth / $imgWidth) * $imgHeight);	// Keep ratio
		}
	}

	$imgfonction='';
	switch($infoImg[2])
	{
		case 1:	// IMG_GIF
			$imgfonction = 'imagecreatefromgif';
			break;
		case 2:	// IMG_JPG
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case 3:	// IMG_PNG
			$imgfonction = 'imagecreatefrompng';
			break;
		case 4:	// IMG_WBMP
			$imgfonction = 'imagecreatefromwbmp';
			break;
	}
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Resize not possible. This PHP does not support GD functions '.$imgfonction;
		}
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif';	// File name extension of image
			$newquality='NU';	// Quality is not used for this format
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg';
			$newquality=100;	// % quality maximum
			break;
		case 3:	// Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			$newquality=0;		// No compression (0-9)
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			$newquality='NU';	// Quality is not used for this format
			break;
	}

	// Create empty image
	if ($infoImg[2] == 1)
	{
		// Compatibilite image GIF
		$imgThumb = imagecreate($newWidth, $newHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($newWidth, $newHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
			break;
		case 2:	// Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
		case 3:	// Png
			imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
			break;
		case 4:	// Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dol_syslog("dol_imageResizeOrCrop: convert image from ($imgWidth x $imgHeight) at position ($src_x x $src_y) to ($newWidth x $newHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgThumb, $img, 0, 0, $src_x, $src_y, $newWidth, $newHeight, ($mode==0?$imgWidth:$newWidth), ($mode==0?$imgHeight:$newHeight)); // Insere l'image de base redimensionnee

	$imgThumbName = $file;

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($infoImg[2])
	{
		case 1:	// Gif
			imagegif($imgThumb, $imgThumbName);
			break;
		case 2:	// Jpg
			imagejpeg($imgThumb, $imgThumbName, $newquality);
			break;
		case 3:	// Png
			imagepng($imgThumb, $imgThumbName, $newquality);
			break;
		case 4:	// Bmp
			image2wmp($imgThumb, $imgThumbName);
			break;
	}

	// Set permissions on file
	if (! empty($conf->global->MAIN_UMASK)) @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

	// Free memory
	imagedestroy($imgThumb);

	clearstatcache();	// File was replaced by a modified one, so we clear file caches.

	return $imgThumbName;
}


/**
 *    	\brief     Create 2 thumbnails from an image file: one small and one mini (Supported extensions are gif, jpg, png and bmp)
 *    	\param     file           	Chemin du fichier image a redimensionner
 *    	\param     maxWidth       	Largeur maximum que dois faire la miniature (-1=unchanged, 160 par defaut)
 *    	\param     maxHeight      	Hauteur maximum que dois faire l'image (-1=unchanged, 120 par defaut)
 *    	\param     extName        	Extension pour differencier le nom de la vignette
 *    	\param     quality        	Quality of compression (0=worst, 100=best)
 *      \param     targetformat     New format of target (1,2,3,4 or 0 to keep old format)
 *    	\return    string			Full path of thumb
 *		\remarks					With file=myfile.jpg -> myfile_small.jpg
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50, $outdir='thumbs', $targetformat=0)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

	global $conf,$langs;

	dol_syslog("vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality." targetformat=".$targetformat);

	// Clean parameters
	$file=trim($file);

	// Check parameters
	if (! $file)
	{
		// Si le fichier n'a pas ete indique
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passe en parametre n'existe pas
		return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
		return 'This file '.$file.' does not seem to be an image format file name.';
	}
	elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1){
		// Si la largeur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		return 'Wrong value for parameter maxWidth';
	}
	elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1){
		// Si la hauteur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		return 'Wrong value for parameter maxHeight';
	}

	$fichier = realpath($file); 	// Chemin canonique absolu de l'image
	$dir = dirname($file); 			// Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	if ($maxWidth  == -1) $maxWidth=$infoImg[0];	// If size is -1, we keep unchanged
	if ($maxHeight == -1) $maxHeight=$infoImg[1];	// If size is -1, we keep unchanged

	// Si l'image est plus petite que la largeur et la hauteur max, on ne cree pas de vignette
	if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight)
	{
		// On cree toujours les vignettes
		dol_syslog("File size is smaller than thumb size",LOG_DEBUG);
		//return 'Le fichier '.$file.' ne necessite pas de creation de vignette';
	}

	$imgfonction='';
	switch($infoImg[2])
	{
		case 1:	// IMG_GIF
			$imgfonction = 'imagecreatefromgif';
			break;
		case 2:	// IMG_JPG
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case 3:	// IMG_PNG
			$imgfonction = 'imagecreatefrompng';
			break;
		case 4:	// IMG_WBMP
			$imgfonction = 'imagecreatefromwbmp';
			break;
	}
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Creation de vignette impossible. Ce PHP ne supporte pas les fonctions du module GD '.$imgfonction;
		}
	}

	// On cree le repertoire contenant les vignettes
	$dirthumb = $dir.($outdir?'/'.$outdir:''); 	// Chemin du dossier contenant les vignettes
	create_exdir($dirthumb);

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif'; // Extension de l'image
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg'; // Extension de l'image
			break;
		case 3:	// Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			break;
	}

	// Initialisation des dimensions de la vignette si elles sont superieures a l'original
	if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
	if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

	$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if($whFact < $imgWhFact){
		// Si largeur determinante
		$thumbWidth  = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	} else {
		// Si hauteur determinante
		$thumbHeight = $maxHeight;
		$thumbWidth  = $thumbHeight * $imgWhFact;
	}
	$thumbHeight=round($thumbHeight);
	$thumbWidth=round($thumbWidth);

    // Define target format
    if (empty($targetformat)) $targetformat=$infoImg[2];

	// Create empty image
	if ($targetformat == 1)
	{
		// Compatibilite image GIF
		$imgThumb = imagecreate($thumbWidth, $thumbHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($targetformat)
	{
		case 1:	// Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
            $extImgTarget = '.gif';
            $newquality='NU';
            break;
		case 2:	// Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = '.jpg';
            $newquality=$quality;
            break;
		case 3:	// Png
			imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
            $extImgTarget = '.png';
            $newquality=$quality-100;
            $newquality=round(abs($quality-100)*9/100);
            break;
		case 4:	// Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = '.bmp';
            $newquality='NU';
            break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dol_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee

	$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$file);	// On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	$imgThumbName = $dirthumb.'/'.$fileName.$extName.$extImgTarget; // Chemin complet du fichier de la vignette

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($targetformat)
	{
		case 1:	// Gif
			imagegif($imgThumb, $imgThumbName);
			break;
		case 2:	// Jpg
			imagejpeg($imgThumb, $imgThumbName, $newquality);
			break;
		case 3:	// Png
			imagepng($imgThumb, $imgThumbName, $newquality);
			break;
		case 4:	// Bmp
			image2wmp($imgThumb, $imgThumbName);
			break;
	}

	// Set permissions on file
	if (! empty($conf->global->MAIN_UMASK)) @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

	// Free memory
	imagedestroy($imgThumb);

	return $imgThumbName;
}


/**
 *	\brief	This function returns the html for the moneymeter.
 *	\param	actualValue: amount of actual money
 *	\param	pendingValue: amount of money of pending memberships
 *	\param	intentValue: amount of intended money (that's without the amount of actual money)
 *	\return thermometer htmlLegenda
 */
function moneyMeter($actualValue=0, $pendingValue=0, $intentValue=0)
{
	global $langs;

	// variables
	$height="200";
	$maximumValue=125000;

	$imageDir = "http://eucd.info/images/therm/";

	$imageTop = $imageDir . "therm_top.png";
	$imageMiddleActual = $imageDir . "therm_actual.png";
	$imageMiddlePending = $imageDir . "therm_pending.png";
	$imageMiddleIntent = $imageDir . "therm_intent.png";
	$imageMiddleGoal = $imageDir . "therm_goal.png";
	$imageIndex = $imageDir . "therm_index.png";
	$imageBottom =  $imageDir . "therm_bottom.png";
	$imageColorActual = $imageDir . "therm_color_actual.png";
	$imageColorPending = $imageDir . "therm_color_pending.png";
	$imageColorIntent = $imageDir . "therm_color_intent.png";

	$htmlThermTop = '
        <!-- Thermometer Begin -->
        <table cellpadding="0" cellspacing="4" border="0">
        <tr><td>
        <table cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td colspan="2"><img src="' . $imageTop . '" width="58" height="6" border="0"></td>
          </tr>
          <tr>
            <td>
              <table cellpadding="0" cellspacing="0" border="0">';

	$htmlSection = '
          <tr><td><img src="{image}" width="26" height="{height}" border="0"></td></tr>';

	$htmlThermbottom = '
              </table>
            </td>
            <td><img src="' . $imageIndex . '" width="32" height="200" border="0"></td>
          </tr>
          <tr>
            <td colspan="2"><img src="' . $imageBottom . '" width="58" height="32" border="0"></td>
          </tr>
        </table>
        </td>
      </tr></table>';

	// legenda

	$legendaActual = "&euro; " . round($actualValue);
	$legendaPending = "&euro; " . round($pendingValue);
	$legendaIntent = "&euro; " . round($intentValue);
	$legendaTotal = "&euro; " . round($actualValue + $pendingValue + $intentValue);
	$htmlLegenda = '

        <table cellpadding="0" cellspacing="0" border="0">
          <tr><td><img src="' . $imageColorActual . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif"><b>'.$langs->trans("Paid").':<br>' . $legendaActual . '</b></font></td></tr>
          <tr><td><img src="' . $imageColorPending . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">'.$langs->trans("Waiting").':<br>' . $legendaPending . '</font></td></tr>
          <tr><td><img src="' . $imageColorIntent . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">'.$langs->trans("Promesses").':<br>' . $legendaIntent . '</font></td></tr>
          <tr><td>&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Total:<br>' . $legendaTotal . '</font></td></tr>
        </table>

        <!-- Thermometer End -->';

	// check and edit some values

	$error = 0;
	if ( $maximumValue <= 0 || $height <= 0 || $actualValue < 0 || $pendingValue < 0 || $intentValue < 0)
	{
		return "The money meter could not be processed<br>\n";
	}
	if ( $actualValue > $maximumValue )
	{
		$actualValue = $maximumValue;
		$pendingValue = 0;
		$intentValue = 0;
	}
	else
	{
		if ( ($actualValue + $pendingValue) > $maximumValue )
		{
	  $pendingValue = $maximumValue - $actualValue;
	  $intentValue = 0;
		}
		else
		{
	  if ( ($actualValue + $pendingValue + $intentValue) > $maximumValue )
	  {
	  	$intentValue = $maximumValue - $actualValue - $pendingValue;
	  }
		}
	}

	// start writing the html (from bottom to top)

	// bottom
	$thermometer = $htmlThermbottom;

	// actual
	$sectionHeight = round(($actualValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $htmlSection;
		$section = str_replace("{image}", $imageMiddleActual, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// pending
	$sectionHeight = round(($pendingValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $htmlSection;
		$section = str_replace("{image}", $imageMiddlePending, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// intent
	$sectionHeight = round(($intentValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $htmlSection;
		$section = str_replace("{image}", $imageMiddleIntent, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// goal
	$sectionHeight = $height- $totalHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $htmlSection;
		$section = str_replace("{image}", $imageMiddleGoal, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// top
	$thermometer = $htmlThermTop . $thermometer;

	return $thermometer . $htmlLegenda;
}

?>
