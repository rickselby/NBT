<?php

namespace Nbt\Tests;

use Nbt\DataHandler;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;

class DataHandlerPutTest extends TestCase
{
    private $vRoot;
    private $vFile;
    private $fPtr;
    public $dataHandler;

    public function setUp(): void
    {
        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('sample.nbt');
        $this->vRoot->addChild($this->vFile);
        $this->fPtr = fopen($this->vFile->url(), 'wb');

        $this->dataHandler = new DataHandler();
    }

    public function tearDown(): void
    {
        fclose($this->fPtr);
    }

    /**
     * @dataProvider providerTestPutTAGByte
     */
    public function testPutTAGByte($value)
    {
        $this->dataHandler->putTAGByte($this->fPtr, $value);

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
        $this->dataHandler->putTAGString($this->fPtr, $value);

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
        $this->dataHandler->putTAGShort($this->fPtr, $value);

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
        $this->dataHandler->putTAGInt($this->fPtr, $value);

        $this->assertSame(
            pack('N', $value),
            $this->vFile->getContent()
        );
    }

    public function providerTestPutTAGInt()
    {
        return [
            // using -2147483648 becomes a float on 32 bit machines...
            'smallest' => [pow(-2, 31)],
            'negative' => [-23456789],
            'zero' => [0],
            'positive' => [1234567],
            'largest' => [2147483647],
        ];
    }

    /**
     * @dataProvider providerTestPutTAGFloat
     */
    public function testPutTAGFloat($value)
    {
        $this->dataHandler->putTAGFloat($this->fPtr, $value);

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
        array_walk($values, function (&$value) {
            $value = [unpack('f', pack('f', $value[0]))[1]];
        });

        return $values;
    }


    /**
     * @dataProvider providerTestPutTAGDouble
     */
    public function testPutTAGDouble($value)
    {
        $this->dataHandler->putTAGDouble($this->fPtr, $value);

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
        $this->dataHandler->putTAGByteArray($this->fPtr, $value);

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
        $this->dataHandler->putTAGIntArray($this->fPtr, $value);

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
            'small values' => [[pow(-2, 31), -2147483647, -2147483646]],
            'large values' => [[2147483645, 2147483646, 2147483647]],
            'zeros' => [[0,0,0]],
            'single' => [[158976]],
            'longarray' => [array_fill(0, 255, -686842)],
        ];
    }
}
