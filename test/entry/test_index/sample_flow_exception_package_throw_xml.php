<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::sample_flow_exception_package_throw_xml'));
eq(403,$b->status());
meq('<message group="" type="SampleException">sample error</message>',$b->body());
