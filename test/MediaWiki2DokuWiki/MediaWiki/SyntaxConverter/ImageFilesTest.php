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

class MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_ImageFilesTest extends MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_TestCase
{
    public function testConvertImageFiles()
    {
        $this->assertEquals(
            '{{wiki:example.jpg}}',
            $this->convert('[[File:example.jpg]]')
        );

        $this->assertEquals(
            '{{wiki:example.jpg?50}}',
            $this->convert('[[File:example.jpg|50px]]')
        );

        $this->assertEquals(
            '{{wiki:dokuwiki-128.png?200x50}}',
            $this->convert('[[File:dokuwiki-128.png|200x50px]]')
        );

        $this->assertEquals(
            '{{ wiki:dokuwiki-128.png}}',
            $this->convert('[[File:dokuwiki-128.png|left]]')
        );

        $this->assertEquals(
            '{{ wiki:dokuwiki-128.png }}',
            $this->convert('[[File:dokuwiki-128.png|center]]')
        );

        $this->assertEquals(
            '{{ wiki:dokuwiki-128.png |This is the caption}}',
            $this->convert('[[File:dokuwiki-128.png|center|This is the caption]]')
        );

        $this->assertEquals(
            '[[http://www.php.net|{{wiki:dokuwiki-128.png}}]]',
            $this->convert('[[File:dokuwiki-128.png|link=http://www.php.net]]')
        );
    }
}

