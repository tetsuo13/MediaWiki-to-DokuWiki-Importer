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
 * Load things from DokuWiki. Encapsulates DokuWiki as much as possible.
 *
 * @author Andrei Nicholson
 * @since  2013-01-01
 */
class MediaWiki2DokuWiki_DokuWiki_Bootstrap
{
    /**
     * Language array.
     */
    public $lang = array();

    /**
     * Configuration array.
     */
    public $conf = array();

    /**
     * Constructor.
     *
     * @param string $dokuInc Path to the base directory of DokuWiki.
     * @global array Language array declared in inc/init.php.
     */
    public function __construct($dokuInc)
    {
        global $lang;

        // Path to root DokuWiki install. Required by include files.
        define('DOKU_INC', $dokuInc . DIRECTORY_SEPARATOR);

        $files = array('init.php', 'common.php');

        foreach ($files as $file) {
            require DOKU_INC . "inc/$file";
        }

        require DOKU_CONF . 'dokuwiki.php';
        $this->conf = $conf;

        $this->setupLanguage($lang);
    }

    /**
     * Assign the global $lang variable as attribute.
     *
     * 2012-10-13 "Adora Belle" only assigned a value to this variable in
     * the init_lang() function. It assumes a global variable.
     *
     * @param array $lang Global variable from init.php. May be null in
     *                    later versions of DokuWiki.
     */
    private function setupLanguage($lang)
    {
        if (function_exists('init_lang')) {
            init_lang($this->conf['lang']);
        }
        $this->lang = $lang;
    }
}

