-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_osc_product.sql,v 1.1 2009/08/13 13:06:59 eldy Exp $
-- ===================================================================

CREATE TABLE llx_osc_product (
  rowid int(11) NOT NULL default '0',
  datem datetime default NULL,
  fk_product int(11) NOT NULL default '0',
  PRIMARY KEY  (rowid),
  UNIQUE KEY fk_product (fk_product)
) TYPE=InnoDB COMMENT='Table transition produit OSC - produit Dolibarr';


