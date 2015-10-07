<?php

error_reporting(E_ALL);
require_once 'vendor/autoload.php';

$nbt = new Nbt\Service();
#$nbt->verbose = true;


$tree = $nbt->loadFile("smalltest.nbt");
echo "Small Test".PHP_EOL;
printTree($tree);

$tree = $nbt->loadFile("bigtest.nbt");
echo "Big Test".PHP_EOL;
#print_r($nbt->root[1]);
printTree($tree);

# $nbt->writeFile($tmp = tempnam(sys_get_temp_dir(), "nbt"));

/**
 *
 * @param \Tree\Node\Node $node
 * @param type $indent
 */
function printTree($node, $indent = 0)
{
    for($i = 0; $i < $indent; $i++) {
        echo ' ';
    }

    $val = $node->getValue();
    if (isset($val['value']) && is_array($val['value']))
    {
        $val['value'] = 'Array['.count($val['value']).']';
    }
    if (is_array($val)) {
        foreach($val AS $k => $v) {
            echo $k.' => '.$v.' : ';
        }
        echo "\n";
#        echo implode(', ',$val)."\n";
    } else {
        var_dump($val);
    }
    if (!$node->isLeaf())
    {
        foreach($node->getChildren() AS $child)
        {
            printTree($child, $indent + 2);
        }
    }
}
