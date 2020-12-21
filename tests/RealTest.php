<?php

namespace Nbt\Tests;

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;

class RealTest extends TestCase
{
    public $service;
    public $usingGMP;
    public $vRoot;
    public $vFile;

    /**
     * @dataProvider providerFiles
     */
    public function testLoadFiles($file, $tree)
    {
        $fileTree = $this->service->loadFile($file);

        $this->assertEquals($tree, $fileTree);
    }

    /**
     * @dataProvider providerRawFiles
     */
    public function testWriteFiles($file, $tree)
    {
        $this->service->writeFile($this->vFile->url(), $tree, '');

        $this->assertFileEquals($file, $this->vFile->url());
    }

    /**************************************************************************/

    public function setUp(): void
    {
        $this->service = new \Nbt\Service(new \Nbt\DataHandler());

        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('test.nbt');
        $this->vRoot->addChild($this->vFile);
    }

    public function providerFiles()
    {
        return [
            ['tests/Data/smalltest.nbt', $this->smallTree()],
            ['tests/Data/bigtest.nbt', $this->bigTree()],
            ['tests/Data/hugetest.nbt', $this->hugeTree()],
        ];
    }

    public function providerRawFiles()
    {
        return [
            ['tests/Data/smalltest.raw.nbt', $this->smallTree()],
            ['tests/Data/bigtest.raw.nbt', $this->bigTree()],
            ['tests/Data/hugetest.raw.nbt', $this->hugeTree()],
        ];
    }

    public function smallTree()
    {
        return (new \Nbt\Node())
            ->setType(\Nbt\Tag::TAG_COMPOUND)
            ->setName('hello world')
            ->setChildren([
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_STRING)
                    ->setName('name')
                    ->setValue('Bananrama')
            ]);
    }

    public function bigTree()
    {
        $byteArray = [];
        for ($n = 0; $n < 1000; $n++) {
            $byteArray[$n] = ($n*$n*255+$n*7)%100;
        }

        return (new \Nbt\Node())
            ->setType(\Nbt\Tag::TAG_COMPOUND)
            ->setName('Level')
            ->setChildren([
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_LONG)
                    ->setName('longTest')
                    ->setValue((PHP_INT_SIZE < 8) ? '9223372036854775807' : 9223372036854775807),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_SHORT)
                    ->setName('shortTest')
                    ->setValue(32767),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_STRING)
                    ->setName('stringTest')
                    ->setValue('HELLO WORLD THIS IS A TEST STRING!'),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_FLOAT)
                    ->setName('floatTest')
                    ->setValue(0.49823147058487),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_INT)
                    ->setName('intTest')
                    ->setValue(2147483647),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('nested compound test')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_COMPOUND)
                            ->setName('ham')
                            ->setChildren([
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_STRING)
                                    ->setName('name')
                                    ->setValue('Hampus'),
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_FLOAT)
                                    ->setName('value')
                                    ->setValue(0.75),
                            ]),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_COMPOUND)
                            ->setName('egg')
                            ->setChildren([
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_STRING)
                                    ->setName('name')
                                    ->setValue('Eggbert'),
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_FLOAT)
                                    ->setName('value')
                                    ->setValue(0.5),
                            ]),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_LIST)
                    ->setName('listTest (long)')
                    ->setPayloadType(\Nbt\Tag::TAG_LONG)
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setValue((PHP_INT_SIZE < 8) ? '11' : 11),
                        (new \Nbt\Node())
                            ->setValue((PHP_INT_SIZE < 8) ? '12' : 12),
                        (new \Nbt\Node())
                            ->setValue((PHP_INT_SIZE < 8) ? '13' : 13),
                        (new \Nbt\Node())
                            ->setValue((PHP_INT_SIZE < 8) ? '14' : 14),
                        (new \Nbt\Node())
                            ->setValue((PHP_INT_SIZE < 8) ? '15' : 15),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_LIST)
                    ->setName('listTest (compound)')
                    ->setPayloadType(\Nbt\Tag::TAG_COMPOUND)
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setChildren([
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_STRING)
                                    ->setName('name')
                                    ->setValue('Compound tag #0'),
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_LONG)
                                    ->setName('created-on')
                                    ->setValue((PHP_INT_SIZE < 8) ? '1264099775885' : 1264099775885),
                            ]),
                        (new \Nbt\Node())
                            ->setChildren([
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_STRING)
                                    ->setName('name')
                                    ->setValue('Compound tag #1'),
                                (new \Nbt\Node())
                                    ->setType(\Nbt\Tag::TAG_LONG)
                                    ->setName('created-on')
                                    ->setValue((PHP_INT_SIZE < 8) ? '1264099775885' : 1264099775885),
                            ]),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_BYTE)
                    ->setName('byteTest')
                    ->setValue(127),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_BYTE_ARRAY)
                    ->setName('byteArrayTest (the first 1000 values of (n*n*255+n*7)%100, starting with '
                        .'n=0 (0, 62, 34, 16, 8, ...))')
                    ->setValue($byteArray),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_DOUBLE)
                    ->setName('doubleTest')
                    ->setValue(0.4931287132182315),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_INT_ARRAY)
                    ->setName('New Int Array')
                    ->setValue([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]),
            ]);
    }


    public function hugeTree()
    {
        return (new \Nbt\Node())
            ->setType(\Nbt\Tag::TAG_COMPOUND)
            ->setName('')
            ->setChildren([
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_STRING)
                    ->setName('description')
                    ->setValue('This is a test of all the tag types up to the introduction of Anvil, including '
                        .'TAG_INT_ARRAY. It\'s not necessarily more exhaustive than bigtest.nbt, but it should be '
                        .'useful to test certain edge cases.'),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('bytes')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_BYTE)
                            ->setName('0')
                            ->setValue(0),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_BYTE)
                            ->setName('1')
                            ->setValue(127),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_BYTE)
                            ->setName('2')
                            ->setValue(-128),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_BYTE)
                            ->setName('3')
                            ->setValue(-1),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('shorts')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_SHORT)
                            ->setName('0')
                            ->setValue(0),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_SHORT)
                            ->setName('1')
                            ->setValue(32767),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_SHORT)
                            ->setName('2')
                            ->setValue(-32768),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_SHORT)
                            ->setName('3')
                            ->setValue(-1),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('ints')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_INT)
                            ->setName('0')
                            ->setValue(0),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_INT)
                            ->setName('1')
                            ->setValue(2147483647),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_INT)
                            ->setName('2')
                            ->setValue(-2147483648),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_INT)
                            ->setName('3')
                            ->setValue(-1),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('longs')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_LONG)
                            ->setName('0')
                            ->setValue((PHP_INT_SIZE < 8) ? '0' : 0),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_LONG)
                            ->setName('1')
                            ->setValue((PHP_INT_SIZE < 8) ? '9223372036854775807' : 9223372036854775807),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_LONG)
                            ->setName('2')
                            ->setValue((PHP_INT_SIZE < 8) ? '-9223372036854775808' : -9223372036854775808),
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_LONG)
                            ->setName('3')
                            ->setValue((PHP_INT_SIZE < 8) ? '-1' : -1),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('floats')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_FLOAT)
                            ->setName('0')
                            ->setValue(0),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_COMPOUND)
                    ->setName('doubles')
                    ->setChildren([
                        (new \Nbt\Node())
                            ->setType(\Nbt\Tag::TAG_DOUBLE)
                            ->setName('0')
                            ->setValue(0),
                    ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_BYTE_ARRAY)
                    ->setName('bytearray')
                    ->setValue([
                        0 => 31, 1 => -117, 2 => 8, 3 => 0, 4 => 0, 5 => 0,
                        6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => -29, 11 => 98, 12 => -32,
                        13 => -50, 14 => 72, 15 => -51, 16 => -55, 17 => -55, 18 => 87,
                        19 => 40, 20 => -49, 21 => 47, 22 => -54, 23 => 73, 24 => -31,
                        25 => 96, 26 => 96, 27 => -55, 28 => 75, 29 => -52, 30 => 77,
                        31 => 101, 32 => -32, 33 => 116, 34 => 74, 35 => -52, 36 => 75,
                        37 => -52, 38 => 43, 39 => 74, 40 => -52, 41 => 77, 42 => 100,
                        43 => 0, 44 => 0, 45 => 119, 46 => -38, 47 => 92, 48 => 58,
                        49 => 33, 50 => 0, 51 => 0, 52 => 0,
                     ]),
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_INT_ARRAY)
                    ->setName('intarray')
                    ->setValue([
                         0 => 529205248,
                         1 => 0,
                         2 => 58210,
                         3 => -523351859,
                         4 => -909551832,
                         5 => -818951607,
                         6 => -513777463,
                         7 => 1271680357,
                         8 => -529249588,
                         9 => 1271671626,
                        10 => -867343360,
                        11 => 7854684,
                        12 => 975241216,
                        13 => 0,
                    ]),
            ]);
    }
}
