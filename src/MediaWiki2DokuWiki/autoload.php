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

spl_autoload_register('loadClass');

/**
 * Find classes related to us.
 *
 * @param string $className
 */
function loadClass($className)
{
    $baseNamespace = 'MediaWiki2DokuWiki_';

    if (strpos($className, $baseNamespace) !== 0) {
        return;
    }

    $fileName = str_replace($baseNamespace, '', $className);
    $fileName = str_replace('_', DIRECTORY_SEPARATOR, $fileName) . '.php';

    require dirname(__FILE__) . "/$fileName";
}

