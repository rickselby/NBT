<?php

namespace Nbt\Tests;

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\Error;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    public $dataHandler;
    public $service;
    public $vRoot;
    public $vFile;

    public function testLoadFile()
    {
        $this->service->onlyMethods(['readFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('readFilePointer');

        $service->loadFile($this->vFile->url(), '');
    }

    public function testLoadFileNotExistsTriggersError()
    {
        $this->expectWarning();
        $this->service->onlyMethods([]);

        $service = $this->getServiceMock();

        $service->loadFile('', '');
    }

    public function testLoadFileNotExistsReturnsFalse()
    {
        $errorReporting = error_reporting(0);

        $this->service->onlyMethods([]);

        $service = $this->getServiceMock();

        $this->assertFalse($service->loadFile('', ''));

        error_reporting($errorReporting);
    }

    public function testReadString()
    {
        $this->service->onlyMethods(['readFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('readFilePointer');

        $service->readString('astring');
    }

    public function testReadFilePointerEmptyFile()
    {
        $this->service->onlyMethods([]);
        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'rb');

        $this->assertFalse($service->readFilePointer($fPtr));
    }

    public function testReadFilePointerValid()
    {
        $this->service->onlyMethods([]);
        $service = $this->getServiceMock();

        $this->dataHandler->expects($this->any())
            ->method('getTAGByte')->willReturn(-1);

        $fPtr = fopen('tests/Data/smalltest.nbt', 'rb');

        $this->assertInstanceOf('\Nbt\Node', $service->readFilePointer($fPtr));
    }

    public function testReadFilePointerValidButEmpty()
    {
        $this->service->onlyMethods([]);
        $service = $this->getServiceMock();

        $fPtr = fopen('tests/Data/smalltest.nbt', 'rb');

        $this->assertFalse($service->readFilePointer($fPtr));
    }

    public function testWriteString()
    {
        $service = $this->getWriteStringServiceMock();

        $service->expects($this->once())->method('writeFilePointer');

        $service->writeString(new \Nbt\Node());
    }

    public function testWriteStringReturnsString()
    {
        $service = $this->getWriteStringServiceMock();

        $this->assertIsString($service->writeString(new \Nbt\Node()));
    }

    private function getWriteStringServiceMock()
    {
        $this->service->onlyMethods(['writeFilePointer']);
        return $this->getServiceMock();
    }

    public function testWriteFile()
    {
        $this->service->onlyMethods(['writeFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('writeFilePointer');

        $service->writeFile($this->vFile->url(), new \Nbt\Node, '');
    }

    public function testWriteFilePointerTriggersErrorIfTreeEmpty()
    {
        $this->expectError();

        $this->service->onlyMethods([]);
        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'wb');
        $service->writeFilePointer($fPtr, new \Nbt\Node());
    }

    public function testWriteFilePointerReturnsFalseIfTreeEmpty()
    {
        $errorReporting = error_reporting(0);

        $this->service->onlyMethods([]);

        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'wb');
        $this->assertFalse($service->writeFilePointer($fPtr, new \Nbt\Node()));

        error_reporting($errorReporting);
    }

    public function testWriteFilePointerWorks()
    {
        $this->service->onlyMethods([]);
        $service = $this->getServiceMock();

        $this->dataHandler->expects($this->any())
            ->method('putTAGByte')->willReturn(true);
        $this->dataHandler->expects($this->any())
            ->method('putTAGString')->willReturn(true);

        $fPtr = fopen($this->vFile->url(), 'wb');
        $service->writeFilePointer($fPtr, $this->smallTree());
        $this->assertNotEquals(0, filesize($this->vFile->url()));
    }

    /**************************************************************************/

    public function setUp(): void
    {
        $this->dataHandler = $this->getMockBuilder('\Nbt\DataHandler')
            ->getMock();

        $this->dataHandler->expects($this->any())->method('is64bit')->willReturn(true);

        $this->service = $this->getMockBuilder('\Nbt\Service')
            ->setConstructorArgs([$this->dataHandler]);

        $this->vRoot = vfsStream::setup();
        $this->vFile = new vfsStreamFile('test.nbt');
        $this->vRoot->addChild($this->vFile);
    }

    /**
     * @returns PHPUnit_Framework_MockObject_MockObject
     */
    public function getServiceMock()
    {
        return $this->service->getMock();
    }

    public function smallTree()
    {
        return (new \Nbt\Node())
            ->setType(\Nbt\Tag::TAG_COMPOUND)
            ->setName('hello world')
            ->setChildren([
                (new \Nbt\Node())
                    ->setType(\Nbt\Tag::TAG_STRING)
                    ->setName('name')
                    ->setValue('Bananrama')
            ]);
    }
}
