<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit','max'=>1,'ignore_auto_code'=>'[0-8]']
 */
class UniqueCodeIgnore extends UniqueCode{
	protected $id;
	protected $code1;
}

