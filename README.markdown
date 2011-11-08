## What?

This "installer" prepares all the dependencies needed to use PHPUnit with CakePHP 2.0 through the use of vendor files.

## Why?

Because I'm a fan of self-contained systems. Sure, installing PHPUnit through PEAR systemwide is "easy". But when you're working on multiple workstations and deploy to different hosting setups its just nice to know you have everything within reach.

## Usage

Copy the contents of the `prepare_phpunit` file anywhere on your filesystem.
Make sure it has permissions and has is executable:

	chmod +X prepare_phpunit
	chmod 777 prepare_phpunit

execute the file:

	./prepare_phpunit

You end up with a nice file called `PHPUnit_for_Vendor.tar.gz`
Extract the file into your `vendors` folder.

Done!

You can now use PHPUnit through the CLI or your favourite browser.