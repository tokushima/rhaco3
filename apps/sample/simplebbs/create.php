<?php
include('rhaco3.php');
$dbc = new \org\rhaco\store\db\Dbc(
        '{"type":"org.rhaco.store.db.module.Sqlite","dbname":"board"}'
        );
$dbc->query('create table board('
      .'id integer primary key autoincrement'
      .',name text,comment text,created_at text)'
    );