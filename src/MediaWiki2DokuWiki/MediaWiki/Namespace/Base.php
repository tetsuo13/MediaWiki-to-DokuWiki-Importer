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
 * Template for all MediaWiki namespaces.
 *
 * @author Andrei nicholson
 * @since  2013-01-01
 */
abstract class MediaWiki2DokuWiki_MediaWiki_Namespace_Base
{
    /**
     * Path to DokuWiki directory.
     */
    protected $dokuWikiDir = '';

    /**
     * Path to MediaWiki directory.
     */
    protected $mediaWikiDir = '';

    /**
     * Language array.
     */
    protected $lang = array();

    /**
     * Constructor.
     *
     * @param string $dokuWikiDir  Path to DokuWiki directory.
     * @param string $mediaWikiDir Path to MediaWiki directory.
     * @param array  $lang         Language array.
     */
    final public function __construct($dokuWikiDir, $mediaWikiDir, array $lang)
    {
        $this->dokuWikiDir = $dokuWikiDir;
        $this->mediaWikiDir = $mediaWikiDir;
        $this->lang = $lang;
    }

    /**
     * Namespace converter.
     *
     * @param array $row DB result for MediaWiki item detail.
     */
    abstract public function process(array $row);
}

