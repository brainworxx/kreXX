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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Service\Overwrites;

// Include some files and set some internal values.
\Krexx::bootstrapKrexx();

/**
 * Public functions, allowing access to the kreXX debug features.
 *
 * @package Krexx
 */
class Krexx
{

    /**
     * Our pool where we keep all relevant classes.
     *
     * @internal
     *
     * @var Pool
     */
    public static $pool;

    /**
     * Includes all needed files and sets some internal values.
     *
     * @internal
     */
    public static function bootstrapKrexx()
    {

        define('KREXX_DIR', __DIR__ . DIRECTORY_SEPARATOR);

        spl_autoload_register(
            function ($className) {
                static $krexxNamespace = 'Brainworxx\\Krexx';

                // If we are dealing with a none krexx class, do nothing.
                if (strpos($className, $krexxNamespace) === false) {
                    return;
                }

                require KREXX_DIR . 'src/' . str_replace(
                    array($krexxNamespace, '\\'),
                    array('', '/'),
                    $className
                ) . '.php';
            },
            false
        );

        if (!function_exists('krexx')) {
            /**
             * Alias function for object analysis.
             *
             * Register an alias function for object analysis,
             * so you will not have to type \Krexx::open($data);
             * all the time.
             *
             * @param mixed $data
             *   The variable we want to analyse.
             * @param string $handle
             *   The developer handle.
             */
            function krexx($data = null, $handle = '')
            {
                if (empty($handle)) {
                    \Krexx::open($data);
                } else {
                    \Krexx::$handle($data);
                }
            }
        }

        // Create a new pool where we store all our classes.
        // We also need to check if we have an overwrite for the pool.
        if (empty(Overwrites::$classes['Brainworxx\\Krexx\\Service\\Factory\\Pool'])) {
            static::$pool = new Pool();
        } else {
            $classname = Overwrites::$classes['Brainworxx\\Krexx\\Service\\Factory\\Pool'];
            static::$pool = new $classname();
        }

        // We might need to register our fatal error handler.
        if (static::$pool->config->getSetting('registerAutomatically')) {
            static::registerFatal();
        }
    }

    /**
     * Handles the developer handle.
     *
     * @api
     *
     * @param string $name
     *   The name of the static function which was called.
     * @param array $arguments
     *   The arguments of said function.
     */
    public static function __callStatic($name, array $arguments)
    {
        // Do we gave a handle?
        if ($name === static::$pool->config->getDevHandler()) {
            // We do a standard-open.
            if (isset($arguments[0])) {
                static::open($arguments[0]);
            } else {
                static::open();
            }
        }
    }

    /**
     * Takes a "moment".
     *
     * @api
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerMoment($string)
    {
        // Disabled?
        if (static::$pool->config->getSetting('disabled') || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->timerAction($string)
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Takes a "moment" and outputs the timer.
     *
     * @api
     */
    public static function timerEnd()
    {
        // Disabled ?
        if (static::$pool->config->getSetting('disabled') || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->timerEndAction()
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Starts the analysis of a variable.
     *
     * @api
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function open($data = null)
    {
        // Disabled?
        if (static::$pool->config->getSetting('disabled') || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->dumpAction($data)
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     *
     * @api
     *
     */
    public static function backtrace()
    {
        // Disabled?
        if (static::$pool->config->getSetting('disabled') || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\BacktraceController')
            ->noFatalForKrexx()
            ->backtraceAction()
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Disable kreXX.
     *
     * @api
     */
    public static function disable()
    {
        static::$pool->config->setDisabled(true);
        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx();
        // We will not re-enable it afterwards, because kreXX
        // is disabled and the handler would not show up anyway.
    }

    /**
     * Displays the edit settings part, no analysis.
     *
     * Ignores the 'disabled' settings in the cookie.
     *
     * @api
     */
    public static function editSettings()
    {
        // Disabled?
        // We are ignoring local settings here.
        if (static::$pool->config->getSetting('disabled')) {
            return;
        }

         static::$pool->createClass('Brainworxx\\Krexx\\Controller\\EditSettingsController')
            ->noFatalForKrexx()
            ->editSettingsAction()
            ->reFatalAfterKrexx();
    }

    /**
     * Registers a shutdown function.
     *
     * Our fatal errorhandler is located there.
     *
     * @api
     */
    public static function registerFatal()
    {
        // Disabled?
        if (static::$pool->config->getSetting('disabled')) {
            return;
        }

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
            ->registerFatalAction();
    }

    /**
     * Tells the registered shutdown function to do nothing.
     *
     * We can not unregister a once declared shutdown function,
     * so we need to tell our errorhandler to do nothing, in case
     * there is a fatal.
     *
     * @api
     */
    public static function unregisterFatal()
    {
        // Disabled?
        if (static::$pool->config->getSetting('disabled')) {
            return;
        }

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
            ->unregisterFatalAction();
    }
}
