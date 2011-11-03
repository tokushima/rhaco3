<?php
namespace org\rhaco\flow\parts\Board;
/**
 * @var serial $id
 * @var string $name
 * @var text $comment @{"require":true}
 * @var timestamp $created_at @{"auto_now_add":true}
 * @class @{"table":"board"}
 */
class Model extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $name;
	protected $comment;
	protected $created_at;
}
