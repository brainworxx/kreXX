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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\View\Output;

use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Output string handling for kreXX, splitting strings into small tiny chunks.
 *
 * The main problem with our "templating engine" is, we are
 * adding partials into partials, over and over again. This
 * results in a very long string, 30 MB or larger. When using
 * str_replace() on it, we can have a memory peak of 90 MB or
 * more.
 * This class splits this string into small and good-to-handle
 * chunks. We also use this class stitch back together this
 * string for output.
 *
 * @see \Brainworxx\Krexx\Service\Factory\Pool->encodingService
 *   We use '@@@' to mark a chunk key. This function escapes the @
 *   so we have no collusion with data from strings.
 */
class Chunks implements ConfigConstInterface
{
    /**
     * Marker of an address string inside the chunks.
     *
     * @var string
     */
    protected const STRING_DELIMITER = '@@@';

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * Here we store the metadata from the call.
     *
     * We save this data in a separate file, so that a backend extension can offer
     * some additional data about the logfiles and their content.
     *
     * @var string[]
     */
    protected array $metadata = [];

    /**
     * Is the chunks' folder write protected?
     *
     * When we do, kreXX will store temporary files in the chunks' folder.
     * This saves a lot of memory!
     *
     * @var bool
     */
    protected bool $chunkAllowed = true;

    /**
     * Is the log folder write protected?
     *
     * @var bool
     */
    protected bool $loggingAllowed = true;

    /**
     * The logfolder.
     *
     * @var string
     */
    protected string $logDir;

    /**
     * The folder for the output chunks.
     *
     * @var string
     */
    protected string $chunkDir;

    /**
     * Microtime stamp for chunk operations.
     *
     * @var string
     */
    protected string $fileStamp;

    /**
     * Here we save the encoding we are currently using.
     *
     * @var string
     */
    protected string $officialEncoding = 'utf8';

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->chunkDir = $pool->config->getChunkDir();
        $this->logDir = $pool->config->getLogDir();
        $stamp = explode(' ', microtime());
        $this->fileStamp = $stamp[1] . str_replace('0.', '', $stamp[0]);

        $pool->chunks = $this;
    }

    /**
     * Splits a string into small chunks.
     *
     * The chunks are saved to disk and later on.
     *
     * @param string $string
     *   The data we want to split into chunks.
     *
     * @return string
     *   The key to the chunk, wrapped up in @@@@@@.
     */
    public function chunkMe(string $string): string
    {
        static $counter = 0;

        if ($this->chunkAllowed && strlen($string) > 10000) {
            // Get the key.
            $key = $this->fileStamp . '_' . ++$counter;
            // Detect the encoding in the chunk.
            $this->detectEncoding($string);
            // Write the key to the chunks' folder.
            $this->pool->fileService->putFileContents($this->chunkDir . $key . '.Krexx.tmp', $string);
            // Return the first part plus the key.
            return static::STRING_DELIMITER . $key . static::STRING_DELIMITER;
        }

        // Return the original, because it's too small.
        return $string;
    }

    /**
     * Gets the original data from the string.
     *
     * Reads the data from a file in the chunks' folder.
     * The output may contain other chunk keys.
     * Nothing more than a wrapper for file_get_contents()
     *
     * @param string $key
     *   The key of the chunk of which we want to get the data.
     *
     * @return string
     *   The original date
     */
    protected function dechunkMe(string $key): string
    {
        $filename = $this->chunkDir . $key . '.Krexx.tmp';
        // Read the file.
        $string = $this->pool->fileService->getFileContents($filename);
        // Delete it, we don't need it anymore.
        $this->pool->fileService->deleteFile($filename);
        return $string;
    }

    /**
     * Replaces all chunk keys from a string with the original data.
     *
     * Send the output to the browser.
     *
     * @param string $string
     *   The chunk string.
     */
    public function sendDechunkedToBrowser(string $string): void
    {
        // Check for HTML output.
        if ($this->pool->createClass(CheckOutput::class)->isOutputHtml()) {
            $chunkPos = strpos($string, static::STRING_DELIMITER);

            while ($chunkPos !== false) {
                // We have a chunk, we send the html part.
                echo substr($string, 0, $chunkPos);
                ob_flush();
                flush();
                $chunkPart = substr($string, $chunkPos);

                // We translate the first chunk.
                $result = explode(static::STRING_DELIMITER, $chunkPart, 3);
                $string = str_replace(
                    static::STRING_DELIMITER . $result[1] . static::STRING_DELIMITER,
                    $this->dechunkMe($result[1]),
                    $chunkPart
                );
                $chunkPos = strpos($string, static::STRING_DELIMITER);
            }

            // No more chunk keys, we send what is left.
            echo $string;
            ob_flush();
            flush();
        }
    }

    /**
     * Replaces all chunk keys from a string with the original data.
     *
     * Saves the output to a file.
     *
     * @param string $string
     *   The chunked version of the output.
     */
    public function saveDechunkedToFile(string $string): void
    {
        if (!$this->loggingAllowed) {
            // We have no write access. Do nothing.
            return;
        }


        // Determine the filename.
        $filename = $this->logDir . $this->fileStamp . '.Krexx.html';
        $chunkPos = strpos($string, static::STRING_DELIMITER);

        while ($chunkPos !== false) {
            // We have a chunk, we save the html part.
            $this->pool->fileService->putFileContents($filename, substr($string, 0, $chunkPos));

            $chunkPart = substr($string, $chunkPos);

            // We translate the first chunk.
            // Strangely, with a memory peak of 84 MB, explode is
            // 2 mb cheaper than preg_match().
            $result = explode(static::STRING_DELIMITER, $chunkPart, 3);
            $string = str_replace(
                static::STRING_DELIMITER . $result[1] . static::STRING_DELIMITER,
                $this->dechunkMe($result[1]),
                $chunkPart
            );
            $chunkPos = strpos($string, static::STRING_DELIMITER);
        }

        // No more chunks, we save what is left.
        $this->pool->fileService->putFileContents($filename, $string);
        // Save our metadata, so a potential backend module can display it.
        // We may or may not have already some output for this file.
        if (!empty($this->metadata)) {
            $filename .= '.json';
            // Remove the old metadata file. We still have all it's content
            // available in $this->metadata.
            $this->pool->fileService->deleteFile($filename);
            // Create a new metadata file with new info.
            $this->pool->fileService->putFileContents($filename, json_encode($this->metadata));
        }
    }

    /**
     * Setter for the $chunkAllowed.
     *
     * When the chunks' folder is not writable, we will not use chunks.
     * This will increase the memory usage significantly!
     *
     * @param bool $bool
     *   Are we using chunks?
     */
    public function setChunkAllowed(bool $bool): void
    {
        $this->chunkAllowed = $bool;
    }

    /**
     * Getter for the chunkAllowed value.
     *
     * @return bool
     *   Are we using chunks?
     */
    public function isChunkAllowed(): bool
    {
        return $this->chunkAllowed;
    }

    /**
     * Setter for the $useLogging. Here we determine, if the logfolder
     * is accessible.
     *
     * @param bool $bool
     *   Is the log folder accessible?
     */
    public function setLoggingAllowed(bool $bool): void
    {
        $this->loggingAllowed = $bool;
    }

    /**
     * Getter for the loggingAllowed.
     *
     * @return bool
     *   Is the log folder accessible?
     */
    public function isLoggingAllowed(): bool
    {
        return $this->loggingAllowed;
    }

    /**
     * We add some metadata that we will store in a separate file.
     *
     * @param array $caller
     *   The caller from the caller finder.
     */
    public function addMetadata(array $caller): void
    {
        if ($this->pool->config->getSetting(static::SETTING_DESTINATION) === static::VALUE_FILE) {
            $this->metadata[] = $caller;
        }
    }

    /**
     * When we are done, delete all leftover chunks, just in case.
     */
    public function __destruct()
    {
        if (empty($this->chunkDir)) {
            return;
        }

        // Get a list of all chunk files from the run.
        $chunkList = glob($this->chunkDir . $this->fileStamp . '_*');
        if (empty($chunkList)) {
            return;
        }

        // Delete them all!
        foreach ($chunkList as $file) {
            $this->pool->fileService->deleteFile($file);
        }
    }

    /**
     * Simple wrapper around mb_detect_encoding.
     *
     * We also try to track the encoding we need to add to the output, so
     * people can use Unicode function names.
     *
     * @see \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString
     *
     * @param string $string
     *   The string we are processing.
     */
    public function detectEncoding(string $string): void
    {
        static $doNothingEncoding = ['ASCII', 'UTF-8', false];
        $encoding = $this->pool->encodingService->mbDetectEncoding($string);

        // We need to decide, if we need to change the official encoding of
        // the HTML output with a meta tag. We ignore everything in the
        // doNothingEncoding array.
        if (!in_array($encoding, $doNothingEncoding, true)) {
            $this->officialEncoding = $encoding;
        }
    }

    /**
     * Getter for the official encoding.
     *
     * @return string
     */
    public function getOfficialEncoding(): string
    {
        return $this->officialEncoding;
    }
}
