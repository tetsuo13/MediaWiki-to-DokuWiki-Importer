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
 * Base controller.
 *
 * @author Andrei Nicholson
 * @since  2012-12-22
 */
class MediaWiki2DokuWiki_Environment
{
    /**
     * Configuration settings.
     */
    private $settings = array();

    /**
     * Constructor.
     *
     * @param array $settings Configuration settings.
     */
    public function __construct(array $settings)
    {
        $this->headerOutput();

        try {
            $settings = $this->checkSettings($settings);
        } catch (Exception $e) {
            $this->out($e->getMessage());
            return;
        }

        $dokuWiki = new MediaWiki2DokuWiki_DokuWiki_Bootstrap($settings['dokuwiki_dir']);

        try {
            $mediaWikiSettings = new MediaWiki2DokuWiki_MediaWiki_Settings($settings['mediawiki_localsettings_file']);
            $db = $mediaWikiSettings->dbConnect();
        } catch (Exception $e) {
            $this->out($e->getMessage());
            return;
        }

        $this->setTimeLimit();

        try {
            $converter = new MediaWiki2DokuWiki_MediaWiki_Converter(
                $settings['dokuwiki_dir'],
                dirname($settings['mediawiki_localsettings_file']),
                $dokuWiki->lang,
                $mediaWikiSettings->getSetting('wgDBprefix')
            );
            $converter->convert($db);
        } catch (Exception $e) {
            $this->out($e->getMessage());
            return;
        }
    }

    public function __destruct()
    {
        $this->footerOutput();
    }

    /**
     * Ensure that settings are used correctly.
     *
     * @param array $settings Configuration settings.
     *
     * @return Modified configuration settings.
     */
    private function checkSettings(array $settings)
    {
        $keys = array('dokuwiki_dir',
                      'mediawiki_localsettings_file');

        foreach ($keys as $key) {
            $settings[$key] = realpath($settings[$key]);

            if (!$this->settingKeyCheck($settings, $key)) {
                throw new RuntimeException("Invalid $key setting");
            }
        }

        return $settings;
    }

    /**
     * Check configuration setting for properness.
     *
     * @param array  $settings Configuration settings.
     * @param string $key      Configuration setting.
     *
     * @return boolean Whether or not setting is correct.
     */
    private function settingKeyCheck(array $settings, $key)
    {
        return $key !== FALSE && isset($settings[$key]) && file_exists($settings[$key]);
    }

    /**
     * Ensure that PHP will not stop execution early. If not running in CLI
     * mode then the web server will impose its own maximum execution time
     * limit, but that's usually higher than PHP's default.
     */
    private function setTimeLimit()
    {
        if (!ini_get('safe_mode')) {
            set_time_limit(0);
        } else {
            $this->out('Cannot change execution time limit in safe mode. ' .
                       'Using default of ' . ini_get('max_execution_time') .
                       ' seconds.');
        }
    }

    /**
     * Context-aware output.
     *
     * @param message Message to output.
     */
    public static function out(
        $message,
        $useParagraph = true,
        $omitIfCli = false
    ) {
        if (PHP_SAPI == 'cli' || isset($_SERVER['SHELL'])) {
            if ($omitIfCli) {
                return;
            }
            echo $message;
            return;
        }

        if ($useParagraph) {
            echo '<p>';
        }
        echo $message;
        if ($useParagraph) {
            echo '</p>';
        }
    }

    /**
     * HTML header output when not in CLI mode.
     */
    private function headerOutput()
    {
        $message = <<<EOT
<!DOCTYPE html>
<html>
<head>
<title>MediaWiki to DokuWiki Converter</title>
<style>body { font-family: arial; }</style>
</head>
<body>
EOT;
        $this->out($message, false, true);
    }

    /**
     * HTML footer when not in CLI mode.
     */
    private function footerOutput()
    {
        $message = <<<EOT
</body>
</html>
EOT;
        $this->out($message, false, true);
    }
}

