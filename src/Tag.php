<?php

namespace Nbt;

class Tag
{
    const TAG_END = 0;
    const TAG_BYTE = 1;
    const TAG_SHORT = 2;
    const TAG_INT = 3;
    const TAG_LONG = 4;
    const TAG_FLOAT = 5;
    const TAG_DOUBLE = 6;
    const TAG_BYTE_ARRAY = 7;
    const TAG_STRING = 8;
    const TAG_LIST = 9;
    const TAG_COMPOUND = 10;
    const TAG_INT_ARRAY = 11;
    const TAG_LONG_ARRAY = 12;

    /**
     * Get a TAG_BYTE node.
     *
     * @param string $name
     * @param int    $value Single byte
     *
     * @return Node
     */
    public static function tagByte($name, $value)
    {
        return self::simpleTag(self::TAG_BYTE, $name, $value);
    }

    /**
     * Get a TAG_SHORT node.
     *
     * @param string $name
     * @param int    $value Short integer
     *
     * @return Node
     */
    public static function tagShort($name, $value)
    {
        return self::simpleTag(self::TAG_SHORT, $name, $value);
    }

    /**
     * Get a TAG_INT node.
     *
     * @param string $name
     * @param int    $value
     *
     * @return Node
     */
    public static function tagInt($name, $value)
    {
        return self::simpleTag(self::TAG_INT, $name, $value);
    }

    /**
     * Get a TAG_LONG node.
     *
     * @param string $name
     * @param int    $value
     *
     * @return Node
     */
    public static function tagLong($name, $value)
    {
        return self::simpleTag(self::TAG_LONG, $name, $value);
    }

    /**
     * Get a TAG_FLOAT node.
     *
     * @param string $name
     * @param float  $value
     *
     * @return Node
     */
    public static function tagFloat($name, $value)
    {
        return self::simpleTag(self::TAG_FLOAT, $name, $value);
    }

    /**
     * Get a TAG_DOUBLE node.
     *
     * @param string $name
     * @param float  $value
     *
     * @return Node
     */
    public static function tagDouble($name, $value)
    {
        return self::simpleTag(self::TAG_DOUBLE, $name, $value);
    }

    /**
     * Get a TAG_BYTE_ARRAY node.
     *
     * @param string $name
     * @param int[]  $value Array of bytes
     *
     * @return Node
     */
    public static function tagByteArray($name, $value)
    {
        return self::simpleTag(self::TAG_BYTE_ARRAY, $name, $value);
    }

    /**
     * Get a TAG_STRING node.
     *
     * @param string $name
     * @param string $value Array of bytes
     *
     * @return Node
     */
    public static function tagString($name, $value)
    {
        return self::simpleTag(self::TAG_STRING, $name, $value);
    }

    /**
     * Get a TAG_INT_ARRAY node.
     *
     * @param string $name
     * @param int[]  $value Array of integers
     *
     * @return Node
     */
    public static function tagIntArray($name, $value)
    {
        return self::simpleTag(self::TAG_INT_ARRAY, $name, $value);
    }

    /**
     * Get a TAG_LIST node.
     *
     * @param string $name
     * @param int    $payloadType Byte describing the payload type
     * @param Node[] $payload     Array of nodes to add as children
     *
     * @return Node
     */
    public static function tagList($name, $payloadType, $payload)
    {
        $node = (new Node())->setType(self::TAG_LIST)->setName($name)->setPayloadType($payloadType);

        foreach ($payload as $child) {
            $node->addChild($child->makeListPayload());
        }

        return $node;
    }

    /**
     * Get a TAG_COMPOUND node.
     *
     * @param string $name
     * @param Node[] $nodes Array of nodes to add as children
     *
     * @return Node
     */
    public static function tagCompound($name, $nodes)
    {
        $node = (new Node())->setType(self::TAG_COMPOUND)->setName($name);

        foreach ($nodes as $child) {
            $node->addChild($child);
        }

        return $node;
    }

    /**
     * Get a simple tag with a value.
     *
     * @param int    $type  Byte representing the tag type
     * @param string $name
     * @param mixed  $value
     *
     * @return Node
     */
    private static function simpleTag($type, $name, $value)
    {
        return (new Node())->setType($type)->setName($name)->setValue($value);
    }
}
