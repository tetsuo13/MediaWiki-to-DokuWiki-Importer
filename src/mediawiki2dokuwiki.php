#!/usr/bin/env php
<?php
/**
 * MediaWiki2DokuWiki. Imports a MediaWiki install into DokuWiki.
 *
 * Copyright (C) 2011-2012 Andrei Nicholson
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

define('IN_CLI_MODE', (PHP_SAPI == 'cli' || isset($_SERVER['SHELL'])));

if (!IN_CLI_MODE) {
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>MediaWiki to DokuWiki Converter</title>
<style>body { font-family: arial; }</style>
</head>
<body>
HTML;
}

if (!ini_get('safe_mode')) {
    // If not running in CLI mode then the web server will impose its own
    // maximum execution time limit, but that's usually higher than PHP's
    // default.
    set_time_limit(0);
} else {
    out('Cannot change execution time limit in safe mode. ' .
        'Using default of ' . ini_get('max_execution_time') . ' seconds.');
}

require_once 'convertSyntax.php';

// Path to root DokuWiki install. Required by include files.
define('DOKU_INC', dirname(__FILE__) . DIRECTORY_SEPARATOR);

require_once DOKU_INC . 'inc' . DIRECTORY_SEPARATOR . 'init.php';
require_once DOKU_INC . 'inc' . DIRECTORY_SEPARATOR . 'common.php';

try {
    $mwikiSettingsPath = getLocalSettingsPath();
} catch (RuntimeException $e) {
    out('Error: ' . $e->getMessage());
    usage();
    exit(1);
}

$mwikiSettings = file($mwikiSettingsPath,
                      FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$mwikiDb = dbConnectionSettings($mwikiSettings);

if (count($mwikiDb) != 8) {
    out('Error: Something went wrong with scraping LocalSettings.php for DB info');
    exit(1);
}

$db = dbConnect($mwikiDb);

convert($db, $mwikiDb, $lang);

if (!IN_CLI_MODE) {
    echo <<<HTML
</body>
</html>
HTML;
}

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
            out('Could not fetch MediaWiki: ' . $error[2]);
            exit(1);
        }

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            out('Processing ' . $row['page_title'] . '... ');

            switch ($row['page_namespace']) {
                case 0:
                    processPage($row, $lang);
                    break;

                case 6:
                    processImage($row, $lang);
                    break;

                default:
                    out('Unknown type. Skipping.');
            }

            out(PHP_EOL);
        }
    } catch (PDOException $e) {
        out('Error: Could not select all pages: ' . $e->getMessage());
        exit(1);
    }
}

/**
 * Inject new page into DokuWiki.
 *
 * @param array $record Info on page.
 * @param array $lang   DokuWiki language
 */
function processPage(array $record, array $lang) {
    $converter = new mediaWikiConverter($record['old_text']);

    saveWikiText($record['page_title'], con('', $converter->convert(), ''),
                 $lang['created']);
}

/**
 * Inject image.
 *
 * @param array $record Info on page.
 * @param array $lang   DokuWiki language
 */
function processImage(array $record, array $lang) {
    # Hashed Upload Directory
    $md5_filename = md5($record['page_title']);
    $dir1 = substr($md5_filename, 0, 1);
    $dir2 = substr($md5_filename, 0, 2);
    # File path
    $src_file_path = realpath(dirname($_SERVER['argv'][1]). DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $dir1 . DIRECTORY_SEPARATOR . $dir2 . DIRECTORY_SEPARATOR . $record['page_title']);
    $dst_file_path = dirname(__FILE__). DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'mediawiki' . DIRECTORY_SEPARATOR . strtolower($record['page_title']);

    if (!is_dir(dirname($dst_file_path))) {
        mkdir(dirname($dst_file_path));
    }

    if (file_exists($dst_file_path)) {
        out('File already exists. Skipping.');
        return;
    }

    if (!copy($src_file_path, $dst_file_path)) {
        out('Error while copying. Skipping.');
        return;
    }
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
        out('DB connection failed: ' . $e->getMessage());
        exit(1);
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

/**
 * Context-aware output.
 *
 * @param message Message to output.
 */
function out($message) {
    if (IN_CLI_MODE) {
        echo $message;
        return;
    }
    echo "<p>$message</p>";
}

/**
 * Finds path to LocalSettings.php from environment.
 *
 * @return string Absolute path to LocalSettings.php.
 */
function getLocalSettingsPath() {
    if (!IN_CLI_MODE) {
        if (!isset($_GET['settings_file'])) {
            throw new RuntimeException('Missing GET argument "settings_file".');
        }
        $settingsPath = $_GET['settings_file'];
    } else {
        if (!isset($_SERVER['argv'], $_SERVER['argc']) || $_SERVER['argc'] != 2) {
            throw new RuntimeException('Path to LocalSettings.php missing.');
        }
        $settingsPath = $_SERVER['argv'][1];
    }

    $realSettingsPath = realpath($settingsPath);

    if ($realSettingsPath === FALSE) {
        throw new RuntimeException("Invalid path to LocalSettings.php: $settingsPath.");
    }

    if (!file_exists($realSettingsPath) || is_dir($realSettingsPath)) {
        throw new RuntimeException("LocalSettings.php at '$settingsPath' cannot be found.");
    }

    // Can't rename MediaWiki settings file otherwise MediaWiki won't work, so
    // path must have the string.
    if (strpos($settingsPath, 'LocalSettings.php') === FALSE) {
        throw new RuntimeException("$settingsPath is not LocalSettings.php.");
    }

    return $settingsPath;
}

/**
 * Display usage info.
 */
function usage() {
    if (IN_CLI_MODE) {
        echo PHP_EOL . PHP_EOL .
             'usage: ' . basename(__FILE__) . ' <path to LocalSettings.php>' .
             PHP_EOL . PHP_EOL;
        return;
    }

    echo <<<HTML
<h3>Usage</h3>
<p>Use GET argument "settings_file" with the path to
    <code>LocalSettings.php</code>, either relative to this script or an
    absolute path.</p>
<p>Example:
    <blockquote><code>{$_SERVER['PHP_SELF']}?settings_file=../mediawiki/LocalSettings.php</code></blockquote>
</p>
HTML;
}
