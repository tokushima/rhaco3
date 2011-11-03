<?php
namespace org\rhaco\store\queue;
/**
 * キューのモデル
 * @author tokushima
 * @var serial $id
 * @var string $type
 * @var string $data
 * @var number $lock
 * @var timestamp $fin;
 * @var integer $priority
 * @var timestamp $create_date;
 */
class Model extends \org\rhaco\Object{
	protected $id;
	protected $type;
	protected $data;
	protected $lock;
	protected $fin;
	protected $priority;
	protected $create_date;
}
