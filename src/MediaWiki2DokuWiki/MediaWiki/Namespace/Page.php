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
 * Convert page from MediaWiki.
 *
 * @author Andrei Nicholson
 * @since  2013-01-01
 */
class MediaWiki2DokuWiki_MediaWiki_Namespace_Page extends MediaWiki2DokuWiki_MediaWiki_Namespace_Base
{
    /**
     * Namespace ID in MediaWiki.
     */
    const NAMESPACE = 0;

    /**
     * Inject new page into DokuWiki.
     *
     * @param array $record Info on page.
     */
    public function process(array $record)
    {
        $converter = new MediaWiki2DokuWiki_MediaWiki_SyntaxConverter($record['old_text']);

        saveWikiText($record['page_title'],
                     con('', "====== " . $record['page_title'] . " ======\n\n" . $converter->convert(), ''),
                     $this->lang['created']);
    }
}

