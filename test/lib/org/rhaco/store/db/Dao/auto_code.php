<?php
/**
 * ユニークなコードが生成できなくなるとGenerateUniqueCodeRetryLimitOverExceptionが発生する
 */
\test\model\AutoCode::create_table();
\test\model\AutoCode::find_delete();

try{
	for($i=0;$i<100;$i++){
		$obj = new \test\model\AutoCode();
		$obj->save();
	}
	failure($i);
}catch(\org\rhaco\store\db\exception\GenerateUniqueCodeRetryLimitOverException $e){
}
