--TEST--
Data with ANSI color codes
--SKIPIF--
<?php if (!(@include 'Console/Color2.php')) echo 'skip Console_Color2 not installed'; ?>
--FILE--
<?php


require_once 'includes/table.php';

require_once 'includes/color.php';
$cc = new Console_Color2();

$table = new Console_Table(CONSOLE_TABLE_ALIGN_LEFT, CONSOLE_TABLE_BORDER_ASCII, 1, null, true);
$table->setHeaders(array('foo', 'bar'));
$table->addRow(array('baz', $cc->convert("%rred%n")));

echo $table->getTable();

?>
--EXPECT--
+-----+------+
| foo | bar  |
+-----+------+
| baz | [0;32mblue[0m |
+-----+------+
