<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'alpha']
 * @var string $code2 @['auto_code_add'=>true,'max'=>10,'ctype'=>'alpha']
 * @var string $code3 @['auto_code_add'=>true,'max'=>40,'ctype'=>'alpha']
 */
class UniqueCodeAlpha extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}
