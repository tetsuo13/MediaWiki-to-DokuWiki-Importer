#!/usr/bin/env php
<?php
/**
 * MediaWiki2DokuWiki. Imports a MediaWiki install into DokuWiki.
 *
 * Copyright (C) 2011 Andrei Nicholson
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Andrei Nicholson
 * @since  2011-11-18
 */

ini_set('display_errors', '1');
error_reporting(E_ALL | E_STRICT);

// Path to MediaWiki to DokuWiki syntax converter shell script.
define('MW2DW_SYNTAX_CONVERTER', 'mw2dw-conv_sed.sh');

// {@link MW2DW_SYNTAX_CONVERTER} will generate this file.
define('MW2DW_SYNTAX_OUTPUT', 'dokuwiki');

// Path to root DokuWiki install. Required by include files.
define('DOKU_INC', dirname(__FILE__) . '/');

require_once DOKU_INC . 'inc/init.php';
require_once DOKU_INC . 'inc/common.php';

if (!isset($_SERVER['argv'], $_SERVER['argc']) || $_SERVER['argc'] != 2) {
    exit('Path to LocalSettings.php missing');
}

if (!file_exists(MW2DW_SYNTAX_CONVERTER)) {
    exit('Missing syntax converter shell script ' . MW2DW_SYNTAX_CONVERTER);
}

$mwikiSettingsPath = realpath($_SERVER['argv'][1]);

if (!file_exists($mwikiSettingsPath)) {
    exit("Path to LocalSettings.php, $mwikiSettingsPath, is invalid");
}

$mwikiSettings = file($mwikiSettingsPath,
                      FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$mwikiDb = dbConnectionSettings($mwikiSettings);

if (count($mwikiDb) != 8) {
    exit('Something went wrong with scraping LocalSettings.php for DB info');
}

$db = dbConnect($mwikiDb);

convert($db, $mwikiDb, $lang);

/**
 * Convert pages from MediaWiki.
 *
 * @param PDO   $db      DB handle.
 * @param array $mwikiDb DB attributes.
 * @param array $lang    DokuWiki language
 */
function convert(PDO $db, array $mwikiDb, array $lang) {
    $prefix = $mwikiDb['wgDBprefix'];

    $sql = "SELECT      p.page_title, p.page_namespace, t.old_text
            FROM        {$prefix}page p
            INNER JOIN  {$prefix}revision r ON
                        p.page_latest = r.rev_id
            INNER JOIN  {$prefix}text t ON
                        r.rev_text_id = t.old_id
            ORDER BY    p.page_title";
    try {
        $statement = $db->prepare($sql);

        if (!$statement->execute()) {
            $error = $statement->errorInfo();
            exit('Could not fetch MediaWiki: ' . $error[2]);
        }

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            echo 'Processing ' . $row['page_title'] . '... ';

            switch ($row['page_namespace']) {
                case 0:
                    processPage($row, $lang);
                    break;

                case 6:
                    processImage($row, $lang);
                    break;

                default:
                    echo 'Unknown type. Skipping.';
            }

            echo PHP_EOL;
        }
    } catch (PDOException $e) {
        exit('Could not select all pages: ' . $e->getMessage());
    }
}

/**
 * Inject new page into DokuWiki.
 *
 * @param array $record Info on page.
 * @param array $lang   DokuWiki language
 */
function processPage(array $record, array $lang) {
    file_put_contents('mediawiki', $record['old_text']);
    exec('./' . MW2DW_SYNTAX_CONVERTER . ' mediawiki');

    if (!file_exists(MW2DW_SYNTAX_OUTPUT)) {
        echo 'Error converting syntax. Skipping.';
        return;
    }

    saveWikiText($record['page_title'],
                 con('', file_get_contents(MW2DW_SYNTAX_OUTPUT), ''),
                 $lang['created']);

    unlink(MW2DW_SYNTAX_OUTPUT);
}

/**
 * Inject image.
 *
 * @param array $record Info on page.
 * @param array $lang   DokuWiki language
 */
function processImage(array $record, array $lang) {
    echo 'Skipping.';
}

/**
 * Connect to the DB and return handle.
 *
 * @param array $mwikiDb DB attributes.
 *
 * @return PDO DB handle.
 */
function dbConnect(array $mwikiDb) {
    $dsn = $mwikiDb['wgDBtype'] . ':dbname=' . $mwikiDb['wgDBname'] . ';'
         . 'host=' . $mwikiDb['wgDBserver'];

    try {
        $db = new PDO($dsn, $mwikiDb['wgDBuser'], $mwikiDb['wgDBpassword']);
    } catch (PDOException $e) {
        exit('DB connection failed: ' . $e->getMessage());
    }
    return $db;
}

/**
 * Strip DB connection settings from LocalSettings.php.
 *
 * @param array $mwikiSettings Content of LocalSettings.php with each line as
 *                             an element in the array.
 *
 * @return array DB attributes.
 */
function dbConnectionSettings(array $mwikiSettings) {
    foreach ($mwikiSettings as $line) {
        if (substr($line, 0, 5) != '$wgDB') {
            continue;
        }

        $x = explode('=', $line, 2);

        if (!is_array($x) || count($x) != 2) {
            continue;
        }

        $val = trim($x[1]);

        // Strip leading dollar sign from key. Strip leading quote, trailing
        // quote and semicolon from value.
        $db[substr(trim($x[0]), 1)] = substr($val, 1, -2);
    }

    return $db;
}
