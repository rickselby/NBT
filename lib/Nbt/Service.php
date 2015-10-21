<?php

/**
 * Class for reading in NBT-format files.
 *
 * @author  Justin Martin <frozenfire@thefrozenfire.com>
 * @author  Rick Selby <rick@selby-family.co.uk>
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

    /**
     * Load a file and read the NBT data from the file.
     *
     * @param string $filename File to open
     * @param string $wrapper  [optional] Stream wrapper if not zlib
     *
     * @return Node|false
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
     * @param Node   $tree     Root of the tree to write
     * @param string $wrapper  [optional] Stream wrapper if not zlib
     *
     * @return true
     */
    public function writeFile($filename, $tree, $wrapper = 'compress.zlib://')
    {
        if ($this->verbose) {
            trigger_error("Writing file \"{$filename}\" with stream wrapper \"{$wrapper}\".", E_USER_NOTICE);
        }
        $fPtr = fopen("{$wrapper}{$filename}", 'wb');
        $this->writeFilePointer($fPtr, $tree);
        fclose($fPtr);
    }

    /**
     * Read NBT data from the given file pointer.
     *
     * @param resource $fPtr File/Stream pointer
     *
     * @return Node
     */
    public function readFilePointer($fPtr)
    {
        if ($this->verbose) {
            trigger_error('Traversing first tag in file.', E_USER_NOTICE);
        }

        $treeRoot = new Node();
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
     * @param Node     $tree Root of the tree to write
     */
    public function writeFilePointer($fPtr, $tree)
    {
        if (!$this->writeTag($fPtr, $tree)) {
            trigger_error('Failed to write tree to file/resource.', E_USER_WARNING);
        }
    }

    /**
     * Read NBT data from a string.
     *
     * @param string $string String containing NBT data
     *
     * @return Node
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
     * @param Node $tree Root of the tree to write
     *
     * @return string
     */
    public function writeString($tree)
    {
        $stream = fopen('php://memory', 'r+b');
        $this->writeFilePointer($stream, $tree);
        rewind($stream);

        return stream_get_contents($stream);
    }

    /**
     * Read the next tag from the stream.
     *
     * @param resource $fPtr Stream pointer
     * @param Node     $node Tree array to write to
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
        if ($tagType == Tag::TAG_END) {
            return false;
        } else {
            $node->setType($tagType);
            if ($this->verbose) {
                $position = ftell($fPtr);
            }
            $tagName = $this->getTAGString($fPtr);
            if ($this->verbose) {
                trigger_error("Reading tag \"{$tagName}\" at offset {$position}.", E_USER_NOTICE);
            }
            $node->setName($tagName);
            $this->readType($fPtr, $tagType, $node);

            return true;
        }
    }

    /**
     * Write the given tag to the stream.
     *
     * @param resource $fPtr Stream pointer
     * @param Node     $node Tag to write
     *
     * @return bool
     */
    private function writeTag($fPtr, $node)
    {
        if ($this->verbose) {
            $position = ftell($fPtr);
            trigger_error(
                "Writing tag \"{$node->getName()}\" of type {$node->getType()} at offset {$position}.",
                E_USER_NOTICE
            );
        }

        return $this->putTAGByte($fPtr, $node->getType())
            && $this->putTAGString($fPtr, $node->getName())
            && $this->writeType($fPtr, $node->getType(), $node);
    }

    /**
     * Read an individual type from the stream.
     *
     * @param resource $fPtr    Stream pointer
     * @param int      $tagType Tag to read
     * @param Node     $node    Node to add data to
     *
     * @return mixed
     */
    private function readType($fPtr, $tagType, $node = null)
    {
        switch ($tagType) {
            case Tag::TAG_BYTE: // Signed byte (8 bit)
                $node->setValue($this->getTAGByte($fPtr));
                break;
            case Tag::TAG_SHORT: // Signed short (16 bit, big endian)
                $node->setValue($this->getTAGShort($fPtr));
                break;
            case Tag::TAG_INT: // Signed integer (32 bit, big endian)
                $node->setValue($this->getTAGInt($fPtr));
                break;
            case Tag::TAG_LONG: // Signed long (64 bit, big endian)
                list(, $firstHalf, $secondHalf) = unpack('N*', fread($fPtr, 8));
                if (PHP_INT_SIZE >= 8) {
                    // Workaround for PHP bug #47564 in 64-bit PHP<=5.2.9
                    $firstHalf &= 0xFFFFFFFF;
                    $secondHalf &= 0xFFFFFFFF;

                    $value = ($firstHalf << 32) | $secondHalf;
                    if ($value >= pow(2, 63)) {
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
                $node->setValue($value);
                break;
            case Tag::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('f', fread($fPtr, 4))
                    : unpack('f', strrev(fread($fPtr, 4)));
                $node->setValue($value);
                break;
            case Tag::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('d', fread($fPtr, 8))
                    : unpack('d', strrev(fread($fPtr, 8)));
                $node->setValue($value);
                break;
            case Tag::TAG_BYTE_ARRAY: // Byte array
                $arrayLength = $this->getTAGInt($fPtr);
                $array = array_values(unpack('c*', fread($fPtr, $arrayLength)));
                $node->setValue($array);
                break;
            case Tag::TAG_STRING: // String
                $node->setValue($this->getTAGString($fPtr));
                break;
            case Tag::TAG_LIST: // List
                $tagID = $this->getTAGByte($fPtr);
                $listLength = $this->getTAGInt($fPtr);
                if ($this->verbose) {
                    trigger_error("Reading in list of {$listLength} tags of type {$tagID}.", E_USER_NOTICE);
                }

                // Add a reference to the payload type
                $node->setPayloadType($tagID);

                for ($i = 0; $i < $listLength; ++$i) {
                    if (feof($fPtr)) {
                        break;
                    }
                    $listNode = new Node();
                    $this->readType($fPtr, $tagID, $listNode);
                    $node->addChild($listNode);
                }
                break;
            case Tag::TAG_COMPOUND: // Compound
                // Uck. Don't know a better way to do this,
                $compoundNode = new Node();
                while ($this->traverseTag($fPtr, $compoundNode)) {
                    $node->addChild($compoundNode);
                    // Reset the node for adding the next tags
                    $compoundNode = new Node();
                }
                break;
            case Tag::TAG_INT_ARRAY:
                $arrayLength = $this->getTAGInt($fPtr);
                $array = array_values(unpack('N*', fread($fPtr, $arrayLength * 4)));
                $node->setValue($array);
                break;
        }
    }

    /**
     * Read a byte tag from the file.
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
     * Read a string from the file.
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
     * Read a short int from the file.
     *
     * @param resource $fPtr
     *
     * @return int
     */
    private function getTAGShort($fPtr)
    {
        return $this->unsignedToSigned(
            unpack('n', fread($fPtr, 2))[1],
            16
        );
    }

    /**
     * Get an int from the file.
     *
     * @param resource $fPtr
     *
     * @return int
     */
    private function getTAGInt($fPtr)
    {
        return $this->unsignedToSigned(
            unpack('N', fread($fPtr, 4))[1],
            32
        );
    }

    /**
     * Convert an unsigned int to signed, if required.
     *
     * @param int $value
     * @param int $size
     *
     * @return int
     */
    private function unsignedToSigned($value, $size)
    {
        if ($value >= pow(2, $size - 1)) {
            $value -= pow(2, $size);
        }

        return $value;
    }

    /**
     * Write an individual type to the stream.
     *
     * @param resource $fPtr    Stream pointer
     * @param int      $tagType Type of tag to write
     * @param Node     $node    Node containing value to write
     *
     * @return bool
     */
    private function writeType($fPtr, $tagType, $node)
    {
        switch ($tagType) {
            case Tag::TAG_BYTE: // Signed byte (8 bit)
                return $this->putTAGByte($fPtr, $node->getValue());
            case Tag::TAG_SHORT: // Signed short (16 bit, big endian)
                return $this->putTAGShort($fPtr, $node->getValue());
            case Tag::TAG_INT: // Signed integer (32 bit, big endian)
                return $this->putTAGInt($fPtr, $node->getValue());
            case Tag::TAG_LONG: // Signed long (64 bit, big endian)
                $value = $node->getValue();
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
            case Tag::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                $value = $node->getValue();

                return is_int(fwrite($fPtr, (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('f', $value)
                    : strrev(pack('f', $value))));
            case Tag::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                $value = $node->getValue();

                return is_int(fwrite($fPtr, (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('d', $value)
                    : strrev(pack('d', $value))));
            case Tag::TAG_BYTE_ARRAY: // Byte array
                $value = $node->getValue();

                return $this->putTAGInt($fPtr, count($value))
                    && is_int(fwrite(
                        $fPtr,
                        call_user_func_array(
                            'pack',
                            array_merge(['c'.count($value)], $value)
                        )
                    ));
            case Tag::TAG_STRING: // String
                return $this->putTAGString($fPtr, $node->getValue());
            case Tag::TAG_LIST: // List
                if ($this->verbose) {
                    trigger_error(
                        'Writing list of '.count($node->getChildren())." tags of type {$node->getPayloadType()}.",
                        E_USER_NOTICE
                    );
                }
                if (!($this->putTAGByte($fPtr, $node->getPayloadType())
                    && $this->putTAGInt($fPtr, count($node->getChildren()))
                    )) {
                    return false;
                }
                foreach ($node->getChildren() as $childNode) {
                    if (!$this->writeType($fPtr, $node->getPayloadType(), $childNode)) {
                        return false;
                    }
                }

                return true;
            case Tag::TAG_COMPOUND: // Compound
                foreach ($node->getChildren() as $childNode) {
                    if (!$this->writeTag($fPtr, $childNode)) {
                        return false;
                    }
                }
                if (!$this->writeType($fPtr, Tag::TAG_END, null)) {
                    return false;
                }

                return true;
            case Tag::TAG_INT_ARRAY: // Byte array
                $value = $node->getValue();

                return $this->putTAGInt($fPtr, count($value))
                    && is_int(fwrite(
                        $fPtr,
                        call_user_func_array(
                            'pack',
                            array_merge(['N'.count($value)], $value)
                        )
                    ));
            case Tag::TAG_END: // End tag
                return is_int(fwrite($fPtr, "\0"));
        }
    }

    /**
     * Write a byte tag to the file.
     *
     * @param resource $fPtr
     * @param byte     $byte
     *
     * @return bool
     */
    private function putTAGByte($fPtr, $byte)
    {
        return is_int(fwrite($fPtr, pack('c', $byte)));
    }

    /**
     * Write a string to the file.
     *
     * @param resource $fPtr
     * @param string   $string
     *
     * @return bool
     */
    private function putTAGString($fPtr, $string)
    {
        $value = utf8_encode($string);

        return $this->putTAGShort($fPtr, strlen($value))
                && is_int(fwrite($fPtr, $value));
    }

    /**
     * Write a short int to the file.
     *
     * @param resource $fPtr
     * @param int      $short
     *
     * @return bool
     */
    private function putTAGShort($fPtr, $short)
    {
        return is_int(fwrite($fPtr, pack('n', $this->signedToUnsigned($short, 16))));
    }

    /**
     * Write an integer to the file.
     *
     * @param resource $fPtr
     * @param int      $int
     *
     * @return bool
     */
    private function putTAGInt($fPtr, $int)
    {
        return is_int(fwrite($fPtr, pack('N', $this->signedToUnsigned($int, 32))));
    }

    /**
     * Convert an unsigned int to signed, if required.
     *
     * @param int $value
     * @param int $size
     *
     * @return int
     */
    private function signedToUnsigned($value, $size)
    {
        if ($value < 0) {
            $value += pow(2, $size);
        }

        return $value;
    }
}
