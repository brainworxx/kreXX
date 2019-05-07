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

namespace Brainworxx\Krexx\Service\Config;

use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\View\Skins\RenderHans;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
abstract class Fallback implements ConstInterface
{
    const RENDER = 'render';
    const EVALUATE = 'eval';
    const VALUE = 'value';
    const SECTION = 'section';

    const EVAL_BOOL = 'evalBool';
    const EVAL_INT = 'evalInt';
    const EVAL_MAX_RUNTIME = 'evalMaxRuntime';
    const DO_NOT_EVAL = 'doNotEval';
    const EVAL_DESTINATION = 'evalDestination';
    const EVAL_SKIN = 'evalSkin';
    const EVAL_IP_RANGE = 'evalIpRange';
    const EVAL_DEV_HANDLE = 'evalDevHandle';
    const EVAL_DEBUG_METHODS = 'evalDebugMethods';

    const SECTION_OUTPUT = 'output';
    const SECTION_BEHAVIOR = 'behavior';
    const SECTION_PRUNE = 'prune';
    const SECTION_PROPERTIES = 'properties';
    const SECTION_METHODS = 'methods';
    const SECTION_EMERGENCY = 'emergency';

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';
    const VALUE_BROWSER = 'browser';
    const VALUE_FILE = 'file';

    const SETTING_DISABLED = 'disabled';
    const SETTING_IP_RANGE = 'iprange';
    const SETTING_SKIN = 'skin';
    const SETTING_DESTINATION = 'destination';
    const SETTING_MAX_FILES = 'maxfiles';
    const SETTING_DETECT_AJAX = 'detectAjax';
    const SETTING_NESTING_LEVEL = 'level';
    const SETTING_MAX_CALL = 'maxCall';
    const SETTING_MAX_RUNTIME = 'maxRuntime';
    const SETTING_MEMORY_LEFT = 'memoryLeft';
    const SETTING_USE_SCOPE_ANALYSIS = 'useScopeAnalysis';
    const SETTING_ANALYSE_PROTECTED = 'analyseProtected';
    const SETTING_ANALYSE_PRIVATE = 'analysePrivate';
    const SETTING_ANALYSE_TRAVERSABLE = 'analyseTraversable';
    const SETTING_ANALYSE_PROTECTED_METHODS = 'analyseProtectedMethods';
    const SETTING_ANALYSE_PRIVATE_METHODS = 'analysePrivateMethods';
    const SETTING_ANALYSE_GETTER = 'analyseGetter';
    const SETTING_DEBUG_METHODS = 'debugMethods';
    const SETTING_MAX_STEP_NUMBER = 'maxStepNumber';
    const SETTING_ARRAY_COUNT_LIMIT = 'arrayCountLimit';
    const SETTING_DEV_HANDLE = 'devHandle';

    const RENDER_TYPE = 'Type';
    const RENDER_EDITABLE = 'Editable';
    // The render type is also part of the template filename of the
    // cookie editor.
    const RENDER_TYPE_SELECT = 'Select';
    const RENDER_TYPE_INPUT = 'Input';
    const RENDER_TYPE_NONE = 'None';

    const RENDER_TYPE_INI_FULL = 'full';
    const RENDER_TYPE_INI_DISPLAY = 'display';
    const RENDER_TYPE_INI_NONE = 'none';

    const SKIN_SMOKY_GREY = 'smokygrey';
    const SKIN_HANS = 'hans';

    /**
     * Defining the layout of the frontend editing form.
     *
     * @var array
     */
    public $configFallback;

    /**
     * Values, rendering settings and the actual fallback value.
     *
     * @var array
     */
    public $feConfigFallback;

    /**
     * Render settings for a editable select field.
     *
     * @var array
     */
    protected $editableSelect = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    ];

    /**
     * Render settings for a editable input field.
     *
     * @var array
     */
    protected $editableInput = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    ];

    /**
     * Render settings for a display only input field.
     *
     * @var array
     */
    protected $displayOnlyInput = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    /**
     * Render settings for a display only select field.
     *
     * @var array
     */
    protected $displayOnlySelect = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    /**
     * Render settings for a field which will not be displayed, or accept values.
     *
     * @var array
     */
    protected $displayNothing = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_NONE,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    protected $skinConfiguration = [];

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool and initialize the fallback configuration, get the skins.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        $this->configFallback = [
            Fallback::SECTION_OUTPUT => [
                Fallback::SETTING_DISABLED,
                Fallback::SETTING_IP_RANGE,
                Fallback::SETTING_DETECT_AJAX,
            ],
            Fallback::SECTION_BEHAVIOR =>[
                Fallback::SETTING_SKIN,
                Fallback::SETTING_DESTINATION,
                Fallback::SETTING_MAX_FILES,
                Fallback::SETTING_USE_SCOPE_ANALYSIS,
            ],
            Fallback::SECTION_PRUNE => [
                Fallback::SETTING_MAX_STEP_NUMBER,
                Fallback::SETTING_ARRAY_COUNT_LIMIT,
                Fallback::SETTING_NESTING_LEVEL,
            ],
            Fallback::SECTION_PROPERTIES => [
                Fallback::SETTING_ANALYSE_PROTECTED,
                Fallback::SETTING_ANALYSE_PRIVATE,
                Fallback::SETTING_ANALYSE_TRAVERSABLE,
            ],
            Fallback::SECTION_METHODS => [
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
                Fallback::SETTING_ANALYSE_GETTER,
                Fallback::SETTING_DEBUG_METHODS,
            ],
            Fallback::SECTION_EMERGENCY => [
                Fallback::SETTING_MAX_CALL,
                Fallback::SETTING_MAX_RUNTIME,
                Fallback::SETTING_MEMORY_LEFT,
            ],
        ];

        $this->feConfigFallback = [
            Fallback::SETTING_ANALYSE_PROTECTED_METHODS => [
                // Analyse protected class methods.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_METHODS,
            ],
            Fallback::SETTING_ANALYSE_PRIVATE_METHODS => [
                // Analyse private class methods.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_METHODS,
            ],
            Fallback::SETTING_ANALYSE_PROTECTED => [
                // Analyse protected class properties.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ],
            Fallback::SETTING_ANALYSE_PRIVATE => [
                // Analyse private class properties.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ],
            Fallback::SETTING_ANALYSE_TRAVERSABLE => [
                // Analyse traversable part of classes.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ],
            Fallback::SETTING_DEBUG_METHODS => [
                // Debug methods that get called.
                // A debug method must be public and have no parameters.
                // Change these only if you know what you are doing.
                Fallback::VALUE => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVALUATE => Fallback::EVAL_DEBUG_METHODS,
                Fallback::SECTION =>  Fallback::SECTION_METHODS,
            ],
            Fallback::SETTING_NESTING_LEVEL => [
                // Maximum nesting level.
                Fallback::VALUE => 5,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE,
            ],
            Fallback::SETTING_MAX_CALL => [
                // Maximum amount of kreXX calls.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ],
            Fallback::SETTING_DISABLED => [
                // Disable kreXX.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ],
            Fallback::SETTING_DESTINATION => [
                // Output destination. Either 'file' or 'browser'.
                Fallback::VALUE => Fallback::VALUE_BROWSER,
                Fallback::RENDER => $this->displayOnlySelect,
                Fallback::EVALUATE => Fallback::EVAL_DESTINATION,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ],
            Fallback::SETTING_MAX_FILES => [
                // Maximum files that are kept inside the logfolder.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ],
            Fallback::SETTING_SKIN => [
                Fallback::VALUE => static::SKIN_SMOKY_GREY,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_SKIN,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ],
            Fallback::SETTING_DETECT_AJAX => [
                // Try to detect ajax requests.
                // If set to 'true', kreXX is disabled for them.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ],
            Fallback::SETTING_IP_RANGE => [
                // IP range for calling kreXX.
                // kreXX is disabled for everyone who dies not fit into this range.
                Fallback::VALUE => '*',
                Fallback::RENDER => $this->displayNothing,
                Fallback::EVALUATE => Fallback::EVAL_IP_RANGE,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ],
            Fallback::SETTING_DEV_HANDLE => [
                Fallback::VALUE => '',
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_DEV_HANDLE,
                Fallback::SECTION => ''
            ],
            Fallback::SETTING_ANALYSE_GETTER => [
                // Analyse the getter methods of a class and try to
                // get a possible return value without calling the method.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION =>  Fallback::SECTION_METHODS,
            ],
            Fallback::SETTING_MEMORY_LEFT => [
                // Maximum MB memory left, before triggering an emergency break.
                Fallback::VALUE => 64,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ],
            Fallback::SETTING_MAX_RUNTIME => [
                // Maximum runtime in seconds, before triggering an emergency break.
                Fallback::VALUE => 60,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_MAX_RUNTIME,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ],
            Fallback::SETTING_USE_SCOPE_ANALYSIS => [
                // Use the scope analysis (aka auto configuration).
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ],
            Fallback::SETTING_MAX_STEP_NUMBER => [
                // Maximum step numbers that get analysed from a backtrace.
                // All other steps be be omitted.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE,
            ],
            Fallback::SETTING_ARRAY_COUNT_LIMIT => [
                // Limit for the count in an array. If an array is larger that this,
                // we will use the ThroughLargeArray callback
                Fallback::VALUE => 300,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE
            ],
        ];

        // Setting up out two bundled skins.
        $this->skinConfiguration = array_merge(
            [
                static::SKIN_SMOKY_GREY => [
                    static::SKIN_CLASS => RenderSmokyGrey::class,
                    static::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/smokygrey/'
                ],
                static::SKIN_HANS => [
                    static::SKIN_CLASS => RenderHans::class,
                    static::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/hans/'
                ]
            ],
            SettingsGetter::getAdditionalSkinList()
        );
    }

    /**
     * List of stuff who's fe-editing status can not be changed. Never.
     *
     * @see Tools::evaluateSetting
     *   Evaluating everything in here will fail, meaning that the
     *   setting will not be accepted.
     *
     * @var array
     */
    protected $feConfigNoEdit = [
        Fallback::SETTING_DESTINATION,
        Fallback::SETTING_MAX_FILES,
        Fallback::SETTING_DEBUG_METHODS,
        Fallback::SETTING_IP_RANGE,
    ];

    /**
     * These classes will never be polled by debug methods, because that would
     * most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security->isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects->pollAllConfiguredDebugMethods()
     *
     * @var array
     */
    protected $classBlacklist = [
        // Fun with reflection classes. Not really.
        \ReflectionType::class,
        \ReflectionGenerator::class,
        \Reflector::class,
    ];

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '3.1.0 dev';
}
