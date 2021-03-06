<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       compta/facture/class/paymentterm.class.php
 *      \ingroup    facture
 *      \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *		\version    $Id: paymentterm.class.php,v 1.3 2010/09/06 23:57:44 eldy Exp $
 *		\author		Put author name here
 *		\remarks	Initialy built by build_class_from_table on 2010-09-06 00:33
 */

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *      \class      PaymentTerm
 *      \brief      Class to manage payment terms records in dictionnary
 *		\remarks	Initialy built by build_class_from_table on 2010-09-06 00:33
 */
class PaymentTerm // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='c_payment_term';			//!< Id that identify managed objects
	//var $table_element='c_payment_term';	//!< Name of table without prefix where object is stored

    var $id;

	var $code;
	var $sortorder;
	var $active;
	var $libelle;
	var $libelle_facture;
	var $fdm;
	var $nbjour;
	var $decalage;




    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function PaymentTerm($DB)
    {
        $this->db = $DB;
        return 1;
    }


    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->sortorder)) $this->sortorder=trim($this->sortorder);
		if (isset($this->active)) $this->active=trim($this->active);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->libelle_facture)) $this->libelle_facture=trim($this->libelle_facture);
		if (isset($this->fdm)) $this->fdm=trim($this->fdm);
		if (isset($this->nbjour)) $this->nbjour=trim($this->nbjour);
		if (isset($this->decalage)) $this->decalage=trim($this->decalage);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(";

		$sql.= "rowid,";
		$sql.= "code,";
		$sql.= "sortorder,";
		$sql.= "active,";
		$sql.= "libelle,";
		$sql.= "libelle_facture,";
		$sql.= "fdm,";
		$sql.= "nbjour,";
		$sql.= "decalage";


        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->rowid)?'NULL':"'".$this->rowid."'").",";
		$sql.= " ".(! isset($this->code)?'NULL':"'".addslashes($this->code)."'").",";
		$sql.= " ".(! isset($this->sortorder)?'NULL':"'".$this->sortorder."'").",";
		$sql.= " ".(! isset($this->active)?'NULL':"'".$this->active."'").",";
		$sql.= " ".(! isset($this->libelle)?'NULL':"'".addslashes($this->libelle)."'").",";
		$sql.= " ".(! isset($this->libelle_facture)?'NULL':"'".addslashes($this->libelle_facture)."'").",";
		$sql.= " ".(! isset($this->fdm)?'NULL':"'".$this->fdm."'").",";
		$sql.= " ".(! isset($this->nbjour)?'NULL':"'".$this->nbjour."'").",";
		$sql.= " ".(! isset($this->decalage)?'NULL':"'".$this->decalage."'")."";


		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_payment_term");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.code,";
		$sql.= " t.sortorder,";
		$sql.= " t.active,";
		$sql.= " t.libelle,";
		$sql.= " t.libelle_facture,";
		$sql.= " t.fdm,";
		$sql.= " t.nbjour,";
		$sql.= " t.decalage";


        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->code = $obj->code;
				$this->sortorder = $obj->sortorder;
				$this->active = $obj->active;
				$this->libelle = $obj->libelle;
				$this->libelle_facture = $obj->libelle_facture;
				$this->fdm = $obj->fdm;
				$this->nbjour = $obj->nbjour;
				$this->decalage = $obj->decalage;


            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *    Return id of default payment term
     *    @return     int         <0 if KO, >0 if OK
     */
    function getDefaultId()
    {
    	global $langs;

        $ret=0;

    	$sql = "SELECT";
		$sql.= " t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.code = 'RECEP'";

    	dol_syslog(get_class($this)."::getDefaultId sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                if ($obj) $ret=$obj->rowid;
            }
            $this->db->free($resql);
            return $ret;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::getDefaultId ".$this->error, LOG_ERR);
            return -1;
        }
    }


	/**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->sortorder)) $this->sortorder=trim($this->sortorder);
		if (isset($this->active)) $this->active=trim($this->active);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->libelle_facture)) $this->libelle_facture=trim($this->libelle_facture);
		if (isset($this->fdm)) $this->fdm=trim($this->fdm);
		if (isset($this->nbjour)) $this->nbjour=trim($this->nbjour);
		if (isset($this->decalage)) $this->decalage=trim($this->decalage);



		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_payment_term SET";

		$sql.= " code=".(isset($this->code)?"'".addslashes($this->code)."'":"null").",";
		$sql.= " sortorder=".(isset($this->sortorder)?$this->sortorder:"null").",";
		$sql.= " active=".(isset($this->active)?$this->active:"null").",";
		$sql.= " libelle=".(isset($this->libelle)?"'".addslashes($this->libelle)."'":"null").",";
		$sql.= " libelle_facture=".(isset($this->libelle_facture)?"'".addslashes($this->libelle_facture)."'":"null").",";
		$sql.= " fdm=".(isset($this->fdm)?$this->fdm:"null").",";
		$sql.= " nbjour=".(isset($this->nbjour)?$this->nbjour:"null").",";
		$sql.= " decalage=".(isset($this->decalage)?$this->decalage:"null")."";


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
	 *   \brief      Delete object in database
     *	\param      user        	User that delete
     *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *	\return		int				<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_payment_term";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.

		        //// Call triggers
		        //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     		Id of object to clone
	 * 	 	\return		int				New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new PaymentTerm($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{



		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->code='';
		$this->sortorder='';
		$this->active='';
		$this->libelle='';
		$this->libelle_facture='';
		$this->fdm='';
		$this->nbjour='';
		$this->decalage='';


	}

}
?>
