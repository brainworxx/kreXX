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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Debug;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Throwable;
use Exception;
use ReflectionException;

/**
 * Poll all configured debug methods of a class.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses mixed data
 *   The class we are currently analysing.
 * @uses string name
 *   The name of the object we are analysing.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class DebugMethods extends AbstractObjectAnalysis
{
    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods';

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of cause, not stop a possible fatal in the function
     * itself.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        $data = $reflectionClass->getData();
        $output = $this->dispatchStartEvent();

        foreach (explode(',', $this->pool->config->getSetting(Fallback::SETTING_DEBUG_METHODS)) as $funcName) {
            if ($this->checkIfAccessible($data, $funcName, $reflectionClass) === true) {
                // Add a try to prevent the hosting CMS from doing something stupid.
                try {
                    // We need to deactivate the current error handling to
                    // prevent the host system to do anything stupid.
                    set_error_handler(
                        function () {
                            // Do nothing.
                        }
                    );
                    $result = $data->$funcName();
                } catch (Throwable $e) {
                    //Restore the previous error handler, and return an empty string.
                    restore_error_handler();
                    continue;
                } catch (Exception $e) {
                    // Restore the old error handler and move to the next method.
                    restore_error_handler();
                    continue;
                }

                // Reactivate whatever error handling we had previously.
                restore_error_handler();

                // We ignore NULL values, as well as the exceptions from above.
                if (isset($result) === true) {
                    $output .= $this->pool->render->renderExpandableChild(
                        $this->dispatchEventWithModel(
                            $funcName,
                            $this->pool->createClass(Model::class)
                                ->setName($funcName)
                                ->setType(static::TYPE_DEBUG_METHOD)
                                ->setNormal(static::UNKNOWN_VALUE)
                                ->setHelpid($funcName)
                                ->setConnectorType(Connectors::METHOD)
                                ->addParameter(static::PARAM_DATA, $result)
                                ->injectCallback(
                                    $this->pool->createClass(Debug::class)
                                )
                        )
                    );
                    unset($result);
                }
            }
        }

        return $output;
    }

    /**
     * Check if we are allowed to access this class method as a debug method for this class.
     *
     * @param mixed $data
     *   The class that we are currently analysing.
     * @param string $funcName
     *   The name of the function that we want to call.
     * @param ReflectionClass $reflectionClass
     *   The reflection of the class that we are currently analysing.
     *
     * @return boolean
     *   Whether or not we are allowed toi access this method.
     */
    protected function checkIfAccessible($data, $funcName, ReflectionClass $reflectionClass)
    {
        // We need to check if:
        // 1.) Method exists. It may be protected though.
        // 2.) Method can be called. There may be a magical method, though.
        // 3.) It's not blacklisted.
        if (method_exists($data, $funcName) === true &&
            is_callable([$data, $funcName]) === true &&
            $this->pool->config->validation->isAllowedDebugCall($data) === true) {
            // We need to check if the callable function requires any parameters.
            // We will not call those, because we simply can not provide them.
            try {
                $ref = $reflectionClass->getMethod($funcName);
            } catch (ReflectionException $e) {
                return false;
            }


            /** @var \ReflectionParameter $param */
            foreach ($ref->getParameters() as $param) {
                if ($param->isOptional() === false) {
                    // We've got a required parameter!
                    // We will not call this one.
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
