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
        $tagType = DataHandler::getTAGByte($fPtr);
        if ($tagType == Tag::TAG_END) {
            return false;
        } else {
            $node->setType($tagType);
            if ($this->verbose) {
                $position = ftell($fPtr);
            }
            $tagName = DataHandler::getTAGString($fPtr);
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

        return DataHandler::putTAGByte($fPtr, $node->getType())
            && DataHandler::putTAGString($fPtr, $node->getName())
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
                $node->setValue(DataHandler::getTAGByte($fPtr));
                break;
            case Tag::TAG_SHORT: // Signed short (16 bit, big endian)
                $node->setValue(DataHandler::getTAGShort($fPtr));
                break;
            case Tag::TAG_INT: // Signed integer (32 bit, big endian)
                $node->setValue(DataHandler::getTAGInt($fPtr));
                break;
            case Tag::TAG_LONG: // Signed long (64 bit, big endian)
                $node->setValue(DataHandler::getTAGLong($fPtr));
                break;
            case Tag::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                $node->setValue(DataHandler::getTAGFloat($fPtr));
                break;
            case Tag::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                $node->setValue(DataHandler::getTAGDouble($fPtr));
                break;
            case Tag::TAG_BYTE_ARRAY: // Byte array
                $node->setValue(DataHandler::getTAGByteArray($fPtr));
                break;
            case Tag::TAG_STRING: // String
                $node->setValue(DataHandler::getTAGString($fPtr));
                break;
            case Tag::TAG_INT_ARRAY:
                $node->setValue(DataHandler::getTAGIntArray($fPtr));
                break;
            case Tag::TAG_LIST: // List
                $tagID = DataHandler::getTAGByte($fPtr);
                $listLength = DataHandler::getTAGInt($fPtr);
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
        }
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
                return DataHandler::putTAGByte($fPtr, $node->getValue());
            case Tag::TAG_SHORT: // Signed short (16 bit, big endian)
                return DataHandler::putTAGShort($fPtr, $node->getValue());
            case Tag::TAG_INT: // Signed integer (32 bit, big endian)
                return DataHandler::putTAGInt($fPtr, $node->getValue());
            case Tag::TAG_LONG: // Signed long (64 bit, big endian)
                return DataHandler::putTAGLong($fPtr, $node->getValue());
            case Tag::TAG_FLOAT: // Floating point value (32 bit, big endian, IEEE 754-2008)
                return DataHandler::putTAGFloat($fPtr, $node->getValue());
            case Tag::TAG_DOUBLE: // Double value (64 bit, big endian, IEEE 754-2008)
                return DataHandler::putTAGDouble($fPtr, $node->getValue());
            case Tag::TAG_BYTE_ARRAY: // Byte array
                return DataHandler::putTAGByteArray($fPtr, $node->getValue());
            case Tag::TAG_STRING: // String
                return DataHandler::putTAGString($fPtr, $node->getValue());
            case Tag::TAG_INT_ARRAY: // Byte array
                return DataHandler::putTAGIntArray($fPtr, $node->getValue());
            case Tag::TAG_LIST: // List
                if ($this->verbose) {
                    trigger_error(
                        'Writing list of '.count($node->getChildren())." tags of type {$node->getPayloadType()}.",
                        E_USER_NOTICE
                    );
                }
                if (!(DataHandler::putTAGByte($fPtr, $node->getPayloadType())
                    && DataHandler::putTAGInt($fPtr, count($node->getChildren()))
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
            case Tag::TAG_END: // End tag
                return is_int(fwrite($fPtr, "\0"));
        }
    }
}
