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
 * Find things from MediaWiki which can be converted.
 *
 * @author Andrei Nicholson
 * @since  2013-01-01
 */
class MediaWiki2DokuWiki_MediaWiki_Converter
{
    /**
     * Path to the base directory of DokuWiki.
     */
    private $dokuWikiDir = '';

    /**
     * Path to the base directory of MediaWiki.
     */
    private $mediaWikiDir = '';

    /**
     * Language array.
     */
    private $lang = array();

    /**
     * Database table prefix used in MediaWiki queries.
     */
    private $dbPrefix = '';

    /**
     * Constructor.
     *
     * @param string $dokuWikiDir  Path to base directory of DokuWiki.
     * @param string $mediaWikiDir Path to base directory of MediaWiki.
     * @param array  $lang         Language array.
     * @param string $dbPrefix     Database table prefix.
     */
    public function __construct(
        $dokuWikiDir,
        $mediaWikiDir,
        array $lang,
        $dbPrefix
    ) {
        $this->dokuWikiDir = $dokuWikiDir;
        $this->mediaWikiDir = $mediaWikiDir;
        $this->lang = $lang;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Convert pages from MediaWiki.
     *
     * @param PDO $db DB handle.
     */
    public function convert(PDO $db)
    {
        $textTable = $db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql' ? "pagecontent" : "text"; 
        
        $sql = "SELECT      p.page_title, p.page_namespace, t.old_text
                FROM        {$this->dbPrefix}page p
                INNER JOIN  {$this->dbPrefix}revision r ON
                            p.page_latest = r.rev_id
                INNER JOIN  {$this->dbPrefix}{$textTable} t ON
                            r.rev_text_id = t.old_id
                ORDER BY    p.page_title";

        try {
            $statement = $db->prepare($sql);

            if (!$statement->execute()) {
                $error = $statement->errorInfo();
                throw new Exception('Could not fetch MediaWiki: ' . $error[2]);
            }

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                MediaWiki2DokuWiki_Environment::out(
                    'Processing ' . $row['page_title'] . '... ',
                    false
                );

                switch ($row['page_namespace']) {
                    case MediaWiki2DokuWiki_MediaWiki_Namespace_Page::NAME_SPACE:
                        $page = new MediaWiki2DokuWiki_MediaWiki_Namespace_Page(
                            $this->dokuWikiDir,
                            $this->mediaWikiDir,
                            $this->lang
                        );
                        $page->process($row);
                        break;

                    case MediaWiki2DokuWiki_MediaWiki_Namespace_Image::NAME_SPACE:
                        $image = new MediaWiki2DokuWiki_MediaWiki_Namespace_Image(
                            $this->dokuWikiDir,
                            $this->mediaWikiDir,
                            $this->lang
                        );
                        $image->process($row);
                        break;

                    default:
                        MediaWiki2DokuWiki_Environment::out(
                            'Unknown type. Skipping.',
                            false
                        );
                        break;
                }

                MediaWiki2DokuWiki_Environment::out(PHP_EOL);
            }
        } catch (PDOException $e) {
            throw new Exception('Error: Could not select all pages: ' . $e->getMessage());
        }
    }
}

