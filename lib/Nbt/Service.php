<?php

/**
 * Class for reading in NBT-format files.
 *
 * @author  Justin Martin <frozenfire@thefrozenfire.com>
 *
 * @version 1.0
 *
 * Dependencies:
 *  PHP 4.3+ (5.3+ recommended)
 *  GMP Extension
 */
namespace Nbt;

if (PHP_INT_SIZE < 8) {
    /*
     *  GMP isn't required for 64-bit machines as we're handling signed ints. We can use native math instead.
     *  We still need to use GMP for 32-bit builds of PHP though.
     */
    if (!extension_loaded('gmp')) {
        trigger_error(
            'The NBT class requires the GMP extension for 64-bit number handling on 32-bit PHP builds. '
            .'Execution will continue, but will halt if a 64-bit number is handled.',
            E_USER_NOTICE
        );
    }
}

class Service
{
    /** @var bool Enable verbose output or not **/
    public $verbose = false;

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

    /**
     * Load a file and read the NBT data from the file.
     *
     * @param string $filename File to open
     * @param string $wrapper  [optional] Stream wrapper if not zlib
     *
     * @return array|false
     */
    public function loadFile($filename, $wrapper = 'compress.zlib://')
    {
        if (is_file($filename)) {
            if ($this->verbose) {
                trigger_error("Loading file \"{$filename}\" with stream wrapper \"{$wrapper}\".", E_USER_NOTICE);
            }
            $fPtr = fopen("{$wrapper}{$filename}", 'rb');

            return $this->readFilePointer($fPtr);
        } else {
            trigger_error('First parameter must be a filename.', E_USER_WARNING);

            return false;
        }
    }

    /**
     * Write the current NBT root data to a file.
     *
     * @param string $filename File to write to
     * @param string $wrapper  [optional] Stream wrapper if not zlib
     *
     * @return true
     */
    public function writeFile($filename, $wrapper = 'compress.zlib://')
    {
        if ($this->verbose) {
            trigger_error("Writing file \"{$filename}\" with stream wrapper \"{$wrapper}\".", E_USER_NOTICE);
        }
        $fPtr = fopen("{$wrapper}{$filename}", 'wb');
        $this->writeFilePointer($fPtr);
        fclose($fPtr);
    }

    /**
     * Read NBT data from the given file pointer.
     *
     * @param resource $fPtr File/Stream pointer
     *
     * @return array
     */
    public function readFilePointer($fPtr)
    {
        if ($this->verbose) {
            trigger_error('Traversing first tag in file.', E_USER_NOTICE);
        }

        $treeRoot = new \Tree\Node\Node();
        $this->traverseTag($fPtr, $treeRoot);

        if ($this->verbose) {
            trigger_error('Encountered end tag for first tag; finished.', E_USER_NOTICE);
        }

        return $treeRoot;
    }

    /**
     * Write the current NBT root data to the given file pointer.
     *
     * @param resource $fPtr File/Stream pointer
     */
    public function writeFilePointer($fPtr)
    {
        if ($this->verbose) {
            trigger_error('Writing '.count($this->root).' root tag(s) to file/resource.', E_USER_NOTICE);
        }
        foreach ($this->root as $rootNum => $rootTag) {
            if (!$this->writeTag($fPtr, $rootTag)) {
                trigger_error("Failed to write root tag #{$rootNum} to file/resource.", E_USER_WARNING);
            }
        }
    }

    /**
     * Read NBT data from a string.
     *
     * @param string $string String containing NBT data
     *
     * @return array
     */
    public function readString($string)
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $string);
        rewind($stream);

        return $this->readFilePointer($stream);
    }

    /**
     * Get a string with the current NBT root data in NBT format.
     *
     * @return string
     */
    public function writeString()
    {
        $stream = fopen('php://memory', 'r+b');
        $this->writeFilePointer($stream);
        rewind($stream);

        return stream_get_contents($stream);
    }

    /**
     * Read the next tag from the stream.
     *
     * @param resource       $fPtr Stream pointer
     * @param \Tree\Node\Node $node Tree array to write to
     *
     * @return bool
     */
    private function traverseTag($fPtr, &$node)
    {
        if (feof($fPtr)) {
            if ($this->verbose) {
                trigger_error('Reached end of file/resource.', E_USER_NOTICE);
            }

            return false;
        }
        // Read type byte
        $tagType = $this->getTAGByte($fPtr);
        if ($tagType == self::TAG_END) {
            return false;
        } else {
            if ($this->verbose) {
                $position = ftell($fPtr);
            }
            $tagName = $this->getTAGString($fPtr);
            if ($this->verbose) {
                trigger_error("Reading tag \"{$tagName}\" at offset {$position}.", E_USER_NOTICE);
            }
            $node->setValue(['type' => $tagType, 'name' => $tagName]);
            $this->readType($fPtr, $tagType, $node);

            /*
            if ($tagData instanceof \Tree\Node\Node) {
                $node->setValue(['type' => $tagType, 'name' => $tagName]);
                $node->addChild($tagData);
            } else {
                // If value is a node
                $node->setValue(['type' => $tagType, 'name' => $tagName, 'value' => $tagData]);
#            $tree[ ] = array('type' => $tagType, 'name' => $tagName, 'value' => $tagData);
            }
             */

            return true;
        }
    }

    /**
     * Write the given tag to the stream.
     *
     * @param resource $fPtr Stream pointer
     * @param array    $tag  Tag to write
     *
     * @return bool
     */
    private function writeTag($fPtr, $tag)
    {
        if ($this->verbose) {
            $position = ftell($fPtr);
            trigger_error(
                "Writing tag \"{$tag['name']}\" of type {$tag['type']} at offset {$position}.",
                E_USER_NOTICE
            );
        }

        return $this->writeType($fPtr, self::TAG_BYTE, $tag['type'])
            && $this->writeType($fPtr, self::TAG_STRING, $tag['name'])
            && $this->writeType($fPtr, $tag['type'], $tag['value']);
    }

    /**
     * Read an individual type from the stream.
     *
     * @param resource        $fPtr    Stream pointer
     * @param int             $tagType Tag to read
     * @param \Tree\Node\Node $node    Node to add data to
     *
     * @return mixed
     */
    private function readType($fPtr, $tagType, $node = null)
    {
        switch ($tagType) {
            case self::TAG_BYTE: // Signed byte (8 bit)
                $this->addValueToNode($node, $this->getTAGByte($fPtr));
                break;
            case self::TAG_SHORT: // Signed short (16 bit, big endian)
                $this->addValueToNode($node, $this->getTAGShort($fPtr));
                break;
            case self::TAG_INT: // Signed integer (32 bit, big endian)
                $this->addValueToNode($node, $this->getTAGInt($fPtr));
                break;
            case self::TAG_LONG: // Signed long (64 bit, big endian)
                list(, $firstHalf, $secondHalf) = unpack('N*', fread($fPtr, 8));
                if (PHP_INT_SIZE >= 8) {
                    // Workaround for PHP bug #47564 in 64-bit PHP<=5.2.9
                    $firstHalf &= 0xFFFFFFFF;
                    $secondHalf &= 0xFFFFFFFF;

                    $value = ($firstHalf << 32) | $secondHalf;
                    if ($value > pow(2, 63)) {
                        $value -= pow(2, 64);
                    }
                } else {
                    if (!extension_loaded('gmp')) {
                        trigger_error(
                            'This file contains a 64-bit number and execution cannot continue. '
                            .'Please install the GMP extension for 64-bit number handling.',
                            E_USER_ERROR
                        );
                    }

                    // Fix values >= 2^31 (same fix as above, but this time because it's > PHP_INT_MAX)
                    $firstHalf = gmp_and($firstHalf, '0xFFFFFFFF');
                    $secondHalf = gmp_and($secondHalf, '0xFFFFFFFF');

                    $value = gmp_add($secondHalf, gmp_mul($firstHalf, '4294967296'));
                    if (gmp_cmp($value, gmp_pow(2, 63)) >= 0) {
                        $value = gmp_sub($value, gmp_pow(2, 64));
                    }
                    $value = gmp_strval($value);
                }
                $this->addValueToNode($node, $value);
                break;
            case self::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('f', fread($fPtr, 4))
                    : unpack('f', strrev(fread($fPtr, 4)));
                $this->addValueToNode($node, $value);
                break;
            case self::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('d', fread($fPtr, 8))
                    : unpack('d', strrev(fread($fPtr, 8)));
                $this->addValueToNode($node, $value);
                break;
            case self::TAG_BYTE_ARRAY: // Byte array
                $arrayLength = $this->getTAGInt($fPtr);
                $array = array_values(unpack('c*', fread($fPtr, $arrayLength)));

                $this->addValueToNode($node, $array);
                break;
            case self::TAG_STRING: // String
                $this->addValueToNode($node, $this->getTAGString($fPtr));
                break;
            case self::TAG_LIST: // List
                $tagID = $this->getTAGByte($fPtr);
                $listLength = $this->getTAGInt($fPtr);
                if ($this->verbose) {
                    trigger_error("Reading in list of {$listLength} tags of type {$tagID}.", E_USER_NOTICE);
                }

                // Add a reference to the payload type
                $value = $node->getValue();
                $value['payloadType'] = $tagID;
                $node->setValue($value);

                for ($i = 0; $i < $listLength; ++$i) {
                    if (feof($fPtr)) {
                        break;
                    }
                    $listNode = new \Tree\Node\Node();
                    $this->readType($fPtr, $tagID, $listNode);
                    $node->addChild($listNode);
                }
                break;
            case self::TAG_COMPOUND: // Compound
                // Uck. Don't know a better way to do this,
                $compoundNode = new \Tree\Node\Node();
                while ($this->traverseTag($fPtr, $compoundNode)) {
                    $node->addChild($compoundNode);
                    // Reset the node for adding the next tags
                    $compoundNode = new \Tree\Node\Node();
                }
                break;
            case self::TAG_INT_ARRAY:
                $arrayLength = $this->getTAGInt($fPtr);
                $array = array_values(unpack('N*', fread($fPtr, $arrayLength * 4)));
                $this->addValueToNode($node, $array);
                break;
        }
    }

    /**
     * Read a byte tag from the file
     *
     * @param resource $fPtr
     *
     * @return byte
     */
    private function getTAGByte($fPtr)
    {
        return unpack('c', fread($fPtr, 1))[1];
    }

    /**
     * Read a string from the file
     *
     * @param resource $fPtr
     *
     * @return string
     */
    private function getTAGString($fPtr)
    {
        if (!$stringLength = $this->getTAGShort($fPtr)) {
            return '';
        }
        // Read in number of bytes specified by string length, and decode from utf8.
        return utf8_decode(fread($fPtr, $stringLength));
    }

    /**
     * Read a short int from the file
     *
     * @param resource $fPtr
     *
     * @return integer
     */
    private function getTAGShort($fPtr)
    {
        return $this->unsignedToSigned(
                unpack('n', fread($fPtr, 2))[1],
                16
        );
    }

    /**
     * Get an int from the file
     * @param resource $fPtr
     * @return integer
     */
    private function getTAGInt($fPtr)
    {
        return $this->unsignedToSigned(
                unpack('N', fread($fPtr, 4))[1],
                32
        );

    }

    /**
     * Convert an unsigned int to signed, if required
     *
     * @param integer $value
     * @param integer $size
     *
     * @return integer
     */
    private function unsignedToSigned($value, $size)
    {
        if ($value >= pow(2, $size-1)) {
            $value -= pow(2, $size);
        }
        return $value;
    }

    /**
     * Write an individual type to the stream.
     *
     * @param resource $fPtr    Stream pointer
     * @param int      $tagType Type of tag to write
     * @param mixed    $value   Value of tag to write
     *
     * @return bool
     */
    private function writeType($fPtr, $tagType, $value)
    {
        switch ($tagType) {
            case self::TAG_BYTE: // Signed byte (8 bit)
                return is_int(fwrite($fPtr, pack('c', $value)));
            case self::TAG_SHORT: // Signed short (16 bit, big endian)
                if ($value < 0) {
                    $value += pow(2, 16);
                } // Convert signed short to unsigned short
                return is_int(fwrite($fPtr, pack('n', $value)));
            case self::TAG_INT: // Signed integer (32 bit, big endian)
                if ($value < 0) {
                    $value += pow(2, 32);
                } // Convert signed int to unsigned int
                return is_int(fwrite($fPtr, pack('N', $value)));
            case self::TAG_LONG: // Signed long (64 bit, big endian)
                if (PHP_INT_SIZE >= 8) {
                    $firstHalf = ($value & 0xFFFFFFFF00000000) >> 32;
                    $secondHalf = $value & 0xFFFFFFFF;

                    $wResult = is_int(fwrite($fPtr, pack('NN', $firstHalf, $secondHalf)));
                } else {
                    if (!extension_loaded('gmp')) {
                        trigger_error(
                            'This file contains a 64-bit number and execution cannot continue. '
                            .'Please install the GMP extension for 64-bit number handling.',
                            E_USER_ERROR
                        );
                    }

                    // 32-bit values seem to be too long for pack() on 32-bit machines. Split into 4x16-bit instead.
                    $quarters[ 0 ] = gmp_div(gmp_and($value, '0xFFFF000000000000'), gmp_pow(2, 48));
                    $quarters[ 1 ] = gmp_div(gmp_and($value, '0x0000FFFF00000000'), gmp_pow(2, 32));
                    $quarters[ 2 ] = gmp_div(gmp_and($value, '0x00000000FFFF0000'), gmp_pow(2, 16));
                    $quarters[ 3 ] = gmp_and($value, '0xFFFF');

                    $wResult = is_int(fwrite(
                        $fPtr,
                        pack(
                            'nnnn',
                            gmp_intval($quarters[ 0 ]),
                            gmp_intval($quarters[ 1 ]),
                            gmp_intval($quarters[ 2 ]),
                            gmp_intval($quarters[ 3 ])
                        )
                    ));
                }

                return $wResult;
            case self::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                return is_int(fwrite($fPtr, (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('f', $value)
                    : strrev(pack('f', $value))));
            case self::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                return is_int(fwrite($fPtr, (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('d', $value)
                    : strrev(pack('d', $value))));
            case self::TAG_BYTE_ARRAY: // Byte array
                return $this->writeType($fPtr, self::TAG_INT, count($value))
                    && is_int(fwrite(
                        $fPtr,
                        call_user_func_array(
                            'pack',
                            array_merge(array('c'.count($value)), $value)
                        )
                    ));
            case self::TAG_STRING: // String
                $value = utf8_encode($value);

                return $this->writeType($fPtr, self::TAG_SHORT, strlen($value)) && is_int(fwrite($fPtr, $value));
            case self::TAG_LIST: // List
                if ($this->verbose) {
                    trigger_error(
                        'Writing list of '.count($value['value'])." tags of type {$value['type']}.",
                        E_USER_NOTICE
                    );
                }
                if (!($this->writeType($fPtr, self::TAG_BYTE, $value['type'])
                    && $this->writeType($fPtr, self::TAG_INT, count($value['value'])))) {
                    return false;
                }
                foreach ($value['value'] as $listItem) {
                    if (!$this->writeType($fPtr, $value['type'], $listItem)) {
                        return false;
                    }
                }

                return true;
            case self::TAG_COMPOUND: // Compound
                foreach ($value as $listItem) {
                    if (!$this->writeTag($fPtr, $listItem)) {
                        return false;
                    }
                }
                if (!is_int(fwrite($fPtr, "\0"))) {
                    return false;
                }

                return true;
            case self::TAG_INT_ARRAY: // Byte array
                return $this->writeType($fPtr, self::TAG_INT, count($value))
                    && is_int(fwrite(
                        $fPtr,
                        call_user_func_array(
                            'pack',
                            array_merge(array('N'.count($value)), $value)
                        )
                    ));
        }
    }

    /**
     * Add a value to the given node.
     *
     * @param \Tree\Node\Node $node
     */
    private function addValueToNode(&$node, $value)
    {
        $nodeValue = $node->getValue();
        $nodeValue['value'] = $value;
        $node->setValue($nodeValue);
    }
}
