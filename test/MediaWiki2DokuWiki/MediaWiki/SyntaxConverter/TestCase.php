<?php
/**
 * MediaWiki2DokuWiki importer.
 * Copyright (C) 2016 Andrei Nicholson
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
 * @copyright Copyright (C) 2016 Andrei Nicholson
 * @link      https://github.com/tetsuo13/MediaWiki-to-DokuWiki-Importer
 */

use PHPUnit\Framework\TestCase;

/**
 * Base unit test class helper.
 */
abstract class MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_TestCase extends TestCase
{
    /**
     * Convert MediaWiki block of text to DokuWiki syntax.
     *
     * @param string $record MediaWiki syntax.
     * @return string
     */
    protected function convert($record)
    {
        $converter = new MediaWiki2DokuWiki_MediaWiki_SyntaxConverter($record);
        return $converter->convert();
    }
}

