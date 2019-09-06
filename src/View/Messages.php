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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Messaging system.
 *
 * @package Brainworxx\Krexx\View
 */
class Messages
{

    /**
     * Here we store all messages, which gets send to the output.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The translatable keys for backend integration.
     *
     * @var array
     */
    protected $keys = [];

    /**
     * A simple array to hold the values.
     *
     * @var array
     */
    protected $helpArray = [];

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool and reads the language file.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->readHelpTexts();
        $pool->messages = $this;
    }

    /**
     * The message we want to add. It will be displayed in the output.
     *
     * @param string $key
     *   The message itself.
     * @param array $args
     *   The parameters for vsprintf().
     */
    public function addMessage($key, array $args = [])
    {
        // We will only display these messages once.
        if (isset($this->keys[$key]) === false) {
            // Add it to the keys, so the CMS can display it.
            $this->keys[$key] = ['key' => $key, 'params' => $args];
            $this->messages[] = $this->getHelp($key, $args);
        }
    }

    /**
     * Removes a key from the key array.
     *
     * @param string $key
     *   The key we want to remove
     */
    public function removeKey($key)
    {
        unset($this->keys[$key]);
    }

    /**
     * Getter for the language key array.
     *
     * @return array
     *   The language keys we added beforehand.
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Renders the output of the messages.
     *
     * @return string
     *   The rendered html output of the messages.
     */
    public function outputMessages()
    {
        // Simple Wrapper for OutputActions::$render->renderMessages
        if (php_sapi_name() === 'cli' &&
            empty($this->messages) === false &&
            defined('KREXX_TEST_IN_PROGRESS') === false
        ) {
            // Output the messages on the shell.
            $result = "\n\nkreXX messages\n";
            $result .= "==============\n";
            foreach ($this->messages as $message) {
                $result .= "$message\n";
            }

            echo $result . "\n\n";
        }

        // Return the rendered messages.
        return $this->pool->render->renderMessages($this->messages);
    }

    /**
     * Returns the help text when found, otherwise returns an empty string.
     *
     * @param string $key
     *   The help ID from the array above.
     * @param  array $args
     *   THe replacement arguments for vsprintf().
     *
     * @return string
     *   The help text.
     */
    public function getHelp($key, array $args = [])
    {
        // Check if we can get a value, at all.
        if (empty($this->helpArray[$key]) === true) {
            return '';
        }

        // Return the value
        return vsprintf($this->helpArray[$key], $args);
    }

    /**
     * Read a help text file, and add its contents to the already read content.
     *
     * @deprecated
     *   Since 3.1.1 dev. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @param string $file
     *   Absolute path to the file we want to read.
     */
    public function readHelpFile($file)
    {
        $this->helpArray = array_merge(
            $this->helpArray,
            (array)parse_ini_string($this->pool->fileService->getFileContents($file))
        );
    }

    /**
     * Reset the read help texts to factory settings.
     */
    public function readHelpTexts()
    {
        $this->helpArray = [];

        $fileList = array_merge(
            [KREXX_DIR . 'resources/language/Help.ini'],
            SettingsGetter::getAdditionalHelpFiles()
        );

        foreach ($fileList as $filename) {
            $this->helpArray = array_merge(
                $this->helpArray,
                (array)parse_ini_string($this->pool->fileService->getFileContents($filename))
            );
        }
    }
}
