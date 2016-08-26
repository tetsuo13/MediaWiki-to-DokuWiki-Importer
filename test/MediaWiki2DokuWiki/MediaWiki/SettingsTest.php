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
 * {@link MediaWiki2DokuWiki_MediaWiki_Settings} unit tests.
 *
 * Creates a temporary file which is populated with mock data as would be
 * found in LocalSettings.php. All temporary files are deleted after.
 *
 * @author Andrei Nicholson
 * @since  2013-08-13
 */
class MediaWiki2DokuWiki_MediaWiki_SettingsTest extends MediaWiki2DokuWiki_MediaWiki_TestCase
{
    private $testFile = '';

    public function setUp()
    {
        $this->testFile = tempnam(sys_get_temp_dir(), __FILE__);
    }

    private function writeSettingsFile($contents)
    {
        if ($this->testFile !== false) {
            $contents = "<?php
$contents
?>";
            file_put_contents($this->testFile, $contents);
        }
    }

    public function tearDown()
    {
        if ($this->testFile !== false) {
            unlink($this->testFile);
        }
    }

    public function testEasyDbConfig()
    {
        $this->writeSettingsFile('## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "my_dbname";
$wgDBuser           = "my_dbuser";
$wgDBpassword       = "abceasyas123";
$wgDBprefix         = "mwiki_";');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        $this->assertEquals('mysql', $settings->getSetting('wgDBtype'));
        $this->assertEquals('localhost', $settings->getSetting('wgDBserver'));
        $this->assertEquals('my_dbname', $settings->getSetting('wgDBname'));
        $this->assertEquals('my_dbuser', $settings->getSetting('wgDBuser'));
        $this->assertEquals('abceasyas123', $settings->getSetting('wgDBpassword'));
        $this->assertEquals('mwiki_', $settings->getSetting('wgDBprefix'));
    }

    public function testEmptyFile()
    {
        $this->writeSettingsFile('');

        try {
            $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);
            $this->fail('Empty LocalSettings.php should trigger error');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testCrazyDbConfig()
    {
        $this->writeSettingsFile('
$wgDBtype = "mysql"; #mysql
$wgDBserver = "localhost"; #localhost
$wgDBname = "my_wp"; #my_wp
$wgDBuser = "my_wp"; #my_wp
$wgDBpassword = "Q?u>U(]\c;e+~e0l|;g~df<}byG/hJ?\'iL.!7HO7s\"+"; #Q?u>U(]\c;e+~e0l|;g~df<}byG/hJ?\'iL.!7HO7s+
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        $this->assertEquals('mysql', $settings->getSetting('wgDBtype'));
        $this->assertEquals('localhost', $settings->getSetting('wgDBserver'));
        $this->assertEquals('my_wp', $settings->getSetting('wgDBname'));
        $this->assertEquals('my_wp', $settings->getSetting('wgDBuser'));
        $this->assertEquals('Q?u>U(]\c;e+~e0l|;g~df<}byG/hJ?\'iL.!7HO7s\"+', $settings->getSetting('wgDBpassword'));
        $this->assertEquals('foo_', $settings->getSetting('wgDBprefix'));
    }

    public function testMissingOptionalDbPrefix()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "my_wiki";
$wgDBuser           = "root";
$wgDBpassword       = "";');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        $this->assertEquals('mysql', $settings->getSetting('wgDBtype'));
        $this->assertEquals('localhost', $settings->getSetting('wgDBserver'));
        $this->assertEquals('my_wiki', $settings->getSetting('wgDBname'));
        $this->assertEquals('root', $settings->getSetting('wgDBuser'));
        $this->assertEquals('', $settings->getSetting('wgDBpassword'));
        $this->assertEquals('', $settings->getSetting('wgDBprefix'));
    }

    public function testMissingRequiredDbType()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBserver         = "localhost";
$wgDBname           = "my_wiki";
$wgDBuser           = "root";
$wgDBpassword       = "";
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        try {
            $this->assertEquals('', $settings->getSetting('wgDBtype'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMissingRequiredDbServer()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBtype           = "mysql";
$wgDBname           = "my_wiki";
$wgDBuser           = "root";
$wgDBpassword       = "";
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        try {
            $this->assertEquals('', $settings->getSetting('wgDBserver'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMissingRequiredDbName()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBuser           = "root";
$wgDBpassword       = "";
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        try {
            $this->assertEquals('', $settings->getSetting('wgDBname'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMissingRequiredDbUser()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "my_wiki";
$wgDBpassword       = "";
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        try {
            $this->assertEquals('', $settings->getSetting('wgDBuser'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testMissingRequiredDbPassword()
    {
        $this->writeSettingsFile('
## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "my_wiki";
$wgDBuser           = "root";
$wgDBprefix = "foo_"; #foo_');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        try {
            $this->assertEquals('', $settings->getSetting('wgDBpassword'));
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testEmptyDbPrefix()
    {
        $this->writeSettingsFile('
# MySQL specific settings
$wgDBprefix         = "";');

        $settings = new MediaWiki2DokuWiki_MediaWiki_Settings($this->testFile);

        $this->assertEquals('', $settings->getSetting('wgDBprefix'));
    }
}

