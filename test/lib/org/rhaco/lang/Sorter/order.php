<?php
eq("name",\org\rhaco\lang\Sorter::order("name",null));
eq("-name",\org\rhaco\lang\Sorter::order("-name",null));
eq("name",\org\rhaco\lang\Sorter::order("name","id"));
eq("name",\org\rhaco\lang\Sorter::order("name","-id"));
eq("-name",\org\rhaco\lang\Sorter::order("-name","id"));
eq("-name",\org\rhaco\lang\Sorter::order("-name","-id"));

eq("-name",\org\rhaco\lang\Sorter::order("name","name"));
eq("name",\org\rhaco\lang\Sorter::order("name","-name"));
eq("-name",\org\rhaco\lang\Sorter::order("-name","name"));
eq("name",\org\rhaco\lang\Sorter::order("-name","-name"));
