<?php
\org\rhaco\store\queue\Queue::set_module(new \org\rhaco\store\queue\module\Dao());
\org\rhaco\store\db\Dao::start_record();


while(true){
	try{
		$model = \org\rhaco\store\queue\Queue::get('test');
		\org\rhaco\store\queue\Queue::finish($model);
	}catch(\Exception $e){
		break;
	}
}

for($i=1;$i<=5;$i++){
	\org\rhaco\store\queue\Queue::insert('test',$i);
}
for($i=1;$i<=5;$i++){
	$model = \org\rhaco\store\queue\Queue::get('test');
	eq($i,$model->data());
	\org\rhaco\store\queue\Queue::finish($model);
}




for($i=1;$i<=5;$i++){
	\org\rhaco\store\queue\Queue::insert('test',$i);
}

$i = 0;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$i++;
	eq($i,$model->data()); // ロックだけする
}
eq(5,$i);
\org\rhaco\store\queue\Queue::reset('test',-86400); // 未来を指定してリセット

$i = 0;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$i++;
	eq($i,$model->data());
	\org\rhaco\store\queue\Queue::finish($model);
}
eq(5,$i);

\org\rhaco\store\queue\Queue::clean('test');

