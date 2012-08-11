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

$mwConvert = new mediawiki2Dokuwiki();


class mediawiki2Dokuwiki {

	private $in_cli_mode;
	private $doku_inc;
	private $mwiki_root;
	private $mwikiSettings;
	private $mwikiDb;
	private $db;
	
	
	public function __construct(){
		ini_set('display_errors', '1');
		error_reporting(E_ALL | E_STRICT);
		
		$this->in_cli_mode = (PHP_SAPI == 'cli' || isset($_SERVER['SHELL']));
		$this->doku_inc = dirname(__FILE__) . DIRECTORY_SEPARATOR;
		
		try {
			$this->mwiki_root = $this->getLocalSettingsPath();
		} catch (RuntimeException $e) {
			$this->out('Error: ' . $e->getMessage()."\n");
			$this->usage();
			exit(1);
		}
		
		require_once 'convertSyntax.php';
		// Path to root DokuWiki install. Required by include files.
		require_once $this->doku_inc . 'inc' . DIRECTORY_SEPARATOR . 'init.php';
		require_once $this->doku_inc . 'inc' . DIRECTORY_SEPARATOR . 'common.php';
		
		if (!$this->in_cli_mode) {
			$this->out("
				<!DOCTYPE html>
				<html>
					<head>
						<title>MediaWiki to DokuWiki Converter</title>
						<style>body { font-family: arial; }</style>
					</head>
					<body>"
			);
		}
		
		if (!ini_get('safe_mode')) {
			// If not running in CLI mode then the web server will impose its own
			// maximum execution time limit, but that's usually higher than PHP's
			// default.
			set_time_limit(0);
		} else {
			$this->out('Cannot change execution time limit in safe mode. Using default of ' . ini_get('max_execution_time') . ' seconds.');
		}
		
		$this->mwikiSettings = file($this->mwiki_root, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->mwikiDb = $this->dbConnectionSettings($this->mwikiSettings);
		
		if (count($this->mwikiDb) != 8) {
			$this->out('Error: Something went wrong with scraping LocalSettings.php for DB info');
			exit(1);
		}
		
		$this->db = $this->dbConnect($this->mwikiDb);
		
		$this->convert($this->db, $this->mwikiDb, $lang);
		
		if (!$this->in_cli_mode) {
			$this->out("</body></html>");
		}
	}
	
	/**
	 * Convert pages from MediaWiki.
	 *
	 * @param PDO   $db      DB handle.
	 * @param array $mwikiDb DB attributes.
	 * @param array $lang    DokuWiki language
	 */
	public function convert(PDO $db, array $mwikiDb, array $lang) {
		$prefix = $mwikiDb['wgDBprefix'];
	
		$sql = "SELECT p.page_title, p.page_namespace, t.old_text
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
				$this->out('Processing ' . $row['page_title'] . '... ');
	
				switch ($row['page_namespace']) {
					case 0:
						$this->processPage($row, $lang);
						break;
	
					case 6:
						$this->processImage($row, $lang);
						break;
	
					default:
						$this->out('Unknown type. Skipping.');
				}
	
				$this->out(PHP_EOL);
			}
		} 
		catch (PDOException $e) {
			$this->out('Error: Could not select all pages: ' . $e->getMessage());
			exit(1);
		}
	}
	
	/**
	 * Display usage info.
	 */
	private function usage() {
		if ($this->in_cli_mode) {
			$this->out("Usage " . basename(__FILE__) . "<path to LocalSettings.php>\n");
		}
		else {
			$this->out("
				<h3>Usage</h3>
				<p>Use GET argument \"settings_file\" with the path to
    			<code>LocalSettings.php</code>, either relative to this script or an absolute path.</p>
				<p>Example:
    				<blockquote><code>{$_SERVER['PHP_SELF']}?settings_file=../mediawiki/LocalSettings.php</code></blockquote>
				</p>
			");
		}
	}
	
	/**
	 * Context-aware output.
	 *
	 * @param message Message to output.
	 */
	private function out($message) {
		if ($this->in_cli_mode) {
			echo $message;
			return;
		}
		echo "<p>$message</p>";
	}
	
	/**
	 * Inject new page into DokuWiki.
	 *
	 * @param array $record Info on page.
	 * @param array $lang   DokuWiki language
	 */
	private function processPage(array $record, array $lang) {
		$converter = new mediaWikiConverter($record['old_text']);
	
		saveWikiText($record['page_title'], con('', "====== " . $record['page_title'] . " ======\n\n" . $converter->convert(), ''), $lang['created']);
	}
	
	/**
	 * Inject image.
	 *
	 * @param array $record Info on page.
	 * @param array $lang   DokuWiki language
	 */
	private function processImage(array $record, array $lang) {
		# Hashed Upload Directory
		$md5_filename = md5($record['page_title']);
		$dir1 = substr($md5_filename, 0, 1);
		$dir2 = substr($md5_filename, 0, 2);
	
		# File path
		$src_file_path = realpath(dirname($this->mwiki_root) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $dir1 .DIRECTORY_SEPARATOR .$dir2. DIRECTORY_SEPARATOR .$record['page_title']);
		$dst_file_path = dirname(__FILE__). DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'mediawiki' . DIRECTORY_SEPARATOR . strtolower($record['page_title']);
	
		if (!is_dir(dirname($dst_file_path))) {
			mkdir(dirname($dst_file_path));
		}
	
		if (file_exists($dst_file_path)) {
			$this->out('File already exists. Skipping.');
			return;
		}
	
		if (!copy($src_file_path, $dst_file_path)) {
			$this->out('Error while copying. Skipping.');
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
	private function dbConnect(array $arg_mwikiDb) {
		$dsn = $arg_mwikiDb['wgDBtype'] . ':dbname=' . $arg_mwikiDb['wgDBname'] . ';' . 'host=' . $arg_mwikiDb['wgDBserver'];
	
		try {
			$db = new PDO($dsn, $arg_mwikiDb['wgDBuser'], $arg_mwikiDb['wgDBpassword']);
		} catch (PDOException $e) {
			$this->out('DB connection failed: ' . $e->getMessage());
			exit(1);
		}
		return $db;
	}
	
	/**
	* Strip DB connection settings from LocalSettings.php.
	*
	* @param array $arg_mwikiSettings Content of LocalSettings.php with each line as
	*                             an element in the array.
	*
	* @return array DB attributes.
	*/
	private function dbConnectionSettings(array $arg_mwikiSettings) {
		foreach ($arg_mwikiSettings as $line) {
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
	* Finds path to LocalSettings.php from environment.
	*
	* @return string Absolute path to LocalSettings.php.
	*/
	private function getLocalSettingsPath() {
		if (!$this->in_cli_mode) {
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
}
