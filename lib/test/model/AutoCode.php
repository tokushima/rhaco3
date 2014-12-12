<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $code @['auto_code_add'=>true,'max'=>1]
 * @author tokushima
 *
 */
class AutoCode extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $code;
}
