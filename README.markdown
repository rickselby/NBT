PHP NBT Decoder / Encoder
=========================

A PHP-based NBT format decoder and encoder, for the Minecraft NBT format.
A basic usage example is available in test.php. I suggest you run this script
before you use the library for any other project, because this script acts
as a sort of "requirements test" for your system, to ensure you have the
right extensions, and that there's nothing funky with your configuration.

**Requires the GMP Extension for PHP on 32-bit builds.**


Can be pulled in using composer, add the following to your composer.json
========================================================================
{
	"name": "vendor/your-project",
	"description": "Description of project",
	"authors": [
		{
			"name": "Your Name",
			"email": "your@yourdomain.com"
		}
	],
	"repositories": [
		{
			"type": "package",
			"package": {
				"name": "Caffe1neAdd1ct/PHP-NBT-Decoder-Encoder",
				"version": "1.0.1",
				"source": {
					"url": "https://github.com/Caffe1neAdd1ct/PHP-NBT-Decoder-Encoder.git",
					"type": "git",
					"reference": "1.0.1"
				}
			}
		}
	],
	"require": {
		"Caffe1neAdd1ct/PHP-NBT-Decoder-Encoder": "1.0.1"
	}
}