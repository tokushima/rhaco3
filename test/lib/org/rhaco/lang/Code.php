<?php
$codebase = '0123456789ABC';


$max = \org\rhaco\lang\Code::max($codebase,5);
$maxcode = \org\rhaco\lang\Code::encode($codebase,$max);
eq('CCCCC',$maxcode);
eq($max,\org\rhaco\lang\Code::decode($codebase, $maxcode));


$min = \org\rhaco\lang\Code::min($codebase,5);
$mincode = \org\rhaco\lang\Code::encode($codebase,$min);
eq('10000',$mincode);
eq($min,\org\rhaco\lang\Code::decode($codebase, $mincode));


eq(3,strlen(\org\rhaco\lang\Code::rand($codebase,3)));


