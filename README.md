PHP NBT Decoder / Encoder
=========================

A PHP-based NBT format decoder and encoder, for the Minecraft NBT format.
A basic usage example is available in test.php. I suggest you run this script
before you use the library for any other project, because this script acts
as a sort of "requirements test" for your system, to ensure you have the
right extensions, and that there's nothing funky with your configuration.

**Requires the GMP Extension for PHP on 32-bit builds.**

## Why another fork?

If you look at the [network from TheFrozenFire's orginal repo](//github.com/TheFrozenFire/PHP-NBT-Decoder-Encoder/network), it's beek forked many times already, and I've just made another.

There are two main 'versions'; the one by TheFrozenFire, continued by crafting-shards; and one forked by jegol, and continued by Caffe1neAdd1ct, sumpygump and now myself. The former is missing the definition for TAG_INT_ARRAY (which I believe is only used in maps); however, crafting-shards did fix the bug with writing to an existing file pointer. Neither worked out-of-the-box for my requirements, so I needed to fork one, and I picked the one with TAG_INT_ARRAY already included.

Ideally they'd all get pulled back into the main repo, so we just have one version; but it doesn't look like that's going to happen.

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
        "rickselby/PHP-NBT-Decoder-Encoder": "1.0.*"
    }
}
```

## Future work

Version 2.0 will work with strings, not files, which will give it more flexibility when being used with region files.
