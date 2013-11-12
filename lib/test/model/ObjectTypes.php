<?php
namespace test\model;
/**
 * org.rhaco.Objectのテスト用
 * @var mixed $aa
 * @var mixed $aaa
 * @var string $bb
 * @var serial $cc
 * @var number $dd
 * @var boolean $ee
 * @var timestamp $ff
 * @var time $gg
 * @var choice $hh @["choices"=>["abc","def"]]
 * @var string{} $ii
 * @var string[] $jj
 * @var email $kk
 * @var date $ll
 * @var alnum $mm
 * @var intdate $nn
 * @var integer $oo
 * @var text $pp
 * @var number $qq @["decimal_places"=>2]
 *
 */
class ObjectTypes extends \org\rhaco\Object{
	protected $aa;
	protected $aaa;
	protected $bb;
	protected $cc;
	protected $dd;
	protected $ee;
	protected $ff;
	protected $gg;
	protected $hh;
	protected $ii;
	protected $jj;
	protected $kk;
	protected $ll;
	protected $mm;
	protected $nn;
	protected $oo;
	protected $pp;
	protected $qq;
		
	protected function __set_aaa__($value){
		$this->aaa = (($value === null) ? "" : "ABC").$value;
	}
	protected function __get_aaa__(){
		return empty($this->aaa) ? null : "[".$this->aaa."]";
	}
}