<?php

eq(true,\org\rhaco\lang\Date::eq("2008/03/31","2008/03/31"));
eq(false,\org\rhaco\lang\Date::eq("2008/03/31","2008/03/30"));


eq(true,\org\rhaco\lang\Date::gt("2008/03/31","2008/03/30"));
eq(false,\org\rhaco\lang\Date::gt("2008/03/30","2008/03/31"));
eq(false,\org\rhaco\lang\Date::gt("2008/03/31","2008/03/31"));


eq(true,\org\rhaco\lang\Date::gte("2008/03/31","2008/03/30"));
eq(false,\org\rhaco\lang\Date::gte("2008/03/30","2008/03/31"));
eq(true,\org\rhaco\lang\Date::gte("2008/03/31","2008/03/31"));

