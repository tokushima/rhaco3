<?php
eq(20080401,\org\rhaco\lang\Date::parse_int("2008/04/01"));
eq(20080401,\org\rhaco\lang\Date::parse_int("2008-04-01"));
eq(20080401,\org\rhaco\lang\Date::parse_int("2008-04/01"));
eq(20080401,\org\rhaco\lang\Date::parse_int("2008-4-1"));
eq(2080401,\org\rhaco\lang\Date::parse_int("2080401"));
eq(null,\org\rhaco\lang\Date::parse_int("2008A04A01"));
eq(intval(date("Ymd")),\org\rhaco\lang\Date::parse_int(time()));
eq(19000401,\org\rhaco\lang\Date::parse_int("1900-4-1"));
eq(19001010,\org\rhaco\lang\Date::parse_int("1900/10/10"));
eq(10101,\org\rhaco\lang\Date::parse_int("1/1/1"));
eq(19601110,\org\rhaco\lang\Date::parse_int("1960/11/10"));
