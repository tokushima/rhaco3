<?php
namespace org\rhaco\store\db\exception;
/**
 * Daoの例外
 * @author tokushima
 */
class NoRowsAffectedException extends \org\rhaco\Exception{
	protected $message = 'no rows affected';
}
