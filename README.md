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
        "rickselby/PHP-NBT-Decoder-Encoder": "1.0.*"
    }
}
```

## Future work

Version 2.0 will work with strings, not files, which will give it more flexibility when being used with region files.
