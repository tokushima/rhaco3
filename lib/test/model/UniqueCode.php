<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true]
 * @var string $code2 @['auto_code_add'=>true,'max'=>10]
 * @var string $code3 @['auto_code_add'=>true,'max'=>40]
 */
class UniqueCode extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}
