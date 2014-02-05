<?php
eq("/abc/",\org\rhaco\net\Path::slash("/abc/",null,null));
eq("/abc/",\org\rhaco\net\Path::slash("abc",true,true));
eq("/abc/",\org\rhaco\net\Path::slash("/abc/",true,true));
eq("abc/",\org\rhaco\net\Path::slash("/abc/",false,true));
eq("/abc",\org\rhaco\net\Path::slash("/abc/",true,false));
eq("abc",\org\rhaco\net\Path::slash("/abc/",false,false));
