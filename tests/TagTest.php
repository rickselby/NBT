<?php

namespace Nbt\tests;

use Nbt\Tag;
use PHPUnit\Framework\TestCase;

/**
 * We have tests for the Node class, so just need to check the correct type is
 * returned.
 *
 * Not sure this is the greatest, most verbose way of doing the tests...
 * but it covers everything we want to check.
 */
class TagTest extends TestCase
{
    /**
     * @dataProvider providerTestTagGetters
     */
    public function testTagGetterReturnsNode($func, $value, $type)
    {
        $tag = Tag::$func('TagName', $value);
        $this->assertInstanceOf('\Nbt\Node', $tag);
    }

    /**
     * @dataProvider providerTestTagGetters
     */
    public function testTagGetterReturnsCorrectType($func, $value, $type)
    {
        $tag = Tag::$func('TagName', $value);
        $this->assertEquals($tag->getType(), $type);
    }

    /**
     * @dataProvider providerTestTagGetters
     */
    public function testTagGetterReturnsCorrectValue($func, $value, $type)
    {
        $tag = Tag::$func('TagName', $value);
        $this->assertEquals($tag->getValue(), $value);
    }

    public function providerTestTagGetters()
    {
        return [
            ['tagByte', 0x7b, Tag::TAG_BYTE],
            ['tagShort', 12, Tag::TAG_SHORT],
            ['tagInt', 234567, Tag::TAG_INT],
            ['tagLong', 8978, Tag::TAG_LONG],
            ['tagFloat', 12.245, Tag::TAG_FLOAT],
            ['tagDouble', 9.876, Tag::TAG_DOUBLE],
            ['tagByteArray', [0xe1, 0xf2], Tag::TAG_BYTE_ARRAY],
            ['tagString', 'AString', Tag::TAG_STRING],
            ['tagIntArray', [1,2,3,45], Tag::TAG_INT_ARRAY],
        ];
    }

    /**
     * @dataProvider providerTestTagList
     */
    public function testTagListReturnsNode($name, $type, $data)
    {
        $tag = Tag::tagList($name, $type, $data);
        $this->assertInstanceOf('\Nbt\Node', $tag);
    }

    /**
     * @dataProvider providerTestTagList
     */
    public function testTagListReturnsListType($name, $type, $data)
    {
        $tag = Tag::tagList($name, $type, $data);
        $this->assertEquals($tag->getType(), Tag::TAG_LIST);
    }

    public function providerTestTagList()
    {
        return [
            [
                'ListTag',
                Tag::TAG_BYTE,
                [
                    Tag::tagByte('b1', 0x01),
                    Tag::tagByte('b2', 0x02),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerTestTagCompound
     */
    public function testTagCompoundReturnsNode($name, $data)
    {
        $tag = Tag::tagCompound($name, $data);
        $this->assertInstanceOf('\Nbt\Node', $tag);
    }

    /**
     * @dataProvider providerTestTagCompound
     */
    public function testTagCompoundReturnsCompoundType($name, $data)
    {
        $tag = Tag::tagCompound($name, $data);
        $this->assertEquals($tag->getType(), Tag::TAG_COMPOUND);
    }

    public function providerTestTagCompound()
    {
        return  [
            [
                'CompoundTag',
                [
                    \Nbt\Tag::tagByte('b1', 0x01),
                    \Nbt\Tag::tagString('s2', 'astring'),
                ],
            ],
        ];
    }
}
