<?php

namespace Nbt;

class DataHandler
{
    /**
     * Read a byte tag from the file.
     *
     * @param resource $fPtr
     *
     * @return byte
     */
    public function getTAGByte($fPtr)
    {
        return unpack('c', fread($fPtr, 1))[1];
    }

    /**
     * Write a byte tag to the file.
     *
     * @param resource $fPtr
     * @param byte     $byte
     *
     * @return bool
     */
    public function putTAGByte($fPtr, $byte)
    {
        return is_int(fwrite($fPtr, pack('c', $byte)));
    }

    /**
     * Read a string from the file.
     *
     * @param resource $fPtr
     *
     * @return string
     */
    public function getTAGString($fPtr)
    {
        if (!$stringLength = $this->getTAGShort($fPtr)) {
            return '';
        }
        // Read in number of bytes specified by string length, and decode from utf8.
        return utf8_decode(fread($fPtr, $stringLength));
    }

    /**
     * Write a string to the file.
     *
     * @param resource $fPtr
     * @param string   $string
     *
     * @return bool
     */
    public function putTAGString($fPtr, $string)
    {
        $value = utf8_encode($string);

        return $this->putTAGShort($fPtr, strlen($value))
                && is_int(fwrite($fPtr, $value));
    }

    /**
     * Read a short int from the file.
     *
     * @param resource $fPtr
     *
     * @return int
     */
    public function getTAGShort($fPtr)
    {
        return $this->unsignedToSigned(unpack('n', fread($fPtr, 2))[1], 16);
    }

    /**
     * Write a short int to the file.
     *
     * @param resource $fPtr
     * @param int      $short
     *
     * @return bool
     */
    public function putTAGShort($fPtr, $short)
    {
        return is_int(fwrite($fPtr, pack('n', $this->signedToUnsigned($short, 16))));
    }

    /**
     * Get an int from the file.
     *
     * @param resource $fPtr
     *
     * @return int
     */
    public function getTAGInt($fPtr)
    {
        return $this->unsignedToSigned(unpack('N', fread($fPtr, 4))[1], 32);
    }

    /**
     * Write an integer to the file.
     *
     * @param resource $fPtr
     * @param int      $int
     *
     * @return bool
     */
    public function putTAGInt($fPtr, $int)
    {
        return is_int(fwrite($fPtr, pack('N', $this->signedToUnsigned($int, 32))));
    }

    /**
     * Read a long int from the file.
     *
     * @param resource $fPtr
     *
     * @return int
     */
    public function getTAGLong($fPtr)
    {
        list(, $firstHalf, $secondHalf) = unpack('N*', fread($fPtr, 8));
        if ($this->is64bit()) {
            // Workaround for PHP bug #47564 in 64-bit PHP<=5.2.9
            $firstHalf &= 0xFFFFFFFF;
            $secondHalf &= 0xFFFFFFFF;

            $value = ($firstHalf << 32) | $secondHalf;

            $value = $this->unsignedToSigned($value, 64);
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
    }

    /**
     * Write a long int to the file.
     *
     * @param resource $fPtr
     * @param int      $long
     *
     * @return bool
     */
    public function putTAGLong($fPtr, $long)
    {
        if ($this->is64bit()) {
            $firstHalf = ($long & 0xFFFFFFFF00000000) >> 32;
            $secondHalf = $long & 0xFFFFFFFF;

            $wResult = is_int(fwrite($fPtr, pack('NN', $firstHalf, $secondHalf)));
        } else {
            if (!extension_loaded('gmp')) {
                trigger_error(
                    'This file contains a 64-bit number and execution cannot continue. '
                    .'Please install the GMP extension for 64-bit number handling.',
                    E_USER_ERROR
                );
            }

            // 32-bit longs seem to be too long for pack() on 32-bit machines. Split into 4x16-bit instead.
            $quarters[ 0 ] = gmp_div(gmp_and($long, '0xFFFF000000000000'), gmp_pow(2, 48));
            $quarters[ 1 ] = gmp_div(gmp_and($long, '0x0000FFFF00000000'), gmp_pow(2, 32));
            $quarters[ 2 ] = gmp_div(gmp_and($long, '0x00000000FFFF0000'), gmp_pow(2, 16));
            $quarters[ 3 ] = gmp_and($long, '0xFFFF');

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
    }

    /**
     * Read a float from the file.
     *
     * @param resource $fPtr
     *
     * @return float
     */
    public function getTAGFloat($fPtr)
    {
        list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
            ? unpack('f', fread($fPtr, 4))
            : unpack('f', strrev(fread($fPtr, 4)));

        return $value;
    }

    /**
     * Write a float to the file.
     *
     * @param resource $fPtr
     * @param float    $float
     *
     * @return bool
     */
    public function putTAGFloat($fPtr, $float)
    {
        return is_int(
            fwrite(
                $fPtr,
                (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('f', $float)
                    : strrev(pack('f', $float))
            )
        );
    }

    /**
     * Read a double from the file.
     *
     * @param resource $fPtr
     *
     * @return float
     */
    public function getTAGDouble($fPtr)
    {
        list(, $value) = (pack('d', 1) == "\77\360\0\0\0\0\0\0")
            ? unpack('d', fread($fPtr, 8))
            : unpack('d', strrev(fread($fPtr, 8)));

        return $value;
    }

    /**
     * Write a double to the file.
     *
     * @param resource $fPtr
     * @param float    $double
     *
     * @return bool
     */
    public function putTAGDouble($fPtr, $double)
    {
        return is_int(
            fwrite(
                $fPtr,
                (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                    ? pack('d', $double)
                    : strrev(pack('d', $double))
            )
        );
    }

    /**
     * Read an array of bytes from the file.
     *
     * @param resource $fPtr
     *
     * @return byte[]
     */
    public function getTAGByteArray($fPtr)
    {
        $arrayLength = $this->getTAGInt($fPtr);

        return array_values(unpack('c*', fread($fPtr, $arrayLength)));
    }

    /**
     * Write an array of bytes to the file.
     *
     * @param resource $fPtr
     * @param byte[]   $array
     *
     * @return bool
     */
    public function putTAGByteArray($fPtr, $array)
    {
        return $this->putTAGInt($fPtr, count($array))
            && is_int(fwrite(
                $fPtr,
                call_user_func_array(
                    'pack',
                    array_merge(['c'.count($array)], $array)
                )
            ));
    }

    /**
     * Read an array of integers from the file.
     *
     * @param resource $fPtr
     *
     * @return int[]
     */
    public function getTAGIntArray($fPtr)
    {
        $arrayLength = $this->getTAGInt($fPtr);

        $values = [];
        for ($i = 0; $i < $arrayLength; ++$i) {
            $values[] = $this->getTAGInt($fPtr);
        }

        return $values;
    }

    /**
     * Write an array of integers to the file.
     *
     * @param resource $fPtr
     * @param int[]    $array
     *
     * @return bool
     */
    public function putTAGIntArray($fPtr, $array)
    {
        return $this->putTAGInt($fPtr, count($array))
            && is_int(fwrite(
                $fPtr,
                call_user_func_array(
                    'pack',
                    array_merge(['N'.count($array)], $array)
                )
            ));
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
        if ($value >= (int) pow(2, $size - 1)) {
            $value -= (int) pow(2, $size);
        }

        return $value;
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
            $value += (int) pow(2, $size);
        }

        return $value;
    }

    /**
     * Check if we're on a 64 bit machine
     *
     * @return boolean
     */
    public function is64bit()
    {
        return (PHP_INT_SIZE >= 8);
    }
}
