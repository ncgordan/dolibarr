-- ===================================================================
-- Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_bookmark.sql,v 1.2 2010/06/06 16:04:25 eldy Exp $
-- ===================================================================

create table llx_bookmark
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc      integer,
  fk_user     integer NOT NULL,
  dateb       datetime,
  url         varchar(128) NOT NULL,
  target      varchar(16),
  title       varchar(64),
  favicon     varchar(24),
  position    integer DEFAULT 0
)type=innodb;
