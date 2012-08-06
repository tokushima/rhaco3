<?php
namespace test\model;

class TestModelLog{
	public function __construct(){
		\org\rhaco\Log::info('NEW');
	}
	public function __destruct(){
		\org\rhaco\Log::info('DEL');
	}
	public function run(){
		\org\rhaco\Log::info('RUN');
	}
}