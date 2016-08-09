<?php
/**
 * @file
 *   Sourcecode GUI for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
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


use Brainworxx\Krexx\config\Config;
use Brainworxx\Krexx\Framework\ShutdownHandler;
use Brainworxx\Krexx\Framework\Chunks;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\Controller\OutputActions;

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
    if ($handle == '') {
        \Krexx::open($data);
    } else {
        \Krexx::$handle($data);
    }
}

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
     * Includes all needed files and sets some internal values.
     */
    public static function bootstrapKrexx()
    {

        $krexxdir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        include_once $krexxdir . 'src/view/Help.php';
        include_once $krexxdir . 'src/view/Render.php';
        include_once $krexxdir . 'src/view/Messages.php';
        include_once $krexxdir . 'src/analysis/CodegenHandler.php';
        include_once $krexxdir . 'src/config/Fallback.php';
        include_once $krexxdir . 'src/config/Config.php';
        include_once $krexxdir . 'src/framework/Toolbox.php';
        include_once $krexxdir . 'src/framework/Chunks.php';
        include_once $krexxdir . 'src/framework/ShutdownHandler.php';
        include_once $krexxdir . 'src/framework/Storage.php';
        include_once $krexxdir . 'src/analysis/Flection.php';
        include_once $krexxdir . 'src/analysis/RecursionHandler.php';
        include_once $krexxdir . 'src/analysis/Routing.php';
        include_once $krexxdir . 'src/model/Simple.php';
        include_once $krexxdir . 'src/model/callback/AbstractCallback.php';
        include_once $krexxdir . 'src/model/callback/analyse/BacktraceStep.php';
        include_once $krexxdir . 'src/model/callback/analyse/ConfigSection.php';
        include_once $krexxdir . 'src/model/callback/analyse/Debug.php';
        include_once $krexxdir . 'src/model/callback/analyse/Object.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughArray.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughConfig.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughConstants.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughMethodAnalysis.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughMethods.php';
        include_once $krexxdir . 'src/model/callback/iterate/ThroughProperties.php';
        include_once $krexxdir . 'src/errorhandler/Error.php';
        include_once $krexxdir . 'src/errorhandler/Fatal.php';
        include_once $krexxdir . 'src/controller/Internals.php';
        include_once $krexxdir . 'src/controller/OutputActions.php';
        include_once $krexxdir . 'src/controller/EmergencyHandler.php';

        Config::$krexxdir = $krexxdir;

        OutputActions::checkEnvironmentAction();
    }

    /**
     * Handles the developer handle.
     *
     * @param string $name
     *   The name of the static function which was called.
     * @param array $arguments
     *   The arguments of said function.
     */
    public static function __callStatic($name, array $arguments)
    {
        OutputActions::noFatalForKrexx();
        // Do we gave a handle?
        $handle = OutputActions::$storage->config->getDevHandler();
        if ($name == $handle) {
            // We do a standard-open.
            if (isset($arguments[0])) {
                self::open($arguments[0]);
            } else {
                self::open();
            }
        }
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Takes a "moment".
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerMoment($string)
    {
        OutputActions::noFatalForKrexx();
        // Disabled?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::timerAction($string);
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Takes a "moment" and outputs the timer.
     */
    public static function timerEnd()
    {
        OutputActions::noFatalForKrexx();
        // Disabled ?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::timerEndAction();
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Starts the analysis of a variable.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function open($data = null)
    {
        OutputActions::noFatalForKrexx();
        // Disabled?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::dumpAction($data);
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     */
    public static function backtrace()
    {
        OutputActions::noFatalForKrexx();
        // Disabled?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        // Render it.
        OutputActions::backtraceAction();
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Enable kreXX.
     */
    public static function enable()
    {
        OutputActions::noFatalForKrexx();
        OutputActions::$storage->config->setEnabled(true);
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Disable kreXX.
     */
    public static function disable()
    {
        OutputActions::noFatalForKrexx();
        OutputActions::$storage->config->setEnabled(false);
        // We will not re-enable it afterwards, because kreXX
        // is disabled and the handler would not show up anyway.
    }

    /**
     * Displays the edit settings part, no analysis.
     *
     * Ignores the 'disabled' settings in the cookie.
     */
    public static function editSettings()
    {
        OutputActions::noFatalForKrexx();
        // Disabled?
        // We are ignoring local settings here.
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::editSettingsAction();
        OutputActions::reFatalAfterKrexx();
    }

    /**
     * Registers a shutdown function.
     *
     * Our fatal errorhandler is located there.
     */
    public static function registerFatal()
    {
        // Disabled?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::registerFatalAction();
    }

    /**
     * Tells the registered shutdown function to do nothing.
     *
     * We can not unregister a once declared shutdown function,
     * so we need to tell our errorhandler to do nothing, in case
     * there is a fatal.
     */
    public static function unregisterFatal()
    {
        // Disabled?
        if (!OutputActions::$storage->config->getEnabled()) {
            return;
        }
        OutputActions::unregisterFatalAction();
    }
}
