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

namespace Brainworxx\Krexx\Logging;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Shared helper methods for logging.
 */
trait LoggingTrait
{
     /**
     * Configure everything to start the forced logging.
     */
    protected static function startForcedLog(): void
    {
        Pool::createPool();

        $source = Krexx::$pool->messages->getHelp('forcedLogging');
        // Output destination: file
        Krexx::$pool->config
            ->settings[ConfigConstInterface::SETTING_DESTINATION]
            ->setSource($source)
            ->setValue(ConfigConstInterface::VALUE_FILE);

        // Do not care about ajax requests.
        Krexx::$pool->config
            ->settings[ConfigConstInterface::SETTING_DETECT_AJAX]
            ->setSource($source)
            ->setValue(false);

        // Reload the disabled settings with the new ajax setting.
         Krexx::$pool->config
            ->loadConfigValue(ConfigConstInterface::SETTING_DISABLED);
    }

    /**
     * Reset everything after the forced logging.
     */
    protected static function endForcedLog(): void
    {
        // Reset everything afterwards.
        Krexx::$pool->config = Krexx::$pool
            ->createClass(Config::class);
    }
}
