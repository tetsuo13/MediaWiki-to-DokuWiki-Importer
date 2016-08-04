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

class MediaWiki2DokuWiki_MediaWiki_SyntaxConverter_HeadingTest extends MediaWiki2DokuWiki_MediaWiki_TestCase
{
    private function assertTest($tests)
    {
        foreach ($tests as $dokuWiki => $mediaWiki) {
            $this->assertEquals($dokuWiki, $this->convert($mediaWiki));
        }
    }

    public function testHeading1()
    {
        // DokuWiki to MediaWiki.
        $tests = array(
            '====== Headline 1 ======' => '== Headline 1 ==',
            '======Headline 1======' => '==Headline 1=='
        );

        $this->assertTest($tests);
    }

    public function testHeading2()
    {
        $tests = array(
            '===== Headline 2 =====' => '=== Headline 2 ===',
            '=====Headline 2=====' => '===Headline 2==='
        );

        $this->assertTest($tests);
    }

    public function testHeading3()
    {
        $tests = array(
            '==== Headline 3 ====' => '==== Headline 3 ====',
            '====Headline 3====' => '====Headline 3===='
        );

        $this->assertTest($tests);
    }

    public function testHeading4()
    {
        $tests = array(
            '=== Headline 4 ===' => '===== Headline 4 =====',
            '===Headline 4===' => '=====Headline 4====='
        );

        $this->assertTest($tests);
    }

    public function testHeading5()
    {
        $tests = array(
            '== Headline 5 ==' => '====== Headline 5 ======',
            '==Headline 5==' => '======Headline 5======'
        );

        $this->assertTest($tests);
    }
}

