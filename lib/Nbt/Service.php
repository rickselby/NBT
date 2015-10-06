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
    public $root = array();
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

    public function loadFile($filename, $wrapper = 'compress.zlib://')
    {
        if (is_file($filename))
        {
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

    public function writeFile($filename, $wrapper = 'compress.zlib://')
    {
        if ($this->verbose) {
            trigger_error("Writing file \"{$filename}\" with stream wrapper \"{$wrapper}\".", E_USER_NOTICE);
        }
        $fPtr = fopen("{$wrapper}{$filename}", 'wb');
        $this->writeFilePointer($fPtr);
        fclose($fPtr);
    }

    public function readFilePointer($fPtr)
    {
        if ($this->verbose) {
            trigger_error('Traversing first tag in file.', E_USER_NOTICE);
        }

        $this->traverseTag($fPtr, $this->root);

        if ($this->verbose) {
            trigger_error('Encountered end tag for first tag; finished.', E_USER_NOTICE);
        }

        return end($this->root);
    }

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
        return true;
    }

    public function readString($string)
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $string);
        rewind($stream);
        return $this->readFilePointer($stream);
    }

    public function writeString()
    {
        $stream = fopen('php://memory', 'r+b');
        $this->writeFilePointer($stream);
        rewind($stream);
        return stream_get_contents($stream);
    }

    public function purge()
    {
        if ($this->verbose) {
            trigger_error('Purging all loaded data', E_USER_ERROR);
        }
        $this->root = array();
    }

    public function traverseTag($fPtr, &$tree)
    {
        if (feof($fPtr)) {
            if ($this->verbose) {
                trigger_error('Reached end of file/resource.', E_USER_NOTICE);
            }

            return false;
        }
        $tagType = $this->readType($fPtr, self::TAG_BYTE); // Read type byte.
        if ($tagType == self::TAG_END) {
            return false;
        } else {
            if ($this->verbose) {
                $position = ftell($fPtr);
            }
            $tagName = $this->readType($fPtr, self::TAG_STRING);
            if ($this->verbose) {
                trigger_error("Reading tag \"{$tagName}\" at offset {$position}.", E_USER_NOTICE);
            }
            $tagData = $this->readType($fPtr, $tagType);
            $tree[ ] = array('type' => $tagType, 'name' => $tagName, 'value' => $tagData);

            return true;
        }
    }

    public function writeTag($fPtr, $tag)
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

    public function readType($fPtr, $tagType)
    {
        switch ($tagType) {
            case self::TAG_BYTE: // Signed byte (8 bit)
                list(, $unpacked) = unpack('c', fread($fPtr, 1));

                return $unpacked;
            case self::TAG_SHORT: // Signed short (16 bit, big endian)
                list(, $unpacked) = unpack('n', fread($fPtr, 2));
                if ($unpacked >= pow(2, 15)) {
                    $unpacked -= pow(2, 16);
                } // Convert unsigned short to signed short.
                return $unpacked;
            case self::TAG_INT: // Signed integer (32 bit, big endian)
                list(, $unpacked) = unpack('N', fread($fPtr, 4));
                if ($unpacked >= pow(2, 31)) {
                    $unpacked -= pow(2, 32);
                } // Convert unsigned int to signed int
                return $unpacked;
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

                return $value;
            case self::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('f', fread($fPtr, 4))
                    : unpack('f', strrev(fread($fPtr, 4)));

                return $value;
            case self::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? unpack('d', fread($fPtr, 8))
                    : unpack('d', strrev(fread($fPtr, 8)));

                return $value;
            case self::TAG_BYTE_ARRAY: // Byte array
                $arrayLength = $this->readType($fPtr, self::TAG_INT);
                $array = array_values(unpack('c*', fread($fPtr, $arrayLength)));

                return $array;
            case self::TAG_STRING: // String
                if (!$stringLength = $this->readType($fPtr, self::TAG_SHORT)) {
                    return '';
                }
                // Read in number of bytes specified by string length, and decode from utf8.
                $string = utf8_decode(fread($fPtr, $stringLength));

                return $string;
            case self::TAG_LIST: // List
                $tagID = $this->readType($fPtr, self::TAG_BYTE);
                $listLength = $this->readType($fPtr, self::TAG_INT);
                if ($this->verbose) {
                    trigger_error("Reading in list of {$listLength} tags of type {$tagID}.", E_USER_NOTICE);
                }
                $list = array('type' => $tagID, 'value' => array());
                for ($i = 0; $i < $listLength; ++$i) {
                    if (feof($fPtr)) {
                        break;
                    }
                    $list['value'][] = $this->readType($fPtr, $tagID);
                }

                return $list;
            case self::TAG_COMPOUND: // Compound
                $tree = array();
                while ($this->traverseTag($fPtr, $tree)) {
                }

                return $tree;
            case self::TAG_INT_ARRAY:
                $arrayLength = $this->readType($fPtr, self::TAG_INT);
                $array = array_values(unpack('N*', fread($fPtr, $arrayLength * 4)));

                return $array;
        }
    }

    public function writeType($fPtr, $tagType, $value)
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
}
