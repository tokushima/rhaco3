<?php
\org\rhaco\store\queue\Queue::set_module(new \org\rhaco\store\queue\module\Dao());
\org\rhaco\store\db\Dao::start_record();


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

$b = false;
$i = 1;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$b = true;
	eq($i,$model->data()); // ロックだけする
	$i++;
}
eq(true,$b);
\org\rhaco\store\queue\Queue::reset('test',0); // リセット

$b = false;
$i = 1;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$b = true;
	eq($i,$model->data());
	\org\rhaco\store\queue\Queue::finish($model);
	$i++;
}
eq(true,$b);

\org\rhaco\store\queue\Queue::clean('test');

