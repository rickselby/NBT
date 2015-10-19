<?php

namespace Nbt\Tests;

/**
 * We have tests for the Node class, so just need to check the correct type is
 * returned.
 *
 * Not sure this is the greatest, most verbose way of doing the tests...
 * but it covers everything we want to check.
 */
class TagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestTagGetters
     */
    public function testTagGetters($node, $type)
    {
        $this->assertInstanceOf('\Nbt\Node', $node);
        $this->assertEquals($node->getType(), $type);
    }

    public function providerTestTagGetters()
    {
        return [
            [\Nbt\Tag::tagByte('ByteTag', 0x7b), \Nbt\Tag::TAG_BYTE],
            [\Nbt\Tag::tagShort('ShortTag', 12), \Nbt\Tag::TAG_SHORT],
            [\Nbt\Tag::tagInt('IntTag', 234567), \Nbt\Tag::TAG_INT],
            [\Nbt\Tag::tagLong('LongTag', 8978), \Nbt\Tag::TAG_LONG],
            [\Nbt\Tag::tagFloat('FTag', 12.245), \Nbt\Tag::TAG_FLOAT],
            [\Nbt\Tag::tagDouble('DTag', 9.876), \Nbt\Tag::TAG_DOUBLE],
            [\Nbt\Tag::tagByteArray('BAT', [0xe1, 0xf2]), \Nbt\Tag::TAG_BYTE_ARRAY],
            [\Nbt\Tag::tagString('StringTag', 'AString'), \Nbt\Tag::TAG_STRING],
            [\Nbt\Tag::tagIntArray('IAT', [1, 2, 3, 45]), \Nbt\Tag::TAG_INT_ARRAY],

            [
                \Nbt\Tag::tagList(
                    'ListTag',
                    \Nbt\Tag::TAG_BYTE,
                    [
                        \Nbt\Tag::tagByte('b1', 0x01),
                        \Nbt\Tag::tagByte('b2', 0x02),
                    ]
                ),
                \Nbt\Tag::TAG_LIST
            ],
            [
                \Nbt\Tag::tagCompound(
                    'CompoundTag',
                    [
                        \Nbt\Tag::tagByte('b1', 0x01),
                        \Nbt\Tag::tagString('s2', 'astring'),
                    ]
                ),
                \Nbt\Tag::TAG_COMPOUND
            ],
        ];
    }
}
