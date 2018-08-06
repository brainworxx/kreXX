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

use Brainworxx\Krexx\Service\Factory\Factory;

/**
 * Interfacing with the data supplied by the plugins.
 *
 * @internal
 *
 * @package Brainworxx\Krexx\Service\Plugin
 */
class SettingsGetter extends Registration
{

    const IS_ACTIVE = 'isActive';
    const CONFIG_CLASS = 'configClass';

    /**
     * Register a plugin.
     *
     * @param string $configClass
     *   The class name of the configuration class for this plugin.
     *   Must extend the \Brainworxx\Krexx\Service\AbstractPluginConfig
     */
    public static function register($configClass)
    {
        static::$plugins[$configClass] = array(
            static::CONFIG_CLASS => $configClass,
            static::IS_ACTIVE => false
        );
    }

    /**
     * We activate the plugin with the name, and execute its configuration method.
     *
     * @param string $name
     *   The name of the plugin.
     */
    public static function activatePlugin($name)
    {
        if (isset(static::$plugins[$name])) {
            static::$plugins[$name][static::IS_ACTIVE] = true;
            /** @var \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface $staticPlugin */
            $staticPlugin = static::$plugins[$name][static::CONFIG_CLASS];
            $staticPlugin::exec();
        }
        // No registration, no config, no plugin.
        // Do nothing.
    }

    /**
     * We deactivate the plugin and reset the configuration
     *
     * @internal
     *
     * @param string $name
     *   The name of the plugin.
     */
    public static function deactivatePlugin($name)
    {
        if (static::$plugins[$name][static::IS_ACTIVE] !== true) {
            // We will not purge everything for a already deactivated plugin.
            return;
        }

        // Purge the rewrites.
        Factory::$rewrite = array();
        // Purge the event registration.
        \Krexx::$pool->eventService->purge();
        // Renew the configration class, to undo all tampering with it.
        \Krexx::$pool->config = \Krexx::$pool->createClass('\\Brainworxx\\Krexx\\Service\\Config\\Config');
        // Purge possible redirtects for the working folders
        static::$logFolder = '';
        static::$chunkFolder = '';
        static::$configFile = '';

        static::$blacklistDebugMethods = array();

        // Go through the remaining plugins.
        static::$plugins[$name][static::IS_ACTIVE] = false;
        foreach (static::$plugins as $pluginName => $plugin) {
            if ($plugin[static::IS_ACTIVE]) {
                /** @var \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface $staticPlugin */
                $staticPlugin = static::$plugins[$pluginName][static::CONFIG_CLASS];
                $staticPlugin::exec();
            }
        }
    }

    /**
     * Getter for the configured configuration file
     *
     * @internal
     *
     * @return string
     *   Absolute path to the configuration file.
     */
    public static function getConfigFile()
    {
        if (empty(static::$configFile)) {
            static::$configFile = KREXX_DIR . 'config/Krexx.ini';
        }

        return static::$configFile;
    }

    /**
     * Setter for the path to the chunks folder.
     *
     * @internal
     *
     * @return string
     *   The absolute path to the chunks folder.
     */
    public static function getChunkFolder()
    {
        if (empty(static::$chunkFolder)) {
            static::$chunkFolder = KREXX_DIR . 'chunks/';
        }

        return static::$chunkFolder;
    }

    /**
     * Getter for the logfolder.
     *
     * @internal
     *
     * @return string
     *   The absolute path to the log folder.
     */
    public static function getLogFolder()
    {
        if (empty(static::$logFolder)) {
            static::$logFolder = KREXX_DIR . 'log/';
        }

        return static::$logFolder;
    }

    /**
     * Getter for the blacklisted debugmethods.
     *
     * @return array
     *   The debug methods.
     */
    public static function getMethodDebugBlacklist()
    {
        return static::$blacklistDebugMethods;
    }
}
