PHP NBT Decoder / Encoder
=========================

A PHP-based NBT format decoder and encoder, for the Minecraft NBT format.
A basic usage example is available in test.php. I suggest you run this script
before you use the library for any other project, because this script acts
as a sort of "requirements test" for your system, to ensure you have the
right extensions, and that there's nothing funky with your configuration.

**Requires the GMP Extension for PHP on 32-bit builds.**

## Installing
### Composer

The library can be pulled in using composer; add the following to your composer.json:

```
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/rickselby/PHP-NBT-Decoder-Encoder"
        }
    ],
    "require": {
        "rickselby/PHP-NBT-Decoder-Encoder": "~3.0"
    }
}
```

## Usage

Reading in from files, resources, or strings:
```php
$nbtService = new \Nbt\Service();
$tree = $nbtService->loadFile('filename.nbt');
$tree = $nbtService->readFilePointer($fPtr);
$tree = $nbtService->readString($nbtString);
```

Then writing to a file, a resource, or returning a string:
```php
$nbtService->writeFile('filename.nbt', $tree);
$nbtService->writeFilePointer($fPtr, $tree);
$nbtString = $nbtService->writeString($tree);
```

To look through a tree:
```php
echo $tree->getName();
$type = $tree->getType();

// Value isn't set for Lists and Compounds; those nodes have children instead
$value = $tree->getValue();

$sectionsNode = $tree->findChildByName('Sections');
```

To update a tree:
```php
$node->setName('Name');
$node->setValue(123456);
```

To create new nodes:
```php
// This is pretty useless on it's own really
$node = \Nbt\Tag::tagByte('aByte', 0x0f);

// You'll be building trees with Compounds and Lists mostly; both take an array of nodes as their values
$tree = \Nbt\Tag::tagCompound('SomeTag', [
    \Nbt\Tag::tagByte('aByte', 0x0f),
    \Nbt\Tag::tagInt('aNumber', 12345),
]);
```
