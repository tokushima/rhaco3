<?php
use \org\rhaco\store\db\Q;

\test\model\DateFormat::create_table();
\test\model\DateTime::find_delete();

$date = strtotime('2015/07/04 12:34:56');
$obj = new \test\model\DateFormat();
$obj->ts($date);
$obj->num(10);
$obj->save();

foreach(\test\model\DateFormat::find() as $o){
	eq(date('Y/m/d H:i:s',$date),$o->fm_ts());
}

foreach(\test\model\DateFormat::find(Q::date_format('ts','Ym')) as $o){
	eq(date('Y/m/01 00:00:00',$date),$o->fm_ts());
}

$date = strtotime('2015/07/01 12:34:56');
$obj = new \test\model\DateFormat();
$obj->ts($date);
$obj->num(20);
$obj->save();

$date = strtotime('2015/07/30 12:34:56');
$obj = new \test\model\DateFormat();
$obj->ts($date);
$obj->num(30);
$obj->save();

foreach(\test\model\DateFormat::find(Q::date_format('ts','Ym')) as $o){
	eq(date('Y/m/01 00:00:00',$date),$o->fm_ts());
}

$date = strtotime('2015/08/30 12:34:56');
$obj = new \test\model\DateFormat();
$obj->ts($date);
$obj->num(40);
$obj->save();

$date = strtotime('2015/08/30 12:34:56');
$obj = new \test\model\DateFormat();
$obj->ts($date);
$obj->num(20);
$obj->save();

eq(4,sizeof(\test\model\DateFormat::find_sum_by('num','ts')));

eq(2,sizeof(\test\model\DateFormat::find_sum_by('num','ts',Q::date_format('ts','Ym'))));

foreach(\test\model\DateFormat::find_sum_by('num','ts',Q::date_format('ts','Ym')) as $k => $num){
	eq('2015',date('Y',$k));
	eq(60,$num);
}

foreach(\test\model\DateFormat::find_sum_by('num','ts',Q::date_format('ts','m')) as $k => $num){
	eq('2000',date('Y',$k));
	eq(60,$num);
}
