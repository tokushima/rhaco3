<?php
eq("2007/07/19",\org\rhaco\lang\Date::format("2007-07-18T16:16:31+00:00","Y/m/d"));
eq("2007-07-18T16:16:31Z",\org\rhaco\lang\Date::format_atom(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thu, 19 Jul 2007 01:16:31 JST",\org\rhaco\lang\Date::format_cookie(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("2008/04/07",\org\rhaco\lang\Date::format_date(20080407));
eq("208/04/07",\org\rhaco\lang\Date::format_date(2080407));
eq("2007/07/19 01:16:31 (Thu)",\org\rhaco\lang\Date::format_full(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("2007-07-19T01:16:31+0900",\org\rhaco\lang\Date::format_ISO8601(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("01:01:01",\org\rhaco\lang\Date::format_time(3661));
eq("00:01:01",\org\rhaco\lang\Date::format_time(61));
eq("300:01:01",\org\rhaco\lang\Date::format_time(1080061));
eq("00:00:00",\org\rhaco\lang\Date::format_time(0));
eq("Thu, 19 Jul 2007 01:16:31 JST",\org\rhaco\lang\Date::format_RFC822(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thursday, 19-Jul-07 01:16:31 JST",\org\rhaco\lang\Date::format_RFC850(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thursday, 19-Jul-07 01:16:31 JST",\org\rhaco\lang\Date::format_RFC1036(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thu, 19 Jul 2007 01:16:31 JST",\org\rhaco\lang\Date::format_RFC1123(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thu, 19 Jul 2007 01:16:31 +0900",\org\rhaco\lang\Date::format_RFC2822(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("Thu, 19 Jul 2007 01:16:31 JST",\org\rhaco\lang\Date::format_rss(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("2007-07-19T01:16:31+09:00",\org\rhaco\lang\Date::format_w3c(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));
eq("D:20070719011631+09'00'",\org\rhaco\lang\Date::format_pdf(\org\rhaco\lang\Date::parse_date("2007-07-18T16:16:31+00:00")));


