<?php
/**
 * MediaWiki2DokuWiki importer.
 *
 * MediaWiki2DokuWiki is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * MediaWiki2DokuWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
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
     * Configuration settings.
     */
    private $settings = array();

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

            $x = explode('=', $line, 2);

            if (!is_array($x) || count($x) != 2) {
                continue;
            }

            $val = trim($x[1]);

            // Strip leading dollar sign from key. Strip leading quote,
            // trailing quote and semicolon from value.
            $settingsParsed[substr(trim($x[0]), 1)] = substr($val, 1, -2);
        }

        return $settingsParsed;
    }

    /**
     * Connect to the DB and return handle.
     *
     * @return PDO DB handle.
     */
    public function dbConnect()
    {
        $dsn = $this->settings['wgDBtype'] . ':'
             . 'dbname=' . $this->settings['wgDBname'] . ';'
             . 'host=' . $this->settings['wgDBserver'];

        return new PDO(
            $dsn,
            $this->settings['wgDBuser'],
            $this->settings['wgDBpassword']
        );
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
        }
        throw new Exception("MediaWiki key $key does not exist");
    }
}

