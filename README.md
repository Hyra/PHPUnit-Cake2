## PHPUnit installer as CakePHP standalone plugin

PHPUnit installer now supports
3.7 (currently 3.7.19), 3.6 and 3.5

## What?

This "installer" prepares all the dependencies needed to use PHPUnit with CakePHP 2.x through the use of vendor files.

## Why?

Because I'm a fan of self-contained systems. Sure, installing PHPUnit through PEAR systemwide is supposed to be "easy", but when you're working on multiple workstations and deploy to different hosting setups its just nice to know you have everything within reach.

## Requirements

PHP5.3 and CakePHP2.x (>= 2.3)

## Install the Plugin

Install this plugin to your `app/Plugin` folder by cloning it to a folder called "Phpunit"

Make sure you got either `CakePlugin::loadAll()` - or specifically `CakePlugin::load('Phpunit')` in your bootstrap!
Otherwise the plugin will not be available.

## Usage

To install PHPUnit to your CakePHP 2.x install you can use the Shell.

Run `cake Phpunit.Phpunit install` directly from your console.
This will download and extract all the necessary files, and put them in your specified `Vendor` folder.

You can now use PHPUnit through the CLI or your favourite browser. Try it by running:

	cake testsuite core Basics

If all went well you will see the PHPUnit run the CakePHP basic tests.

It works with Mac OSX, Linux and Windows. Please report any problems.

## Autoload

If you have it installed in your ROOT vendors and get (fatal) errors while baking put this at the top of the VENDORS/PHPUnit/Autoload.php file:

    if (strpos(get_include_path(), dirname(dirname(__FILE__))) === false) {
        set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(__FILE__)));
    }

This way the vendors folder itself is also an include path and those errors will go away.
The PHPUnit files are checked/included prior to baking unit tests. So if you did not already set the path in your system, you need to do it this way.

## Checking for updates

Running `Phpunit.Phpunit info` you can check for updates. This is mainly for maintaining the plugin and keeping it up to date.

## Credits

Mark Scherer (dereuromark) and Stef van den Ham (Hyra)
