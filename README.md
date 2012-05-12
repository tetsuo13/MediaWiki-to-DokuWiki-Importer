MediaWiki2DokuWiki
==================

Import MediaWiki into DokuWiki. Will also translate from MediaWiki syntax to
DokuWiki as best as it can (not all modifiers from MediaWiki are available in
DokuWiki). It processes pages and images/files.

Tested against:

* MediaWiki 1.17.1 and DokuWiki 2012-01-25 "Angua".
* MediaWiki 1.16.1 and DokuWiki 2011-05-25a "Rincewind"


Requirements
------------

* Physical access to the MediaWiki and DokuWiki install on server.
* Read access to MediaWiki's LocalSettings.php script.
* PHP 5.1.0 or greater.
* PDO extension with MySQL/PostgreSQL binding -- whatever DB type MediaWiki is using.

Usage
-----

Copy both mediawiki2dokuwiki.php and convertSyntax.php into the base DokuWiki
directory. Execute mediawiki2dokuwiki.php from either the command line or from
a web browser.

If using the command line, execute mediawiki2dokuwiki.php along with the path
to LocalSettings.php, either as an absolute or relative path.

    $ ./mediawiki2dokuwiki.php <path to LocalSettings.php>

Otherwise if using a web browser, call mediawiki2dokuwiki.php with the
argument "settings_file" set to the path to LocalSettings.php.

    /mediawiki2dokuwiki.php?settings_file=<path to LocalSettings.php>
