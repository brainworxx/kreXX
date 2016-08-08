<?php
/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Framework;

use Brainworxx\Krexx\Controller\OutputActions;

/**
 * Toolbox methods.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Toolbox
{

    /**
     * Simply outputs a formatted var_dump.
     *
     * This is an internal debugging function, because it is
     * rather difficult to debug a debugger, when your tool of
     * choice is the debugger itself.
     *
     * @param mixed $data
     *   The data for the var_dump.
     */
    public static function formattedVarDump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo('</pre>');
    }

    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $file
     *   Path to the file you want to read.
     * @param int $highlight
     *   The line number you want to highlight
     * @param int $from
     *   The start line.
     * @param int $to
     *   The Endline.
     *
     * @return string
     *   The source code.
     */
    public static function readSourcecode($file, $highlight, $from, $to)
    {
        $result = '';
        if (is_readable($file)) {
            // Load content and add it to the backtrace.
            $contentArray = file($file);
            // Correct the value, in case we are exceeding the line numbers.
            if ($from < 0) {
                $from = 0;
            }
            if ($to > count($contentArray)) {
                $to = count($contentArray);
            }

            for ($currentLineNo = $from; $currentLineNo <= $to; $currentLineNo++) {
                if (isset($contentArray[$currentLineNo])) {
                    // Add it to the result.
                    $realLineNo = $currentLineNo + 1;

                    // Escape it.
                    $contentArray[$currentLineNo] = self::encodeString($contentArray[$currentLineNo], true);

                    if ($currentLineNo == $highlight) {
                        $result .= OutputActions::$render->renderBacktraceSourceLine(
                            'highlight',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    } else {
                        $result .= OutputActions::$render->renderBacktraceSourceLine(
                            'source',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    }
                } else {
                    // End of the file.
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Reads the content of a file.
     *
     * @param string $path
     *   The path to the file.
     *
     * @return string
     *   The content of the file, if readable.
     */
    public static function getFileContents($path)
    {
        $result = '';
        // Is it readable and does it have any content?
        if (is_readable($path)) {
            $size = filesize($path);
            if ($size > 0) {
                $file = fopen($path, "r");
                $result = fread($file, $size);
                fclose($file);
            }
        }

        return $result;
    }

    /**
     * Sanitizes a string, by completely encoding it.
     *
     * Should work with mixed encoding.
     *
     * @param string $data
     *   The data which needs to be sanitized.
     * @param bool $code
     *   Do we need to format the string as code?
     *
     * @return string
     *   The encoded string.
     */
    public static function encodeString($data, $code = false)
    {
        if (strlen($data) === 0) {
            return '';
        }

        // Try to encode it.
        set_error_handler(function () {
            /* do nothing. */
        });
        $result = @htmlentities($data, ENT_DISALLOWED);
        // We are also encoding @, because we need them for our chunks.
        $result = str_replace('@', '&#64;', $result);
        // We are also encoding the {, because we use it as markers for the skins.
        $result = str_replace('{', '&#123;', $result);
        restore_error_handler();

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (strlen($result) === 0) {
            // Something went wrong with the encoding, we need to
            // completely encode this one to be able to display it at all!
            $data = @mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

            if ($code) {
                // We are displaying sourcecode, so we need
                // to do some formatting.
                $sortingCallback = function ($n) {
                    if ($n == 9) {
                        // Replace TAB with two spaces, it's better readable that way.
                        $result = '&nbsp;&nbsp;';
                    } else {
                        $result = "&#$n;";
                    }
                    return $result;
                };
            } else {
                // No formatting.
                $sortingCallback = function ($n) {
                    return "&#$n;";
                };
            }

            // Here we have another SPOF. When the string is large enough
            // we will run out of memory!
            // @see https://sourceforge.net/p/krexx/bugs/21/
            // We will *NOT* return the unescaped string. So we must check if it
            // is small enough for the unpack().
            // 100 kb should be save enough.
            if (strlen($data) < 102400) {
                $result = implode("", array_map($sortingCallback, unpack("N*", $data)));
            } else {
                $result = OutputActions::$render->getHelp('stringTooLarge');
            }
        } else {
            if ($code) {
                // Replace all tabs with 2 spaces to make sourcecode better
                // readable.
                $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
            }
        }

        return $result;
    }
}
