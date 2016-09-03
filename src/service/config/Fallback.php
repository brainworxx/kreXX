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

namespace Brainworxx\Krexx\Service\Config;

use Brainworxx\Krexx\Service\Storage;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Fallback
{

    /**
     * Here we store all relevant data.
     *
     * @var Storage
     */
    protected $storage;

    /**
     * Security measures for the configuration.
     *
     * @var \Brainworxx\Krexx\Service\Config\Security
     */
    public $security;

    /**
     * Injects the storage and initializes the security.
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->security = new Security($storage);
        $this->storage = $storage;
    }

    /**
     * Stores if kreXX is actually enabled.
     *
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * Fallback settings, in case there is nothing in the config ini.
     *
     * @var array
     */
    public $configFallback = array(
        'runtime' => array(
            'disabled' => 'false',
            'detectAjax' => 'true',
            'level' => '5',
            'maxCall' => '10',
            'memoryLeft' => '64',
            'maxRuntime' => '60',
        ),
        'output' => array(
            'skin' => 'smokygrey',
            'destination' => 'frontend',
            'folder' => 'log',
            'maxfiles' => '10',
        ),
        'properties' => array(
            'analyseProtected' => 'false',
            'analysePrivate' => 'false',
            'analyseConstants' => 'true',
            'analyseTraversable' => 'true',
        ),
        'methods' => array(
            'analyseMethodsAtall' => 'true',
            'analyseProtectedMethods' => 'false',
            'analysePrivateMethods' => 'false',
            'debugMethods' => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
        ),
        'backtraceAndError' => array(
            'registerAutomatically' => 'false',
            'backtraceAnalysis' => 'deep',
        ),
    );

    /**
     * Settings, what can be edited on the frontend, and what not.
     *
     * @var array
     */
    public $feConfigFallback = array(
        'analyseMethodsAtall' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtectedMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivateMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtected' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivate' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseConstants' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseTraversable' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'debugMethods' => array(
            'type' => 'Input',
            'editable' => 'false',
        ),
        'level' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxCall' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'disabled' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'destination' => array(
            'type' => 'Select',
            'editable' => 'false',
        ),
        'maxfiles' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'folder' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'skin' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'registerAutomatically' => array(
            'type' => 'Select',
            'editable' => 'false',
        ),
        'detectAjax' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'backtraceAnalysis' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'memoryLeft' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxRuntime' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'Local open function' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
    );

    /**
     * The directory where kreXX is stored.
     *
     * @var string
     */
    public $krexxdir;

    /**
     * Caching for the local settings.
     *
     * @var array
     */
    protected $localConfig = array();

    /**
     * Path to the configuration file.
     *
     * @var string
     */
    protected $pathToIni;

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '2.0.1 dev';
}
