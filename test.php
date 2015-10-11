<?php

error_reporting(E_ALL);
require_once 'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$nbt = new Nbt\Service();
$nbt->verbose = true;

echo 'Small Test'.PHP_EOL;
printTree($nbt->loadFile('smalltest.nbt'));
echo PHP_EOL;

echo 'Big Test'.PHP_EOL;
$bigTree = $nbt->loadFile('bigtest.nbt');
printTree($bigTree);
echo PHP_EOL;

echo 'Creation Test'.PHP_EOL;
$createdTree = \Nbt\Tag::tagCompound('Level', [
    \Nbt\Tag::tagShort('shortTest', 32767),
    \Nbt\Tag::tagInt('intTest', 2147483647),
    \Nbt\Tag::tagLong('longTest', 9223372036854775807),
    \Nbt\Tag::tagFloat('floatTest', '0.49823147058487'),
    \Nbt\Tag::tagDouble('doubleTest', '0.49312871321823'),
    \Nbt\Tag::tagByteArray('byteArrayTest', [0, 65, 54, 250, 99, 1]),
    \Nbt\Tag::tagString('stringTest', 'HELLO WORLD!'),
    \Nbt\Tag::tagList('listTest', \Nbt\Tag::TAG_SHORT, [
        \Nbt\Tag::tagShort('', 1),
        \Nbt\Tag::tagShort('', 2),
        \Nbt\Tag::tagShort('', 3),
        \Nbt\Tag::tagShort('', 4),
    ]),
    \Nbt\Tag::tagList('listTest', \Nbt\Tag::TAG_COMPOUND, [
        \Nbt\Tag::tagCompound('', [
            \Nbt\Tag::tagString('name', 'Compound tag #1'),
            \Nbt\Tag::tagInt('value', 1),
        ]),
        \Nbt\Tag::tagCompound('', [
            \Nbt\Tag::tagString('name', 'Compound tag #2'),
            \Nbt\Tag::tagInt('value', 2),
        ]),
    ]),
    \Nbt\Tag::tagIntArray('intArrayTest', [1, 2, 3, 4, 5, 6, 7, 8, 9]),
]);
printTree($createdTree);
echo PHP_EOL;

echo 'Write Test (created test written to disk and re-read'.PHP_EOL;
$tmpFile = tempnam(sys_get_temp_dir(), 'nbt');
$nbt->writeFile($tmpFile, $createdTree);
printTree($nbt->loadFile($tmpFile));

/**
 * Quick function to print a tree in a nice manner.
 *
 * @param \Nbt\Node $node
 * @param int       $indent
 */
function printTree($node, $indent = 0)
{
    for ($i = 0; $i < $indent; ++$i) {
        echo ' ';
    }

    if ($node->getType()) {
        echo 'Type => '.$node->getType().' ; ';
    }
    if ($node->getName()) {
        echo 'Name => '.$node->getName().' ; ';
    }
    if ($node->getValue()) {
        echo 'Value => '
            .(is_array($node->getValue())
                ? 'array['.count($node->getValue()).']'
                : $node->getValue())
            .' ; ';
    }
    if ($node->getPayloadType()) {
        echo 'PayloadType => '.$node->getPayloadType().' ; ';
    }
    echo PHP_EOL;

    if (!$node->isLeaf()) {
        foreach ($node->getChildren() as $child) {
            printTree($child, $indent + 2);
        }
    }
}
