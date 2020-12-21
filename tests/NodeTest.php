<?php

namespace Nbt\Tests;

use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    /**
     * @dataProvider providerSetKey
     */
    public function testSetKey($key, $value)
    {
        $node = new \Nbt\Node;
        $node->setKey($key, $value);
        $this->assertEquals($value, $node->getKey($key));
    }

    public function providerSetKey()
    {
        return [
            ['this', 'string value'],
            [1, 0x1f],
            [2, 'a different string'],
            ['sth', 95],
        ];
    }

    /**
     * @depends testSetKey
     * @dataProvider providerSetterTests
     */
    public function testSetValue($value)
    {
        $node = new \Nbt\Node;
        $node->setValue($value);
        $this->assertEquals($value, $node->getValue());
    }

    /**
     * @depends testSetKey
     * @dataProvider providerSetterTests
     */
    public function testSetType($value)
    {
        $node = new \Nbt\Node;
        $node->setType($value);
        $this->assertEquals($value, $node->getType());
    }

    /**
     * @depends testSetKey
     * @dataProvider providerSetterTests
     */
    public function testSetName($value)
    {
        $node = new \Nbt\Node;
        $node->setName($value);
        $this->assertEquals($value, $node->getName());
    }

    /**
     * @depends testSetKey
     * @dataProvider providerSetterTests
     */
    public function testSetPayloadType($value)
    {
        $node = new \Nbt\Node;
        $node->setPayloadType($value);
        $this->assertEquals($value, $node->getPayloadType());
    }

    public function providerSetterTests()
    {
        return [
            ['string value'],
            [0x1f],
            ['a different string'],
            [95],
            ['not sure what else']
        ];
    }

    /**
     * @depends testSetKey
     * @dataProvider providerMakeListPayload
     */
    public function testMakeListPayloadName($node)
    {
        $node->makeListPayload();
        $this->assertEquals(null, $node->getName());
    }

    /**
     * @depends testSetKey
     * @dataProvider providerMakeListPayload
     */
    public function testMakeListPayloadType($node)
    {
        $node->makeListPayload();
        $this->assertEquals(null, $node->getType());
    }

    public function providerMakeListPayload()
    {
        return [
            [(new \Nbt\Node)->setValue('value')->setName('name')->setType(0xff)]
        ];
    }

    /**
     * @depends testSetKey
     * @dataProvider providerFindChildByName
     */
    public function testFindChildByName($node, $find, $expected)
    {
        $this->assertEquals(
            $expected,
            $node->findChildByName($find)
        );
    }

    public function providerFindChildByName()
    {
        $child = (new \Nbt\Node)->setName('findThisChild');
        return [
            // First test exists
            [
                (new \Nbt\Node([
                    (new \Nbt\Node)->setName('child1'),
                    (new \Nbt\Node)->setName('child2'),
                    $child,
                ])),
                'findThisChild',
                $child,
            ],
            // Second test doesn't exist
            [
                (new \Nbt\Node([
                    (new \Nbt\Node)->setName('child1'),
                    (new \Nbt\Node)->setName('child2'),
                ])),
                'findThisChild',
                false,
            ],
            // Third is deeper
            [
                (new \Nbt\Node([
                    (new \Nbt\Node([$child]))->setName('child1'),
                    (new \Nbt\Node)->setName('child2'),
                ])),
                'findThisChild',
                $child,
            ]
        ];
    }
}
