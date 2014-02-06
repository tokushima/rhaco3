<?php
$b = b();
$b->do_get(test_map_url('test_index::html_filter'));
meq('PPPPP',$b->body());
meq('&lt;tag&gt;QQQ&lt;/tag&gt;',$b->body());

meq('<input type="text" name="aaa" value="hogehoge" />',$b->body());
meq('<input type="text" name="ttt" value="&lt;tag&gt;ttt&lt;/tag&gt;" />',$b->body());
meq('<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge',$b->body());
meq('<input type="checkbox" name="bbb[]" value="fuga" />fuga',$b->body());
meq('<input type="checkbox" name="eee[]" value="true" checked="checked" />foo',$b->body());
meq('<input type="checkbox" name="fff[]" value="false" checked="checked" />foo',$b->body());
meq('<input type="submit" />',$b->body());
meq('<textarea name="aaa">hogehoge</textarea>',$b->body());
meq('<textarea name="ttt">&lt;tag&gt;ttt&lt;/tag&gt;</textarea>',$b->body());
meq('<option value="456" selected="selected">456</option>',$b->body());
meq('<option value="789" selected="selected">789</option>',$b->body());
meq('<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>',$b->body());
