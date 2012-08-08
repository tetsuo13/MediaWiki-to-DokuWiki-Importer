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

    /** Stored code blocks to prevent further conversions. */
    private $codeBlock = array();

    /** What string should never occur in user content? */
    private $placeholder = '';

    /**
     * Constructor.
     *
     * @param string $record MediaWiki record.
     */
    public function __construct($record) {
        $this->placeholder = '@@' . __CLASS__ . '_';
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

        if (count($this->codeBlock) > 0) {
            $record = $this->replaceStoredCodeBlocks($record);
        }

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
                          // Also replace ALL //... strings
                          '/([^\/]*)(\/\/+)/' => '\1<nowiki>\2<\/nowiki>',

                          // Change the ones that have been replaced in a link
                          // [] BACK to normal (do it twice in case
                          // [http://addres.com http://address.com] ) [quick
                          // and dirty]
                          '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',
                          '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',

                          '@<pre>(.*?)?</pre>@es'     => '$this->storeCodeBlock(\'\1\')',
                          '@</code>\n[ \t]*\n<code>@' => '');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Replace content in PRE tag with placeholder. This is done so no more
     * conversions are performed with the contents. The last thing this class
     * will do is replace those placeholders with their original content.
     *
     * @param string $code Contents of PRE tag.
     *
     * @return string CODE tag with placeholder in content.
     */
    private function storeCodeBlock($code) {
        $this->codeBlock[] = $code;

        $replace = $this->placeholder . (count($this->codeBlock) - 1) . '@@';

        return "<code>$replace</code>";
    }

    /**
     * Replace PRE tag placeholders back with their original content.
     *
     * @param string $record Converted record.
     *
     * @return string Record with placeholders removed.
     */
    private function replaceStoredCodeBlocks($record) {
        for ($i = 0, $numBlocks = count($this->codeBlock); $i < $numBlocks; $i++) {
            $record = str_replace($this->placeholder . $i . '@@',
                                  $this->codeBlock[$i],
                                  $record);
        }
        return $record;
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
        $patterns = array('/^\* /m'    => '  * ',
                          '/^\*{2} /m' => '    * ',
                          '/^\*{3} /m' => '      * ',
                          '/^\*{4} /m' => '        * ',
                          '/^# /m'     => '  - ',
                          '/^#{2} /m'  => '    - ',
                          '/^#{3} /m'  => '      - ',
                          '/^#{4} /m'  => '        - ');

        return preg_replace(array_keys($patterns), array_values($patterns),
                            $record);
    }

    /**
     * Convert headings. Syntax between MediaWiki and DokuWiki is completely
     * opposite: the largest heading in MediaWiki is two equal marks while in
     * DokuWiki it's six equal marks. This creates a problem since the first
     * replaced string of two marks will be caught by the last search string
     * also of two marks, resulting in eight total equal marks.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertHeadings($record) {
        $patterns = array('/^======(.+)======\s*$/m' => '==\1==',
                          '/^=====(.+)=====\s*$/m'   => '==\1==',
                          '/^====(.+)====\s*$/m'     => '===\1===',
                          '/^===(.+)===\s*$/m'       => '====\1====',
                          '/^==(.+)==\s*$/m'         => '=====\1=====');

        // Insert a unique string to the replacement so that it won't be
        // caught in a search later.
        // @todo A lambda function can be used when PHP 5.4 is required.
        array_walk($patterns,
                   create_function('&$v, $k',
                                   '$v = "' . $this->placeholder . '" . $v;'));

        $convertedRecord = preg_replace(array_keys($patterns),
                                        array_values($patterns), $record);

        // No headings were found.
        if ($convertedRecord == $record) {
            return $record;
        }

        // Strip out the unique strings.
        return str_replace($this->placeholder, '', $convertedRecord);
    }
}
