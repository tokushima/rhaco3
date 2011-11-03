<?php
include('rhaco3.php');
\org\rhaco\Conf::set('org.rhaco.store.db.Dao'
            ,'Board'
            ,'{"type":"org.rhaco.store.db.module.Sqlite","dbname":"board"}'
          );
/**
 * @var serial $id
 * @var string $name
 * @var text $comment
 * @var timestamp $created_at @{"auto_now_add":true}
 */
class Board extends \org\rhaco\store\db\Dao{
  protected $id;
  protected $name;
  protected $comment;
  protected $created_at;
}

$req = new \org\rhaco\Request();
if($req->is_post()){
  $obj = new Board();
  $obj->name($req->in_vars("name"));
  $obj->comment($req->in_vars("comment"));
  $obj->save();
}

$paginator = new \org\rhaco\Paginator(5,$req->in_vars("page",1));
$template = new \org\rhaco\Template();
$template
  ->set_object_module(new \org\rhaco\flow\module\HtmlFilter())
  ->set_object_module(new \org\rhaco\flow\module\Paginator());
$template->cp($req);
$template->vars("object_list"
  ,Board::find_all($paginator,\org\rhaco\store\db\Q::order('-id'))
);
$template->vars("paginator",$paginator);
$template->output(__FILE__);
?>

<rt:template>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
  <form method="post">
    名前:<input type="text" name="name" rt:ref="true" /><br />
    コメント:<textarea name="comment"></textarea><br />
    <input type="submit" value="投稿" />
  </form>
  <rt:loop param="object_list" var="obj">
  <div>
    {$obj.name()} [{$obj.fm_created_at()}]
  </div>
  <pre>
  {$obj.comment()}
  </pre>
  <hr />
  </rt:loop>
  
  <div class="paginator">
    <rt:paginator />
  </div>
</body>
</html>
</rt:template>