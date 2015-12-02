<?php

namespace Nbt\Tests;

use Nbt\DataHandler;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;

class DataHandlerPutTest extends \PHPUnit_Framework_TestCase
{
    private $vRoot;
    private $vFile;
    private $fPtr;

    public function setUp()
    {
        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('sample.nbt');
        $this->vRoot->addChild($this->vFile);
        $this->fPtr = fopen($this->vFile->url(), 'wb');
    }

    public function tearDown()
    {
        fclose($this->fPtr);
    }

    /**
     * @dataProvider providerTestPutTAGByte
     */
    public function testPutTAGByte($value)
    {
        DataHandler::putTAGByte($this->fPtr, $value);

        $this->assertSame(
            pack('c', $value),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGByte()
    {
        return [
            'smallest' => [-128],
            'negative' => [-34],
            'zero' => [0],
            'positive' => [52],
            'largest' => [127],
        ];
    }

    /**
     * @dataProvider providerTestPutTAGString
     */
    public function testPutTAGString($value)
    {
        DataHandler::putTAGString($this->fPtr, $value);

        $this->assertSame(
            pack('n', strlen($value)).utf8_encode($value),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGString()
    {
        return [['z'], ['words!'], ['averylongexampleimnotsurehowlongtheycanbe']];
    }

    /**
     * @dataProvider providerTestPutTAGShort
     */
    public function testPutTAGShort($value)
    {
        DataHandler::putTAGShort($this->fPtr, $value);

        $this->assertSame(
            pack('n', $value),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGShort()
    {
        return [
            'smallest' => [-32767],
            'negative' => [-12345],
            'zero' => [0],
            'positive' => [23456],
            'largest' => [32676],
        ];
    }

    /**
     * @dataProvider providerTestPutTAGInt
     */
    public function testPutTAGInt($value)
    {
        DataHandler::putTAGInt($this->fPtr, $value);

        $this->assertSame(
            pack('N', $value),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGInt()
    {
        return [
            // using -2147483648 becomes a float on 32 bit machines...
            'smallest' => [pow(-2,31)],
            'negative' => [-23456789],
            'zero' => [0],
            'positive' => [1234567],
            'largest' => [2147483647],
        ];
    }

    /**
     * @dataProvider providerTestPutTAGLong
     */
    public function testPutTAGLong($value)
    {
        if (PHP_INT_SIZE >= 8) {
            $firstHalf = ($value & 0xFFFFFFFF00000000) >> 32;
            $secondHalf = $value & 0xFFFFFFFF;

            $binary = pack('NN', $firstHalf, $secondHalf);
        } elseif (extension_loaded('gmp')) {
            // 32-bit longs seem to be too long for pack() on 32-bit machines. Split into 4x16-bit instead.
            $quarters[0] = gmp_div(gmp_and($value, '0xFFFF000000000000'), gmp_pow(2, 48));
            $quarters[1] = gmp_div(gmp_and($value, '0x0000FFFF00000000'), gmp_pow(2, 32));
            $quarters[2] = gmp_div(gmp_and($value, '0x00000000FFFF0000'), gmp_pow(2, 16));
            $quarters[3] = gmp_and($value, '0xFFFF');

            $binary = pack(
                'nnnn',
                gmp_intval($quarters[0]),
                gmp_intval($quarters[1]),
                gmp_intval($quarters[2]),
                gmp_intval($quarters[3])
            );
        } else {
            // no way of testing 64-bit numbers here. do nothing.
            return;
        }

        DataHandler::putTAGLong($this->fPtr, $value);

        $this->assertSame(
            $binary,
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGLong()
    {
        // Values are stated as strings, then convert to ints if it's a 64 bit
        // machine, or passed through gmp_strval() (probably not necessary, actually)
        // if a 32 bit machine
        $values = [
                'smallest' => ['-9223372036854775808'],
                'negative' => ['-23456789012345'],
                'zero' => ['0'],
                'positive' => ['12345678901234'],
                'largest' => ['9223372036854775807'],
            ];

        if (PHP_INT_SIZE >= 8) {
            array_walk($values, function(&$value) {
                $value = [intval($value[0])];
            });
        } elseif (extension_loaded('gmp')) {
            array_walk($values, function(&$value) {
                $value = [gmp_strval(gmp_init($value[0]))];
            });
        } else {
            $values = [[0]];
        }
        return $values;
    }

    /**
     * @dataProvider providerTestPutTAGFloat
     */
    public function testPutTAGFloat($value)
    {
        DataHandler::putTAGFloat($this->fPtr, $value);

        $this->assertSame(
            (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                ? pack('f', $value)
                : strrev(pack('f', $value)),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGFloat()
    {
        $values = [
            // using -2147483648 becomes a float on 32 bit machines...
            'smallest' => [-3.4 * pow(10, 38)],
            'negative' => [-6.7 * pow(10, 10)],
            'zero' => [0.0],
            'positive' => [6.7 * pow(10, 10)],
            'largest' => [3.4 * pow(10, 38)],
        ];

        // Force a single-precision float value by packing and unpacking
        array_walk($values, function(&$value) {
            $value = [unpack('f', pack('f', $value[0]))[1]];
        });

        return $values;
    }


    /**
     * @dataProvider providerTestPutTAGDouble
     */
    public function testPutTAGDouble($value)
    {
        DataHandler::putTAGDouble($this->fPtr, $value);

        $this->assertSame(
            (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                ? pack('d', $value)
                : strrev(pack('d', $value)),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGDouble()
    {
        $values = [
            // using -2147483648 becomes a float on 32 bit machines...
            'smallest' => [-1.8 * pow(10, 308)],
            'negative' => [-6.7 * pow(10, 150)],
            'zero' => [0.0],
            'positive' => [6.7 * pow(10, 150)],
            'largest' => [1.8 * pow(10, 308)],
        ];

        return $values;
    }

    /**
     * @dataProvider providerTestPutTAGByteArray
     */
    public function testPutTAGByteArray($value)
    {
        DataHandler::putTAGByteArray($this->fPtr, $value);

        $this->assertSame(
            pack('N', count($value))
            .call_user_func_array(
                'pack',
                array_merge(['c'.count($value)], $value)
            ),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGByteArray()
    {
        return [
            'small values' => [[-128, -127, -126]],
            'large values' => [[125, 126, 127]],
            'zeros' => [[0,0,0]],
            'single' => [[52]],
            'longarray' => [array_fill(0, 255, 77)],
        ];
    }

    /**
     * @dataProvider providerTestPutTAGIntArray
     */
    public function testPutTAGIntArray($value)
    {
        DataHandler::putTAGIntArray($this->fPtr, $value);

        $this->assertSame(
            pack('N', count($value))
            .call_user_func_array(
                'pack',
                array_merge(['N'.count($value)], $value)
            ),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGIntArray()
    {
        return [
            'small values' => [[pow(-2,31), -2147483647, -2147483646]],
            'large values' => [[2147483645, 2147483646, 2147483647]],
            'zeros' => [[0,0,0]],
            'single' => [[158976]],
            'longarray' => [array_fill(0, 255, -686842)],
        ];
    }

}


