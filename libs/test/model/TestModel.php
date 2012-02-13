<?php
namespace test\model;
/**
 * @var serial $id
 * @var number $number
 * @bar integer $integer
 * @var string $string
 * @var text $text
 * @var timestamp $timestamp
 * @var boolean $boolean
 * @author tokushima
 */
class TestModel extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $number;
	protected $integer;
	protected $string;
	protected $text;
	protected $timestamp;
	protected $boolean;
}