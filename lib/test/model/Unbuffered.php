<?php
namespace test\model;
use \org\rhaco\store\db\Q;
/**
 * create table はされるはず
 * @var serial $id
 * @var string $value
 * @author tokushima
 */
class Unbuffered extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
}
