<?php

namespace Nbt\Tests;

use Nbt\DataHandler;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use \org\bovigo\vfs\content\StringBasedFileContent;
use PHPUnit\Framework\TestCase;

class DataHandlerGetTest extends TestCase
{
    private $vRoot;
    private $vFile;
    public $dataHandler;

    public function setUp(): void
    {
        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('sample.nbt');
        $this->vRoot->addChild($this->vFile);

        $this->dataHandler = new DataHandler();
    }

    /**
     * @dataProvider providerTestGetTAGByte
     */
    public function testGetTAGByte($value)
    {
        $fPtr = $this->setContentAndOpen(pack('c', $value));
        $byte = $this->dataHandler->getTAGByte($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $byte);
    }

    public function providerTestGetTAGByte()
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
     * @dataProvider providerTestGetTAGString
     */
    public function testGetTAGString($value)
    {
        $fPtr = $this->setContentAndOpen(pack('n', strlen($value)).utf8_encode($value));
        $string = $this->dataHandler->getTAGString($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    public function providerTestGetTAGString()
    {
        return [['z'], ['words!'], ['averylongexampleimnotsurehowlongtheycanbe'], ['']];
    }

    /**
     * @dataProvider providerTestGetTAGShort
     */
    public function testGetTAGShort($value)
    {
        $fPtr = $this->setContentAndOpen(pack('n', $value));
        $string = $this->dataHandler->getTAGShort($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    public function providerTestGetTAGShort()
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
     * @dataProvider providerTestGetTAGInt
     */
    public function testGetTAGInt($value)
    {
        $fPtr = $this->setContentAndOpen(pack('N', $value));
        $string = $this->dataHandler->getTAGInt($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    public function providerTestGetTAGInt()
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
     * @dataProvider providerTestGetTAGFloat
     */
    public function testGetTAGFloat($value)
    {
        $fPtr = $this->setContentAndOpen(
            (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                ? pack('f', $value)
                : strrev(pack('f', $value))
        );
        $string = $this->dataHandler->getTAGFloat($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    public function providerTestGetTAGFloat()
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
     * @dataProvider providerTestGetTAGDouble
     */
    public function testGetTAGDouble($value)
    {
        $fPtr = $this->setContentAndOpen(
            (pack('d', 1) == "\77\360\0\0\0\0\0\0")
                ? pack('d', $value)
                : strrev(pack('d', $value))
        );
        $string = $this->dataHandler->getTAGDouble($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    public function providerTestGetTAGDouble()
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
     * @dataProvider providerTestGetTAGByteArray
     */
    public function testGetTAGByteArray($value)
    {
        $fPtr = $this->setContentAndOpen(
            pack('N', count($value))
            .call_user_func_array(
                'pack',
                array_merge(['c'.count($value)], $value)
            )
        );
        $byte = $this->dataHandler->getTAGByteArray($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $byte);
    }

    public function providerTestGetTAGByteArray()
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
     * @dataProvider providerTestGetTAGIntArray
     */
    public function testGetTAGIntArray($value)
    {
        $fPtr = $this->setContentAndOpen(
            pack('N', count($value))
            .call_user_func_array(
                'pack',
                array_merge(['N'.count($value)], $value)
            )
        );
        $int = $this->dataHandler->getTAGIntArray($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $int);
    }

    public function providerTestGetTAGIntArray()
    {
        return [
            'small values' => [[pow(-2, 31), -2147483647, -2147483646]],
            'large values' => [[2147483645, 2147483646, 2147483647]],
            'zeros' => [[0,0,0]],
            'single' => [[158976]],
            'longarray' => [array_fill(0, 255, -686842)],
        ];
    }

    /*************************************************************************/

    private function setContentAndOpen($binary)
    {
        $content = new StringBasedFileContent($binary);
        $this->vFile->setContent($content);
        return fopen($this->vFile->url(), 'rb');
    }
}
