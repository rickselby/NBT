<?php

namespace Nbt\Tests;

use Nbt\DataHandler;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use \org\bovigo\vfs\content\StringBasedFileContent;
use PHPUnit\Framework\TestCase;

class DataHandlerLong32Test extends TestCase
{
    private $vRoot;
    private $vFile;
    public $dataHandler;

    /**
     * @dataProvider providerTestTAGLong
     */
    public function testGetTAGLong($value)
    {
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

        $fPtr = $this->setContentAndOpen($binary);
        $string = $this->dataHandler->getTAGLong($fPtr);
        fclose($fPtr);
        $this->assertSame($value, $string);
    }

    /**
     * @dataProvider providerTestTAGLong
     */
    public function testPutTAGLong($value)
    {
        $fPtr = fopen($this->vFile->url(), 'wb');

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

        $this->dataHandler->putTAGLong($fPtr, $value);

        $this->assertSame(
            $binary,
            $this->vFile->getContent()
        );
    }

    public function providerTestTAGLong()
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

        // Tests won't run if PHP_INT_SIZE < 8, but not certain the providers
        // won't be initialised...

        if (extension_loaded('gmp')) {
            array_walk($values, function (&$value) {
                $value = [gmp_strval(gmp_init($value[0]))];
            });
        }

        return $values;
    }

    /*************************************************************************/

    public function setUp(): void
    {
        if (PHP_INT_SIZE >= 8) {
            $this->dataHandler = $this->getMockBuilder('\Nbt\DataHandler')
                ->onlyMethods(['is64bit'])
                ->getMock();
            $this->dataHandler->expects($this->any())->method('is64bit')->willReturn(false);
        } else {
            $this->dataHandler = new DataHandler();
        }

        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('No GMP extension found; cannot test 32 bit functionality');
        }

        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('sample.nbt');
        $this->vRoot->addChild($this->vFile);
    }

    private function setContentAndOpen($binary)
    {
        $content = new StringBasedFileContent($binary);
        $this->vFile->setContent($content);
        return fopen($this->vFile->url(), 'rb');
    }
}
