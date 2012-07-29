<?php
/**
 * {@link mediaWikiConverter} unit tests.
 *
 * Copyright (C) 2012 Andrei Nicholson
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
 * @author Andrei Nicholson
 * @since  2012-05-12
 */

require_once '..' . DIRECTORY_SEPARATOR .
             'src' . DIRECTORY_SEPARATOR .
             'convertSyntax.php';

/**
 * Tests {@link mediaWikiConverter}.
 */
class mediaWikiConverterTest extends PHPUnit_Framework_TestCase {
    private function convert($record) {
        $converter = new mediaWikiConverter($record);
        return $converter->convert();
    }

    public function testConvertCodeBlocks() {
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

        $this->assertEquals($expected, $this->convert($actual),
                            'Code within PRE tags should not be converted');
    }

    public function testConvertImageFiles() {
        $this->markTestIncomplete();
    }

    public function testConvertTalks() {
        $this->markTestIncomplete();
    }

    public function testConvertBoldItalic() {
        $this->assertEquals('//Italic//', $this->convert("''Italic''"));
        $this->assertEquals('**Bold**', $this->convert("'''Bold'''"));
    }

    public function testConvertLink() {
        $this->markTestIncomplete();
    }

    public function testConvertUrlText() {
        $this->markTestIncomplete();
    }

    public function testConvertList() {
        $expected = '  * This is a list
  * The second item
    * You may have different levels
  * Another item';

        $actual = '* This is a list
* The second item
** You may have different levels
* Another item';

        $this->assertEquals($expected, $this->convert($actual));

        $expected = "  - The same list but ordered
  - Another item
    - Just use indention for deeper levels
  - That's it";

        $actual = "# The same list but ordered
# Another item
## Just use indention for deeper levels
# That's it";

        $this->assertEquals($expected, $this->convert($actual));
    }

    /**
     */
    public function testConvertHeadings() {
        // DokuWiki to MediaWiki.
        $tests = array('====== Headline 1 ======' => '== Headline 1 ==',
                       '======Headline 1======'   => '==Headline 1==',
                       '===== Headline 2 ====='   => '=== Headline 2 ===',
                       '=====Headline 2====='     => '===Headline 2===',
                       '==== Headline 3 ===='     => '==== Headline 3 ====',
                       '====Headline 3===='       => '====Headline 3====',
                       '=== Headline 4 ==='       => '===== Headline 4 =====',
                       '===Headline 4==='         => '=====Headline 4=====',
                       '== Headline 5 =='         => '====== Headline 5 ======',
                       '==Headline 5=='           => '======Headline 5======');

        foreach ($tests as $dokuWiki => $mediaWiki) {
            $this->assertEquals($dokuWiki, $this->convert($mediaWiki));
        }
    }
}
