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
 * Convert image from MediaWiki.
 *
 * @author Andrei Nicholson
 * @since  2013-01-01
 */
class MediaWiki2DokuWiki_MediaWiki_Namespace_Image extends MediaWiki2DokuWiki_MediaWiki_Namespace_Base
{
    /**
     * Namespace ID in MediaWiki.
     */
    const NAME_SPACE = 6;

    /**
     * Inject image.
     *
     * @param array $record Info on page.
     */
    public function process(array $record)
    {
        // Hashed Upload Directory.
        $md5Filename = md5($record['page_title']);
        $dir1 = substr($md5Filename, 0, 1);
        $dir2 = substr($md5Filename, 0, 2);

        $srcFilePath = realpath("{$this->mediaWikiDir}/images/$dir1/$dir2/{$record['page_title']}");

        // From inc/pageutils.php
        $dstFilePath = realpath("{$this->dokuWikiDir}/data/media/wiki")
                     . '/' . cleanID($record['page_title']);

        if ($srcFilePath === false) {
            MediaWiki2DokuWiki_Environment::out(
                'Does not exist in MediaWiki installation. Skipping'
            );
            return;
        }

        if (!is_dir(dirname($dstFilePath))) {
            mkdir(dirname($dstFilePath));
        }

        if (file_exists($dstFilePath)) {
            MediaWiki2DokuWiki_Environment::out('File already exists. Skipping.');
            return;
        }

        if (!copy($srcFilePath, $dstFilePath)) {
            MediaWiki2DokuWiki_Environment::out('Error while copying. Skipping.');
            return;
        }
    }
}

