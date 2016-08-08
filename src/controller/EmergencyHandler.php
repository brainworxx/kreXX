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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\View\Messages;

/**
 * Emergency break handler for large output (runtime and memory usage).
 *
 * @package Brainworxx\Krexx\Controller
 */
class EmergencyHandler extends Internals
{
    /**
     * Stores if the emergency break is enabled.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Has this one failed before?
     *
     * @var bool
     */
    protected static $allIsOk = true;

    /**
     * Maximum runtime from the config, cached.
     *
     * @var int
     */
    protected $maxRuntime = 0;

    /**
     * The server memory limit, coming from the php.ini.
     *
     * @var int
     */
    protected $serverMemoryLimit = 0;

    /**
     * Cached configuration of the minimum leftover memory.
     *
     * @var int
     */
    protected $minMemoryLeft = 0;

    /**
     * The level inside the object/array hierarchy we are in.
     *
     * @var int
     */
    protected $nestingLevel = 0;

    /**
     * Get some system and config data during construct.
     */
    public function __construct()
    {
        // Cache the configured maximum runtime.
        $this->maxRuntime = (int)Config::getConfigValue('runtime', 'maxRuntime');
        // Cache the configured minimum left memory.
        $this->minMemoryLeft = (int)Config::getConfigValue('runtime', 'memoryLeft');

        // Cache the server memory limit.
        $limit = strtoupper(ini_get('memory_limit'));
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            if ($matches[2] == 'M') {
                // Megabyte.
                $this->serverMemoryLimit = $matches[1] * 1024 * 1024;
            } elseif ($matches[2] == 'K') {
                // Kilobyte.
                $this->serverMemoryLimit = $matches[1] * 1024;
            }
        }
    }

    /**
     * Setter for the enabling of the break.
     *
     * @param $bool
     */
    public function setEnable($bool)
    {
        $this->enabled = $bool;
    }

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   TRUE = all is OK.
     *   FALSE = we have a problem.
     */
    public function checkEmergencyBreak()
    {
        if (!$this->enabled) {
            // Tell them, everything is OK!
            return true;
        }

        if (self::$allIsOk === false) {
            // This has failed before!
            // No need to check again!
            return false;
        }

        // Check Runtime.
        if (self::$timer + $this->maxRuntime <= time()) {
            // This is taking longer than expected.
            Messages::addMessage('Emergency break due to extensive run time!');
            \Krexx::editSettings();
            \Krexx::disable();
            self::$allIsOk = false;
            return false;
        }

        // Still here ? Commence with the memory check.
        // We will only check, if we were able to determine a memory limit
        // in the first place.
        if ($this->serverMemoryLimit > 2) {
            $usage = memory_get_usage();
            $left = $this->serverMemoryLimit - $usage;
            // Is more left than is configured?
            if ($left < $this->minMemoryLeft * 1024 * 1024) {
                Messages::addMessage('Emergency break due to extensive memory usage!');
                // Show settings to give the dev to repair the situation.
                \Krexx::editSettings();
                \Krexx::disable();
                self::$allIsOk = false;
                return false;
            }
        }

        // Still here? Everything must be good  :-)
        return true;
    }

    /**
     * Going up one level in the object/array hierarchy.
     */
    public function upOneNestingLevel()
    {
        $this->nestingLevel++;
    }

    /**
     * Going down one level in the object/array hierarchy.
     */
    public function downOneNestingLevel()
    {
        $this->nestingLevel--;
    }

    /**
     * Checks our current nesting level
     *
     * @return bool
     *   TRUE if we are too deep.
     */
    public function checkNesting()
    {
        return ($this->nestingLevel > (int)Config::getConfigValue('runtime', 'level'));
    }

    /**
     * Getter for the nesting level.
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }
}
