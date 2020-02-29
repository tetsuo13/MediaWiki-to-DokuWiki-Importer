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
 *
 * @author Andrei Nicholson
 * @author Johannes Buchner
 * @author Frederik Tilkin
 * @since  2012-05-07
 */
class MediaWiki2DokuWiki_MediaWiki_SyntaxConverter
{
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
    public function __construct($record)
    {
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
    public function convert()
    {
        $record = $this->convertCodeBlocks($this->record);
        $record = $this->convertHeadings($record);
        $record = $this->convertList($record);
        $record = $this->convertUrlText($record);
        $record = $this->convertLink($record);
        $record = $this->convertDoubleSlash($record);
        $record = $this->convertBoldItalic($record);
        $record = $this->convertTalks($record);
        $record = $this->convertImagesFiles($record);

        if (count($this->codeBlock) > 0) {
            $record = $this->replaceStoredCodeBlocks($record);
        }

        return $record;
    }

    /**
     * Double forward slashes are not italic. There is no double slash syntax
     * rule in MediaWiki. This conversion must happen before the conversion of
     * italic markup.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertDoubleSlash($record)
    {
        $patterns = array(
            '/([^:])\/\//m' => '\1<nowiki>//</nowiki>',
        );
        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
    }

    /**
     * Code blocks.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertCodeBlocks($record)
    {
        $patterns = array(
            // Change the ones that have been replaced in a link [] BACK to
            // normal (do it twice in case
            // [http://addres.com http://address.com] ) [quick and dirty]
            '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',
            '/([\[][^\[]*)(<nowiki>)(\/\/+)(<\/nowiki>)([^\]]*)/' => '\1\3\5',

            '@</code>\n[ \t]*\n<code>@' => ''
        );

        $result = preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );

        return preg_replace_callback(
            '@<pre>(.*?)?</pre>@s',
            array($this, 'storeCodeBlock'),
            $result
        );
    }

    /**
     * Replace content in PRE tag with placeholder. This is done so no more
     * conversions are performed with the contents. The last thing this class
     * will do is replace those placeholders with their original content.
     *
     * @param string[] $matches Contents of PRE tag in second element.
     *
     * @return string CODE tag with placeholder in content.
     */
    private function storeCodeBlock($code)
    {
        $this->codeBlock[] = $code[1];

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
    private function replaceStoredCodeBlocks($record)
    {
        for ($i = 0, $numBlocks = count($this->codeBlock); $i < $numBlocks; $i++) {
            $record = str_replace(
                $this->placeholder . $i . '@@',
                $this->codeBlock[$i],
                $record
            );
        }
        return $record;
    }

    /**
     * Convert images and files.
     *
     * @param string $record Converted record.
     *
     * @return string
     */
    private function convertImagesFiles($record)
    {
        $numMatches = preg_match_all(
            '/\[\[(Image|File):(.*?)\]\]/',
            $record,
            $matches
        );

        if ($numMatches === 0 || $numMatches === false) {
            return $record;
        }

        for ($i = 0; $i < $numMatches; $i++) {
            $converted = $this->convertImage($matches[2][$i]);

            // Replace the full tag, [[File:example.jpg|options|caption]],
            // with the DokuWiki equivalent.
            $record = str_replace($matches[0][$i], $converted, $record);
        }

        return $record;
    }

    /**
     * Process a MediaWiki image tag.
     *
     * @param string $detail Filename and options, ie.
     *                       example.jpg|options|caption.
     *
     * @return string DokuWiki version of tag.
     */
    private function convertImage($detail)
    {
        $parts = explode('|', $detail);
        $numParts = count($parts);

        // Image link.
        if ($numParts == 2 && substr($parts[1], 0, 5) == 'link=') {
            return '[[' . substr($parts[1], 5) . '|{{wiki:' . $parts[0] . '}}]]';
        }

        $converted = '{{';
        $leftAlign = '';
        $rightAlign = '';
        $imageSize = '';
        $caption = '';

        if ($numParts > 1) {
            $imageFilename = array_shift($parts);

            foreach ($parts as $part) {
                if ($part == 'left') {
                    $leftAlign = ' ';
                    continue;
                } else if ($part == 'right') {
                    $rightAlign = ' ';
                    continue;
                } else if ($part == 'center') {
                    $leftAlign = $rightAlign = ' ';
                    continue;
                }

                if (substr($part, -2) == 'px') {
                    preg_match('/((\d+)x)?(\d+)px/', $part, $matches);

                    if (count($matches) > 0) {
                        if ($matches[1] == '') {
                            $imageSize = $matches[3];
                        } else {
                            $imageSize = $matches[2] . 'x' . $matches[3];
                        }
                    }

                    continue;
                }

                $caption = $part;
            }

            $converted .= $leftAlign . 'wiki:' . $imageFilename . $rightAlign;

            if ($imageSize != '') {
                $converted .= '?' . $imageSize;
            }

            if ($caption != '') {
                $converted .= '|' . $caption;
            }
        } else {
            $converted .= "wiki:$detail";
        }

        $converted .= '}}';

        return $converted;
    }

    /**
     * Convert talks.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertTalks($record)
    {
        $patterns = array(
            '/^[ ]*:/'  => '>',
            '/>:/'      => '>>',
            '/>>:/'     => '>>>',
            '/>>>:/'    => '>>>>',
            '/>>>>:/'   => '>>>>>',
            '/>>>>>:/'  => '>>>>>>',
            '/>>>>>>:/' => '>>>>>>>'
        );

        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
    }

    /**
     * Convert bold and italic.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertBoldItalic($record)
    {
        $patterns = array(
            "/'''''(.*)'''''/" => '//**\1**//',
            "/'''/"            => '**',
            "/''/"             => '//',

            // Changes by Reiner Rottmann: - fixed erroneous interpretation
            // of combined bold and italic text.
            '@\*\*//@'         => '//**'
        );

        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
    }

    /**
     * Convert [link] => [[link]].
     *
     * @param string $record
     *
     * @return string
     */
    private function convertLink($record)
    {
        $patterns = array('/([^[]|^)(\[[^]]*\])([^]]|$)/' => '\1[\2]\3');

        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
    }

    /**
     * Convert [url text] => [url|text].
     *
     * @param string $record
     *
     * @return string
     */
    private function convertUrlText($record)
    {
        $patterns = array(
            '/([^[]|^)(\[[^] ]*) ([^]]*\])([^]]|$)/' => '\1\2|\3\4'
        );

        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
    }

    /**
     * Convert lists.
     *
     * @param string $record
     *
     * @return string
     */
    private function convertList($record)
    {
        $patterns = array(
            '/^\* /m'    => '  * ',
            '/^\*{2} /m' => '    * ',
            '/^\*{3} /m' => '      * ',
            '/^\*{4} /m' => '        * ',
            '/^# /m'     => '  - ',
            '/^#{2} /m'  => '    - ',
            '/^#{3} /m'  => '      - ',
            '/^#{4} /m'  => '        - '
        );

        return preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );
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
    private function convertHeadings($record)
    {
        $patterns = array(
            '/^======(.+)======\s*$/m' => '==\1==',
            '/^=====(.+)=====\s*$/m'   => '===\1===',
            '/^====(.+)====\s*$/m'     => '====\1====',
            '/^===(.+)===\s*$/m'       => '=====\1=====',
            '/^==(.+)==\s*$/m'         => '======\1======'
        );

        // Insert a unique string to the replacement so that it won't be
        // caught in a search later.
        array_walk(
            $patterns,
            function (&$item, $key) {
                $item = $this->placeholder . $item;
            }
        );

        $convertedRecord = preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $record
        );

        // No headings were found.
        if ($convertedRecord == $record) {
            return $record;
        }

        // Strip out the unique strings.
        return str_replace($this->placeholder, '', $convertedRecord);
    }
}

