-- ===================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2010 Juanjo Menent        <jmenent@2byte.es>
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
-- $Id: llx_commande_fournisseur.sql,v 1.2 2010/03/02 19:25:30 simnandez Exp $
-- ===================================================================

create table llx_commande_fournisseur
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  tms                 timestamp,
  fk_soc              integer NOT NULL,
  ref                 varchar(30) NOT NULL,          -- order number
  entity              integer DEFAULT 1 NOT NULL,	 -- multi company id
  ref_supplier        varchar(30),

  fk_projet           integer DEFAULT 0,             -- projet auquel est rattache la commande
  date_creation       datetime,                      -- date de creation 
  date_valid          datetime,                      -- date de validation
  date_cloture        datetime,                      -- date de cloture
  date_commande       date,                          -- date de la commande
  fk_user_author      integer,                       -- createur de la commande
  fk_user_valid       integer,                       -- valideur de la commande
  fk_user_cloture     integer,                       -- auteur cloture
  source              smallint NOT NULL,
  fk_statut           smallint  default 0,
  amount_ht           real      default 0,
  remise_percent      real      default 0,
  remise              real      default 0,
  tva                 double(24,8)      default 0,
  localtax1           double(24,8)      default 0,
  localtax2           double(24,8)      default 0,
  total_ht            double(24,8)      default 0,
  total_ttc           double(24,8)      default 0,
  note                text,
  note_public         text,
  model_pdf           varchar(50),
  fk_methode_commande integer default 0
)type=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company order
-- 2 : second company order
-- 3 : etc...
--