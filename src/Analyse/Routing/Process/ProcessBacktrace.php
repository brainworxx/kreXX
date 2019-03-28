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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Processing of a backtrace. No abstract for you, because we are dealing with
 * an array here.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessBacktrace implements ConstInterface
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
         $this->pool = $pool;
    }

    /**
     * Do a backtrace analysis.
     *
     * @param array $backtrace
     *   The backtrace, which may (or may not) come from other sources.
     *   If omitted, a new debug_backtrace() will be retrieved.
     *
     * @return string
     *   The rendered backtrace.
     */
    public function process(&$backtrace = array())
    {
        if (empty($backtrace) === true) {
            $backtrace = $this->getBacktrace();
        }

        $output = '';
        $maxStep = (int) $this->pool->config->getSetting(Fallback::SETTING_MAX_STEP_NUMBER);
        $stepCount = count($backtrace);

        // Remove steps according to the configuration.
        if ($maxStep < $stepCount) {
            $this->pool->messages->addMessage('omittedBacktrace', array(($maxStep + 1), $stepCount));
        } else {
            // We will not analyse more steps than we actually have.
            $maxStep = $stepCount;
        }

        for ($step = 1; $step <= $maxStep; ++$step) {
            $output .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setName($step)
                    ->setType(static::TYPE_STACK_FRAME)
                    ->addParameter(static::PARAM_DATA, $backtrace[$step - 1])
                    ->injectCallback(
                        $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep')
                    )
            );
        }

        return $output;
    }

    /**
     * Get the backtrace, and remove all steps that were caused by kreXX.
     *
     * @return array
     *   The scrubbed backtrace.
     */
    protected function getBacktrace()
    {
        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();

        // We remove all steps that came from inside the kreXX lib.
        foreach ($backtrace as $key => $step) {
            if (isset($step[static::TRACE_FILE]) && strpos($step[static::TRACE_FILE], KREXX_DIR) !== false) {
                unset($backtrace[$key]);
            }
        }

        // Reset the array keys, because the 0 is now missing.
        return array_values($backtrace);
    }
}
