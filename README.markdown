## What?

This "installer" prepares all the dependencies needed to use PHPUnit with CakePHP 2.0 through the use of vendor files.

## Why?

Because I'm a fan of self-contained systems. Sure, installing PHPUnit through PEAR systemwide is "easy". But when you're working on multiple workstations and deploy to different hosting setups its just nice to know you have everything within reach.

## Usage

To install PHPUnit to your CakePHP 2.0 install you can use the Shell.

Put the file `PhpunitShell.php` in your `app/Console/Command` folder.

Tun `cake phpunit install`
This will download and extract all the neccesary files, and put them in your `vendors` folder.

You can now use PHPUnit through the CLI or your favourite browser.