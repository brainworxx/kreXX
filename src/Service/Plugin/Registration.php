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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Plugin;

/**
 * Allow plugins to alter the configuration
 *
 * @api
 *
 * @package Brainworxx\Krexx\Service
 */
class Registration
{



    /**
     * The registered plugin configuration files as class names.
     *
     * @var array
     */
    protected static $plugins = array();

    /**
     * The configured chunk folder from the plugin.
     *
     * @var string
     */
    protected static $chunkFolder;

    /**
     * The configures log folder from the plugin.
     *
     * @var string
     */
    protected static $logFolder;

    /**
     * The configured configuration file from the plugin.
     *
     * @var string
     */
    protected static $configFile;

    /**
     * Blacklist of forbidden debug methods.
     *
     * @var array
     */
    protected static $blacklistDebugMethods = array();

    /**
     * Setter for the path to the configuration file.
     *
     * @param $path
     *   The absolute path to the configuration file.
     */
    public static function setConfigFile($path)
    {
        static::$configFile = $path;
    }

    /**
     * Setter for the path to the chaunks folder.
     *
     * @param $path
     *   The absolute path to the chunks folder.
     */
    public static function setChunksFolder($path)
    {
        static::$chunkFolder = $path;
    }

    /**
     * Setter for the log folder.
     *
     * @param $path
     *   The absolute path to the log folder.
     */
    public static function setLogFolder($path)
    {
        static::$logFolder = $path;
    }

    /**
     * Add a class / method to the debug method blacklist
     *
     * @api
     *
     * @param string $class
     * @param string $methodName
     */
    public static function addMethodToDebugBlacklist($class, $methodName)
    {
        if (isset(static::$blacklistDebugMethods[$class]) === false) {
            static::$blacklistDebugMethods[$class] = array();
        }
        if (in_array($methodName, static::$blacklistDebugMethods[$class]) === false) {
            static::$blacklistDebugMethods[$class][] = $methodName;
        }
    }


}
