<?php
eq(-297993600,\org\rhaco\lang\Date::parse_date("1960-07-23 05:00:00+05:00"));
eq("1960-07-23 09:00:00",date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date("1960-07-23 05:00:00+05:00")));
eq("1976-07-23 09:00:00",date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date("1976-07-23 05:00:00+05:00")));
eq("2005-08-15 09:52:01",date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date("2005-08-15T01:52:01+0100")));
eq("2005-08-15 10:01:01",date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date("Mon, 15 Aug 2005 01:01:01 UTC")));
eq(null,\org\rhaco\lang\Date::parse_date(null));
eq(null,\org\rhaco\lang\Date::parse_date(0));
eq(null,\org\rhaco\lang\Date::parse_date(""));
eq("2005-03-02 00:00:00",date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date("2005/02/30 00:00:00")));
eq(date("Y-m-d H:i:s",time()),date("Y-m-d H:i:s",\org\rhaco\lang\Date::parse_date(time())));
