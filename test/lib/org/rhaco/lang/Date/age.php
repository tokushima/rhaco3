<?php
eq(5,\org\rhaco\lang\Date::age(20001010,\org\rhaco\lang\Date::parse_date("2005/01/01")));
eq(6,\org\rhaco\lang\Date::age(20001010,\org\rhaco\lang\Date::parse_date("2005/10/10")));
eq(5,\org\rhaco\lang\Date::age(20001010,\org\rhaco\lang\Date::parse_date("2005/10/9")));
eq(5,\org\rhaco\lang\Date::age(20001010,\org\rhaco\lang\Date::parse_date("2005/10/11")));
