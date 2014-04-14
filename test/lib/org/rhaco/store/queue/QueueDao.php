<?php
// 1..5追加
for($i=1;$i<=5;$i++){
	\org\rhaco\store\queue\Queue::insert('test',$i);
}
// 1..5取得/finish
for($i=1;$i<=5;$i++){
	$model = \org\rhaco\store\queue\Queue::get('test');
	eq($i,$model->data());
	\org\rhaco\store\queue\Queue::finish($model);
}

// 1..5追加
for($i=1;$i<=5;$i++){
	\org\rhaco\store\queue\Queue::insert('test',$i);
}
// 1..5取得、finishなし
$i = 0;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$i++;
	eq($i,$model->data()); // ロックだけする
}
// ロックのリセット
eq(5,$i);
\org\rhaco\store\queue\Queue::reset('test',-86400); // 未来を指定してリセット

// 解除された5件が取得できる
$i = 0;
foreach(\org\rhaco\store\queue\Queue::gets(5,'test') as $model){
	$i++;
	eq($i,$model->data());
	\org\rhaco\store\queue\Queue::finish($model);
}
eq(5,$i);

eq(10,\org\rhaco\store\queue\module\Dao\QueueDao::find_count());
\org\rhaco\store\queue\Queue::clean('test',time(),3);

eq(7,\org\rhaco\store\queue\module\Dao\QueueDao::find_count());
\org\rhaco\store\queue\Queue::clean('test');

eq(0,\org\rhaco\store\queue\module\Dao\QueueDao::find_count());

