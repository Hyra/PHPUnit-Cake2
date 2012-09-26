## Update 2012-02-21

PHPUnit Installer now supports both 3.5 and 3.6 (currently 3.6.11)

## What?

This "installer" prepares all the dependencies needed to use PHPUnit with CakePHP 2.x through the use of vendor files.

## Why?

Because I'm a fan of self-contained systems. Sure, installing PHPUnit through PEAR systemwide is "easy". But when you're working on multiple workstations and deploy to different hosting setups its just nice to know you have everything within reach.

## Usage

To install PHPUnit to your CakePHP 2.x install you can use the Shell.

Run `Phpunit.Phpunit install` directly from your console.
This will download and extract all the necessary files, and put them in your specified `Vendor` folder.

Make sure you got `CakePlugin::loadAll()` - or specifically `CakePlugin::load('Phpunit')` in your bootstrap!
Otherwise the plugin will not be available.

You can now use PHPUnit through the CLI or your favourite browser.

It works with Linux and Windows. Please report any problems.

## Autoload

If you have it installed in your ROOT vendors and get some include warnings while baking put this at the top of the VENDORS/PHPUnit/Autoload.php file:

    set_include_path(get_include_path().PATH_SEPARATOR.dirname(dirname(__FILE__)));
    
This way the vendors folder itself is also an include path and those warnings will go away.

## Checking for updates

Running `Phpunit.Phpunit info` you can check for updates. This is mainly for maintaining the plugin and keeping it up to date.

## Credits

Mark Scherer (dereuromark) and Stef van den Ham (Hyra)