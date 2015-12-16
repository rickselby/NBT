This is a Named Binary Tag parser based upon the specification by Markus Persson.

From the spec: "NBT (Named Binary Tag) is a tag based binary format designed to carry large amounts of binary data with smaller amounts of additional data. An NBT file consists of a single GZIPped Named Tag of type TAG_Compound."

NBT data is also used in region files, which store the data for Minecraft worlds.

**Requires the GMP Extension for PHP on 32-bit builds.**

## Installing
### Composer

The library can be pulled in using composer; add the following to your composer.json:

```
{
    "require": {
        "rickselby/nbt": "~4.0"
    }
}
```

## Usage

Reading in from files, resources, or strings:
```php
$nbtService = new \Nbt\Service(new \Nbt\DataHandler());
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
$tree = \Nbt\Tag::tagCompound('aCompound', [
    \Nbt\Tag::tagByte('aByte', 0x0f),
    \Nbt\Tag::tagInt('aNumber', 12345),
]);

// Child tags for lists do not require names, as they are not named - and they must match the payload of the list
$tree = \Nbt\Tag::tagList('aList', \Nbt\Tag::TAG_STRING, [
    \Nbt\Tag::tagString('', 'firstString'),
    \Nbt\Tag::tagString('', 'secondString'),
]);
```

## History

The original PHP NBT package was written by [TheFrozenFire](//github.com/TheFrozenFire/PHP-NBT-Decoder-Encoder).

This repo has been forked by many, but most forks have one of two issues; they either don't handle TAG_INT_ARRAY or don't correctly handle writing to a file pointer.

The returned format - an array - isn't ideal for creating your own NBT data, and some kind of wrapper was required to assist in creation.

I tidied up the code a little, then added nicmart/tree to store the NBT data; after much work, it's nothing like the original, so I've pulled it into it's own (non-forked) repo.
