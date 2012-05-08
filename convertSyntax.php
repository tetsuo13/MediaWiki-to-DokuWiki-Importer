<?php
/**
 * Convert MediaWiki syntax to DokuWiki syntax.
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
 * @since  2012-05-07
 */

/**
 * Convert syntaxes.
 *
 * Regular expressions originally by Johannes Buchner
 * <buchner.johannes [at] gmx.at>.
 *
 * Changes by Frederik Tilkin:
 *
 * <ul>
 * <li>uses sed instead of perl</li>
 * <li>resolved some bugs ('''''IMPORTANT!!!''''' becomes //**IMPORTANT!!!** //,
 *     // becomes <nowiki>//</nowiki> if it is not in a CODE block)</li>
 * <li>added functionality (multiple lines starting with a space become CODE
 *     blocks)</li>
 * </ul>
 */
class mediaWikiConverter {
    /** Original MediaWiki record. */
    private $record = '';

    /**
     * Constructor.
     *
     * @param string $record MediaWiki record.
     */
    public function __construct($record) {
        $this->record = $record;
    }

    /**
     * Convert page syntax from MediaWiki to DokuWiki.
     *
     * @return string DokuWiki page.
     * @author Johannes Buchner <buchner.johannes [at] gmx.at>
     * @author Frederik Tilkin
     */
    public function convert() {
        $record = $this->convertCodeBlocks($this->record);
        $record = $this->convertHeadings($record);
        $record = $this->convertList($record);
        $record = $this->convertUrlText($record);
        $record = $this->convertLink($record);
        $record = $this->convertBoldItalic($record);
        $record = $this->convertTalks($record);
        $record = $this->convertImagesFiles($record);

        return $record;
    }

    /**
     * Code blocks.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertCodeBlocks($record) {
        $patterns = array(
                          // Replace ALL **... strings (not at beginning of
                          // line)
                          '/([^^][^\*]*)(\*\*+)/' => '\1<nowiki>\2<\/nowiki>',

                          // Also replace ALL //... strings
                          '/([^\/]*)(\/\/+)/' => '\1<nowiki>\2<\/nowiki>',

                          // Change the ones that have been replaced in a link
                          // [] BACK to normal (do it twice in case
                          // [http://addres.com http://address.com] ) [quick
                          // and dirty]
                          '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',
                          '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',

                          '/<pre>(.*?)?<\/pre>/'      => '<code>\1</code>',
                          '/<\code>\n[ \t]*\n<code>/' => '');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert images and files.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertImagesFiles($record) {
        $patterns = array('/\[\[([media]|[medium]|[bild]|[image]|[datei]|[file]):([^\|\S]*)\|?\S*\]\]/i' => '{{mediawiki:\2}}');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert talks.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertTalks($record) {
        $patterns = array('/^[ ]*:/'  => '>',
                          '/>:/'      => '>>',
                          '/>>:/'     => '>>>',
                          '/>>>:/'    => '>>>>',
                          '/>>>>:/'   => '>>>>>',
                          '/>>>>>:/'  => '>>>>>>',
                          '/>>>>>>:/' => '>>>>>>>');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert bold and italic.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertBoldItalic($record) {
        $patterns = array("/'''''(.*)'''''/" => '//**\1**//',
                          "/'''/"            => '**',
                          "/''/"             => '//',

                          // Changes by Reiner Rottmann: - fixed erroneous
                          // interpretation of combined bold and italic text.
                          '@\*\*//@'         => '//**');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert [link] => [[link]].
     *
     * @param string $record
     *
     * @return string
     */
    private function convertLink($record) {
        $patterns = array('/([^[]|^)(\[[^]]*\])([^]]|$)/' => '\1[\2]\3');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert [url text] => [url|text].
     *
     * @param string $record
     *
     * @return string
     */
    private function convertUrlText($record) {
        $patterns = array('/([^[]|^)(\[[^] ]*) ([^]]*\])([^]]|$)/' => '\1\2|\3\4');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert lists.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertList($record) {
        $patterns = array('/^[*#][*#][*#][*#]\*/' => '          * ',
                          '/^[*#][*#][*#]\*/'     => '        * ',
                          '/^[*#][*#]\*/'         => '      * ',
                          '/^[*#]\*/'             => '    * ',
                          '/^\*/'                 => '  * ',
                          '/^[*#][*#][*#][*#]#/'  => '          - ',
                          '/^[*#][*#][*#]#/'      => '        - ',
                          '/^[*#][*#]#/'          => '      - ',
                          '/^[*#]#/'              => '    - ',
                          '/^#/'                  => '  - ');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert headings.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertHeadings($record) {
        $patterns = array('/^[ ]*=([^=])/'      => '<h1> \1',
                          '/([^=])=[ ]*$/'      => '\1 <\/h1>',
                          '/^[ ]*==([^=])/'     => '<h2> \1',
                          '/([^=])==[ ]*$/'     => '\1 <\/h2>',
                          '/^[ ]*===([^=])/'    => '<h3> \1',
                          '/([^=])===[ ]*$/'    => '\1 <\/h3>',
                          '/^[ ]*====([^=])/'   => '<h4> \1',
                          '/([^=])====[ ]*$/'   => '\1 <\/h4>',
                          '/^[ ]*=====([^=])/'  => '<h5> \1',
                          '/([^=])=====[ ]*$/'  => '\1 <\/h5>',
                          '/^[ ]*======([^=])/' => '<h6> \1',
                          '/([^=])======[ ]*$/' => '\1 <\/h6>',
                          '/<\/?h1>/'           => '======',
                          '/<\/?h2>/'           => '=====',
                          '/<\/?h3>/'           => '====',
                          '/<\/?h4>/'           => '===',
                          '/<\/?h5>/'           => '==',
                          '/<\/?h6>/'           => '=');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }
}
