<?php
namespace test\model;
/**
 * @class @['table'=>'replication','update'=>false,'create'=>false,'delete'=>false]
 * @var serial $id
 * @var string $value
 */
class ReplicationSlave extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
}
