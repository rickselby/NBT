<?php

namespace Nbt\Tests;

use Nbt\DataHandler;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use \org\bovigo\vfs\content\StringBasedFileContent;
use PHPUnit\Framework\TestCase;

class DataHandlerLong64Test extends TestCase
{
    private $vRoot;
    private $vFile;
    public $dataHandler;

    /**
     * @dataProvider providerTestTAGLong
     */
    public function testGetTAGLong($value)
    {
        $firstHalf = ($value & 0xFFFFFFFF00000000) >> 32;
        $secondHalf = $value & 0xFFFFFFFF;

        $binary = pack('NN', $firstHalf, $secondHalf);

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

        $firstHalf = ($value & 0xFFFFFFFF00000000) >> 32;
        $secondHalf = $value & 0xFFFFFFFF;

        $binary = pack('NN', $firstHalf, $secondHalf);

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
        if (PHP_INT_SIZE >= 8) {
            array_walk($values, function (&$value) {
                $value = [intval($value[0])];
            });
        }

        return $values;
    }

    /*************************************************************************/

    public function setUp(): void
    {
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('Can\'t test 64 bit code on 32-bit machine');
        }

        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('sample.nbt');
        $this->vRoot->addChild($this->vFile);

        $this->dataHandler = new DataHandler();
    }

    private function setContentAndOpen($binary)
    {
        $content = new StringBasedFileContent($binary);
        $this->vFile->setContent($content);
        return fopen($this->vFile->url(), 'rb');
    }
}
