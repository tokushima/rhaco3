<?php
eq(time()+1,\org\rhaco\lang\Date::add(time(),1,0));
eq(time()+60,\org\rhaco\lang\Date::add(time(),0,1));
eq(time()+3600,\org\rhaco\lang\Date::add(time(),0,0,1));
eq(time()-1,\org\rhaco\lang\Date::add(time(),-1,0));
eq(time()-60,\org\rhaco\lang\Date::add(time(),0,-1));
eq(time()-3600,\org\rhaco\lang\Date::add(time(),0,0,-1));
