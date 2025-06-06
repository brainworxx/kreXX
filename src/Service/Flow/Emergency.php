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

namespace Brainworxx\Krexx\Service\Flow;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Emergency break handler for large output (runtime and memory usage).
 */
class Emergency implements ConfigConstInterface
{
    /**
     * Counts how often kreXX was called.
     *
     * @var int
     */
    protected int $krexxCount = 0;

    /**
     * Unix timestamp, used to determine if we need to do an emergency break.
     *
     * @var int
     */
    protected int $timer = 0;

    /**
     * Stores if the emergency break is enabled.
     *
     * @var bool
     */
    protected bool $disabled = false;

    /**
     * Has this one failed before?
     *
     * @var bool
     */
    protected bool $allIsOk = true;

    /**
     * Maximum runtime from the config, cached.
     *
     * @var int
     */
    protected int $maxRuntime = 0;

    /**
     * The server memory limit, coming from the php.ini.
     *
     * @var int
     */
    protected int $serverMemoryLimit = 0;

    /**
     * Cached configuration of the minimum leftover memory (MB).
     *
     * @var int
     */
    protected int $minMemoryLeft = 0;

    /**
     * The level inside the object/array hierarchy we are in.
     *
     * @var int
     */
    protected int $nestingLevel = 0;

    /**
     * Caching the setting of the maximum nesting level.
     *
     * @var int
     */
    protected int $maxNestingLevel;

    /**
     * The pool, where we store the classes we need.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * Configured maximum amount of calls.
     *
     * @var int
     */
    protected int $maxCall = 0;

    /**
     * Get some system and config data during construct.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        // Cache the server memory limit.
        if (preg_match('/^(\d+)(.)$/', strtoupper(ini_get('memory_limit')), $matches)) {
            if ($matches[2] === 'M') {
                // Megabyte.
                $this->serverMemoryLimit = $matches[1] * 1024 * 1024;
            } elseif ($matches[2] === 'K') {
                // Kilobyte.
                $this->serverMemoryLimit = $matches[1] * 1024;
            }
        }

        // Cache some settings.
        $this->maxRuntime = (int) $pool->config->getSetting(static::SETTING_MAX_RUNTIME);
        $this->minMemoryLeft = ((int) $pool->config->getSetting(static::SETTING_MEMORY_LEFT))  * 1024 * 1024;
        $this->maxCall = (int) $this->pool->config->getSetting(static::SETTING_MAX_CALL);
        $this->maxNestingLevel = (int) $this->pool->config->getSetting(static::SETTING_NESTING_LEVEL);

        $pool->emergencyHandler = $this;
    }

    /**
     * Setter for the enabling of the break.
     *
     * @param bool $bool
     *  Whether it is enabled, or not.
     */
    public function setDisable(bool $bool): void
    {
        $this->disabled = $bool;
    }

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   FALSE = all is OK.
     *   TRUE = we have a problem.
     */
    public function checkEmergencyBreak(): bool
    {
        if ($this->disabled) {
            // Tell them, everything is OK!
            return false;
        }

        if (!$this->allIsOk) {
            // This has failed before!
            // No need to check again!
            return true;
        }

        return $this->checkMemory() || $this->checkRuntime();
    }

    /**
     * Checks if there is enough time left for the analysis.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   FALSE = all is OK.
     *   TRUE = we have a problem.
     */
    protected function checkRuntime(): bool
    {
        // Check Runtime.
        if ($this->timer < time()) {
            // This is taking longer than expected.
            $this->pool->messages->addMessage('emergencyTimer');
            Krexx::editSettings();
            Krexx::disable();
            $this->allIsOk = false;
            return true;
        }

        return false;
    }

    /**
     * Check if we have enough memory left.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   FALSE = all is OK.
     *   TRUE = we have a problem.
     */
    protected function checkMemory(): bool
    {
        // We will only check, if we were able to determine a memory limit
        // in the first place.
        if ($this->serverMemoryLimit > 2) {
            // Is more left than is configured?
            if (($this->serverMemoryLimit - memory_get_usage()) < $this->minMemoryLeft) {
                $this->pool->messages->addMessage('emergencyMemory');
                // Show settings to give the dev to repair the situation.
                Krexx::editSettings();
                Krexx::disable();
                $this->allIsOk = false;
                return true;
            }
        }

        return false;
    }

    /**
     * Going up one level in the object/array hierarchy.
     */
    public function upOneNestingLevel(): void
    {
        ++$this->nestingLevel;
    }

    /**
     * Going down one level in the object/array hierarchy.
     */
    public function downOneNestingLevel(): void
    {
        --$this->nestingLevel;
    }

    /**
     * Checks our current nesting level
     *
     * @return bool
     *   TRUE if we are too deep.
     */
    public function checkNesting(): bool
    {
        return ($this->nestingLevel > $this->maxNestingLevel);
    }

    /**
     * Getter for the nesting level.
     *
     * @return int
     */
    public function getNestingLevel(): int
    {
        return $this->nestingLevel;
    }

    /**
     * Initialize the timer.
     *
     * When a certain time has passed, kreXX will use an emergency break to
     * prevent too large output (or no output at all (WSOD)).
     * The first kreXX request triggers the timer, we then measure the rest
     * of the time.
     * When coming from cli, we will reset the timer, because cli has normally
     * a much greater execution time.
     */
    public function initTimer(): void
    {
        if (empty($this->timer) || php_sapi_name() === 'cli') {
            $this->timer = time() + $this->maxRuntime;
        }
    }

    /**
     * Finds out, if kreXX was called too often, to prevent large output.
     *
     * @return bool
     *   Whether kreXX was called too often or not.
     */
    public function checkMaxCall(): bool
    {
        if ($this->krexxCount >= $this->maxCall) {
            // Called too often, we might get into trouble here!
            return true;
        }

        // Give feedback if this is our last call.
        if ($this->krexxCount === ($this->maxCall - 1)) {
            $this->pool->messages->addMessage('maxCallReached');
        }

        // Count goes up.
        ++$this->krexxCount;
        // Tell them that we are still good.
        return false;
    }

    /**
     * Getter for the krexxCount.
     *
     * @return int
     *   How often kreXX was called.
     */
    public function getKrexxCount(): int
    {
        return $this->krexxCount;
    }
}
