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

class MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_CodeBlockTest extends MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_TestCase
{
    public function testItalicsOutsideOfPre()
    {
        $actual = "''Italic text outside of PRE.''

<pre>
# Only warning, error, critical, alert, emergency messages if \$syslogseverity <= 4 then @@192.168.x.x:10514
# All messages
#. @@192.168.x.x:10514

#### RULES ####
</pre>";

        $expected = '//Italic text outside of PRE.//

<code>
# Only warning, error, critical, alert, emergency messages if $syslogseverity <= 4 then @@192.168.x.x:10514
# All messages
#. @@192.168.x.x:10514

#### RULES ####
</code>';

        $this->assertEquals(
            $expected,
            $this->convert($actual),
            'Code within PRE tags should not be converted'
        );
    }

    public function testLinksInsidePreShouldntConvert()
    {
        $actual = '<pre>
http://server/file/default/path/a/b/c
</pre>';

        $expected = '<code>
http://server/file/default/path/a/b/c
</code>';

        $this->assertEquals(
            $expected,
            $this->convert($actual),
            'Links within PRE tags should not be converted'
        );
    }
}

