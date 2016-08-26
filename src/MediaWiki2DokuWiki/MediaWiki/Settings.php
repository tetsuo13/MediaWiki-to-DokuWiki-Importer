<?php
/**
 * MediaWiki2DokuWiki importer.
 * Copyright (C) 2011-2013  Andrei Nicholson
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
 * @package   MediaWiki2DokuWiki
 * @author    Andrei Nicholson
 * @copyright Copyright (C) 2011-2013 Andrei Nicholson
 * @link      https://github.com/tetsuo13/MediaWiki-to-DokuWiki-Importer
 */

/**
 * Parse MediaWiki settings file for required parts.
 *
 * @author Andrei Nicholson
 * @since  2013-01-01
 */
class MediaWiki2DokuWiki_MediaWiki_Settings
{
    /**
     * @var array<string, string> Configuration settings.
     */
    private $settings = array();

    /**
     * @var array<string, string> Keys that will be imported and their default
     *                            values. A null value indicates that the key
     *                            is expected to be found in the MediaWiki
     *                            settings.
     */
    private $keys = array(
        'wgDBtype' => null,
        'wgDBserver' => null,
        'wgDBname' => null,
        'wgDBuser' => null,
        'wgDBpassword' => null,
        'wgDBprefix' => ''
    );

    /**
     * Constructor.
     *
     * @param string $settingsFile Path to MediaWiki settings file.
     */
    public function __construct($settingsFile)
    {
        $this->parseSettings($settingsFile);
    }

    /**
     * Retrieve parts from settings file which are required.
     *
     * @param string $settingsFile Path to MediaWiki settings file.
     */
    private function parseSettings($settingsFile)
    {
        $settings = $this->fileSource($settingsFile);
        $this->settings = $this->parseSettingsFileForVariables($settings);

        if (count($this->settings) == 0) {
            throw new Exception('Something went wrong with scraping LocalSettings.php for DB info');
        }
    }

    /**
     * Validate path to settings file and return contents as an array.
     *
     * @param string $settingsFile Path to MediaWiki settings file.
     *
     * @return array Contents of settings file.
     */
    private function fileSource($settingsFile)
    {
        if (!is_file($settingsFile)) {
            throw new Exception("Invalid path to LocalSettings.php, $settingsFile");
        }

        $localSettings = realpath($settingsFile);

        if ($localSettings === false) {
            throw new Exception('Could not read LocalSettings.php');
        }

        return file($localSettings, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Does a string start with one of a collection of needles. Needles are
     * any of the specific variables expected for {@link $settings}.
     *
     * @param string $line
     *
     * @return boolean
     */
    private function startsWithKey($line)
    {
        foreach (array_keys($this->keys) as $needle) {
            $variableName = '$' . $needle;

            if (!strncmp($line, $variableName, strlen($variableName))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find string value assigned to variable.
     *
     * @param array $tokens Result from token_get_all() on a line.
     *
     * @return string
     */
    private function getValue(array $tokens)
    {
        foreach ($tokens as $token) {
            if (!is_array($token) || $token[0] != T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            // Strip quotes.
            return substr($token[1], 1, -1);
        }

        throw new Exception('Value not found');
    }

    /**
     * Convert variables to keys and values.
     *
     * @param array $settings Content of LocalSettings.php with each line as
     *                        an element in the array.
     *
     * @return array Settings file.
     */
    private function parseSettingsFileForVariables(array $settings)
    {
        $settingsParsed = array();

        foreach ($settings as $line) {
            if ($line{0} != '$') {
                continue;
            }

            if (!$this->startsWithKey($line)) {
                continue;
            }

            $tokens = token_get_all("<?php $line ?>");

            if ($tokens[1][0] != T_VARIABLE) {
                continue;
            }

            // Get variable name without dollar sign.
            $key = substr($tokens[1][1], 1);
            $value = $this->getValue($tokens);
            $settingsParsed[$key] = $value;
        }

        return $settingsParsed;
    }

    /**
     * Generate DSN.
     *
     * @return string
     */
    private function generateDsn()
    {
        $dsn = array(
            $this->settings['wgDBtype'] . ':dbname=' . $this->settings['wgDBname']
        );

        $mysqlSocket = ini_get('mysql.default_socket');

        if (!empty($mysqlSocket)) {
            $dsn[] = 'unix_socket=' . $mysqlSocket;
        } else {
            $dsn[] = 'host=' . $this->settings['wgDBserver'];
        }

        return implode(';', $dsn);
    }

    /**
     * Connect to the DB and return handle.
     *
     * @return PDO DB handle.
     */
    public function dbConnect()
    {
        $db = new PDO(
            $this->generateDsn(),
            $this->settings['wgDBuser'],
            $this->settings['wgDBpassword']
        );

        // Force encoding just in case the character set of the server isn't
        // already UTF-8. Both MediaWiki and DokuWiki use UTF-8.
        $db->exec('SET NAMES utf8');

        return $db;
    }

    /**
     * Getter for setting.
     *
     * @param string $key PHP variable name from settings file.
     *
     * @return string Value of variable.
     */
    public function getSetting($key)
    {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        } else if (isset($this->keys[$key]) && $this->keys[$key] !== null) {
            return $this->keys[$key];
        }
        throw new Exception("MediaWiki key $key does not exist and is required");
    }
}

