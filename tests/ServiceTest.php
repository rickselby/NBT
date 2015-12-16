<?php

namespace Nbt\Tests;

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamFile;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public $service, $vRoot, $vFile, $dataHandler;

    public function testLoadFile()
    {
        $this->service->setMethods(['readFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('readFilePointer');

        $service->loadFile($this->vFile->url(), '');
    }

    public function testLoadFileNotExistsTriggersError()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->service->setMethods(null);

        $service = $this->getServiceMock();

        $service->loadFile('', '');
    }

    public function testLoadFileNotExistsReturnsFalse()
    {
        $warningEnabledOrig = \PHPUnit_Framework_Error_Warning::$enabled;
        \PHPUnit_Framework_Error_Warning::$enabled = FALSE;
        $errorReporting = error_reporting(0);

        $this->service->setMethods(null);

        $service = $this->getServiceMock();

        $this->assertFalse($service->loadFile('', ''));

        \PHPUnit_Framework_Error_Warning::$enabled = $warningEnabledOrig;
        error_reporting($errorReporting);
    }

    public function testReadString()
    {
        $this->service->setMethods(['readFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('readFilePointer');

        $service->readString('astring');
    }

    public function testReadFilePointerEmptyFile()
    {
        $this->service->setMethods(null);
        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'rb');

        $this->assertFalse($service->readFilePointer($fPtr));
    }

    public function testReadFilePointerValid()
    {
        $this->service->setMethods(null);
        $service = $this->getServiceMock();

        $this->dataHandler->expects($this->any())
            ->method('getTAGByte')->willReturn(-1);

        $fPtr = fopen('tests/Data/smalltest.nbt', 'rb');

        $this->assertInstanceOf('\Nbt\Node', $service->readFilePointer($fPtr));
    }

    public function testReadFilePointerValidButEmpty()
    {
        $this->service->setMethods(null);
        $service = $this->getServiceMock();

        $fPtr = fopen('tests/Data/smalltest.nbt', 'rb');

        $this->assertFalse($service->readFilePointer($fPtr));
    }

    public function testWriteString()
    {
        $this->service->setMethods(['writeFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('writeFilePointer');

        $service->writeString('astring');
    }

    public function testWriteStringReturnsString()
    {
        $this->service->setMethods(['writeFilePointer']);
        $service = $this->getServiceMock();


        $this->assertInternalType('string', $service->writeString(new \Nbt\Node()));
    }

    public function testWriteFile()
    {
        $this->service->setMethods(['writeFilePointer']);
        $service = $this->getServiceMock();

        $service->expects($this->once())->method('writeFilePointer');

        $service->writeFile($this->vFile->url(), new \Nbt\Node, '');
    }

    public function testWriteFilePointerTriggersErrorIfTreeEmpty()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');

        $this->service->setMethods(null);
        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'wb');
        $service->writeFilePointer($fPtr, new \Nbt\Node());
    }

    public function testWriteFilePointerReturnsFalseIfTreeEmpty()
    {
        $warningEnabledOrig = \PHPUnit_Framework_Error_Warning::$enabled;
        \PHPUnit_Framework_Error_Warning::$enabled = FALSE;
        $errorReporting = error_reporting(0);

        $this->service->setMethods(null);

        $service = $this->getServiceMock();

        $fPtr = fopen($this->vFile->url(), 'wb');
        $this->assertFalse($service->writeFilePointer($fPtr, new \Nbt\Node()));

        \PHPUnit_Framework_Error_Warning::$enabled = $warningEnabledOrig;
        error_reporting($errorReporting);
    }

    public function testWriteFilePointerWorks()
    {
        $this->service->setMethods(null);
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

    public function setUp()
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
