<?php
eq("&lt;abc aa=&#039;123&#039; bb=&quot;ddd&quot;&gt;あいう&lt;/abc&gt;",\org\rhaco\lang\Text::htmlencode("<abc aa='123' bb=\"ddd\">あいう</abc>"));

