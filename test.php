<?php

error_reporting(E_ALL);
require_once 'vendor/autoload.php';

$nbt = new Nbt\Service();
$nbt->verbose = true;

echo 'Small Test'.PHP_EOL;
printTree($nbt->loadFile('smalltest.nbt'));

echo 'Big Test'.PHP_EOL;
$bigTree = $nbt->loadFile('bigtest.nbt');
printTree($bigTree);

echo 'Write Test (big file written to disk and re-read'.PHP_EOL;
$tmpFile = tempnam(sys_get_temp_dir(), 'nbt');
$nbt->writeFile($tmpFile, $bigTree);
printTree($nbt->loadFile($tmpFile));

/**
 * Quick function to print a tree in a nice manner.
 *
 * @param Node $node
 * @param int  $indent
 */
function printTree($node, $indent = 0)
{
    for ($i = 0; $i < $indent; ++$i) {
        echo ' ';
    }

    $val = $node->getValue();
    if (isset($val['value']) && is_array($val['value'])) {
        $val['value'] = 'Array['.count($val['value']).']';
    }
    if (is_array($val)) {
        if (count($val) == 0) {
            echo '[Empty node]';
        }
        foreach ($val as $k => $v) {
            echo $k.' => '.$v.' : ';
        }
    } else {
        var_dump($val);
    }
    echo PHP_EOL;

    if (!$node->isLeaf()) {
        foreach ($node->getChildren() as $child) {
            printTree($child, $indent + 2);
        }
    }
}
