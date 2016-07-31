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

namespace Brainworxx\Krexx\Model\Objects;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Analysis\Codegen;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Analysis\Flection;

/**
 * Object analysis methods.
 *
 * @package Brainworxx\Krexx\Model\Objects
 */
class AnalyseObject extends Simple
{
    /**
     * Starts the dump of an object.
     *
     * @return string
     */
    public function renderMe()
    {
        $data = $this->parameters['data'];
        $name = $this->parameters['name'];
        $output = SkinRender::renderSingeChildHr();

        $ref = new \ReflectionClass($data);

        // Dumping public properties.
        $refProps = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        // Adding undeclared public properties to the dump.
        // Those are properties which are not visible with
        // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        // but are in get_object_vars();
        // 1. Make a list of all properties
        // 2. Remove those that are listed in
        // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        // What is left are those special properties that were dynamically
        // set during runtime, but were not declared in the class.
        foreach ($refProps as $refProp) {
            $publicProps[$refProp->name] = $refProp->name;
        }
        foreach (get_object_vars($data) as $key => $value) {
            if (!isset($publicProps[$key])) {
                $refProps[] = new Flection($value, $key);
            }
        }

        // We will dump the properties alphabetically sorted, via this callback.
        $sortingCallback = function ($a, $b) {
            return strcmp($a->name, $b->name);
        };

        if (count($refProps)) {
            usort($refProps, $sortingCallback);
            $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Public properties');
            // Adding a HR to reflect that the following stuff are not public
            // properties anymore.
            $output .= SkinRender::renderSingeChildHr();
        }

        // Dumping protected properties.
        if (Config::getConfigValue('properties', 'analyseProtected') == 'true' || Codegen::isInScope()) {
            $refProps = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
            usort($refProps, $sortingCallback);

            if (count($refProps)) {
                $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Protected properties');
            }
        }

        // Dumping private properties.
        if (Config::getConfigValue('properties', 'analysePrivate') == 'true' || Codegen::isInScope()) {
            $refProps = $ref->getProperties(\ReflectionProperty::IS_PRIVATE);
            usort($refProps, $sortingCallback);
            if (count($refProps)) {
                $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Private properties');
            }
        }

        // Dumping class constants.
        if (Config::getConfigValue('properties', 'analyseConstants') == 'true') {
            $output .= $this->getReflectionConstantsData($ref);
        }

        // Dumping all methods.
        $output .= $this->getMethodData($data);

        // Dumping traversable data.
        if (Config::getConfigValue('properties', 'analyseTraversable') == 'true') {
            $output .= $this->getTraversableData($data, $name);
        }

        // Dumping all configured debug functions.
        $output .= $this->pollAllConfiguredDebugMethods($data);

        // Adding a HR for a better readability.
        $output .= SkinRender::renderSingeChildHr();
        return $output;
    }

    /**
     * Decides which methods we want to analyse and then starts the dump.
     *
     * @param object $data
     *   The object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected function getMethodData($data)
    {
        // Dumping all methods but only if we have any.
        $public = array();
        $protected = array();
        $private = array();
        $ref = new \ReflectionClass($data);
        if (Config::getConfigValue('methods', 'analyseMethodsAtall') == 'true') {
            $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

            if (Config::getConfigValue('methods', 'analyseProtectedMethods') == 'true' || Codegen::isInScope()) {
                $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
            }
            if (Config::getConfigValue('methods', 'analysePrivateMethods') == 'true' || Codegen::isInScope()) {
                $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
            }
        }

        // Is there anything to analyse?
        $methods = array_merge($public, $protected, $private);
        if (count($methods)) {
            // We need to sort these alphabetically.
            $sortingCallback = function ($a, $b) {
                return strcmp($a->name, $b->name);
            };
            usort($methods, $sortingCallback);
            $model = new IterateThroughMethods();
            $model->setName('Methods')
                ->setType('class internals')
                ->addParameter('ref', $ref)
                ->addParameter('methods', $methods);

            return SkinRender::renderExpandableChild($model);
        }
        return '';
    }

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of cause, not stop a possible fatal in the function
     * itself.
     *
     * @param object $data
     *   The object we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    protected function pollAllConfiguredDebugMethods($data)
    {
        $output = '';

        $funcList = explode(',', Config::getConfigValue('methods', 'debugMethods'));
        foreach ($funcList as $funcName) {
            if (is_callable(array(
                    $data,
                    $funcName,
                )) && Config::isAllowedDebugCall($data, $funcName)
            ) {
                $foundRequired = false;
                // We need to check if this method actually exists. Just because it is
                // callable does not mean it exists!
                if (method_exists($data, $funcName)) {
                    // We need to check if the callable function requires any parameters.
                    // We will not call those, because we simply can not provide them.
                    // Interestingly, some methods of a class are callable, but are not
                    // implemented. This means, that when I try to get a reflection,
                    // it will result in a WSOD.
                    $ref = new \ReflectionMethod($data, $funcName);
                    $params = $ref->getParameters();
                    foreach ($params as $param) {
                        if (!$param->isOptional()) {
                            // We've got a required parameter!
                            // We will not call this one.
                            $foundRequired = true;
                        }
                    }
                    unset($ref);
                } else {
                    // It's callable, but does not exist. Looks like a __call fallback.
                    // We will not poll it for data.
                    $foundRequired = true;
                }

                if ($foundRequired == false) {
                    // Add a try to prevent the hosting CMS from doing something stupid.
                    try {
                        // We need to deactivate the current error handling to
                        // prevent the host system to do anything stupid.
                        set_error_handler(function () {
                            // Do nothing.
                        });
                        $result = $data->$funcName();
                        // Reactivate whatever error handling we had previously.
                        restore_error_handler();
                    } catch (\Exception $e) {
                        // Do nothing.
                    }
                    if (isset($result)) {
                        $model = new IterateThroughDebug();
                        $model->setName($funcName)
                            ->setType('debug method')
                            ->setAdditional('. . .')
                            ->setHelpid($funcName)
                            ->setConnector1('->')
                            ->setConnector2('() =')
                            ->addParameter('result', $result);

                        $output .= SkinRender::renderExpandableChild($model);
                        unset($result);
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Dumps all available traversable data.
     *
     * @param \Iterator $data
     *   The object we are analysing.
     * @param string $name
     *   The name of the object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected function getTraversableData($data, $name)
    {
        if (is_a($data, 'Traversable')) {
            $connector1 = '';
            $connector2 = '';

            // If we are facing a IteratorAggregate, we can not access the array
            // directly. To do this, we must get the Iterator from the class.
            // For our analysis is it not really important, because it does not
            // change anything. We need this for the automatic code generation.
            if (is_a($data, 'IteratorAggregate')) {
                $connector2 = '->getIterator()';
                // Remove the name, because this would then get added to the source
                // generation, resulting in unusable code.
                $name = '';
            }

            // SplObjectStorage objects are something 'special'.
            // You can only get their value by wrapping then with a
            // iterator_or_array() or via a foreach and then using the
            // key. Either can not be generated by the code generator. :-(
            if (is_a($data, 'SplObjectStorage')) {
                $connector1 = '';
                $name = '';
                $connector2 = '. . .';
            }
            $model = new IterateThroughTraversable();
            $parameter = iterator_to_array($data);
            $model->setName($name)
                ->setType('Foreach')
                ->setAdditional('Traversable Info')
                ->setConnector1($connector1)
                ->setConnector2($connector2)
                ->addParameter('data', $parameter);

            return SkinRender::renderExpandableChild($model);
        }
        return '';
    }

    /**
     * Dumps the constants of a class,
     *
     * @param \ReflectionClass $ref
     *   The already generated reflection of said class
     *
     * @return string
     *   The generated markup.
     */
    protected function getReflectionConstantsData(\ReflectionClass $ref)
    {
        // This is actually an array, we ara analysing. But We do not want to render
        // an array, so we need to process it like the return from an iterator.
        $refConst = $ref->getConstants();

        if (count($refConst) > 0) {
            // We've got some values, we will dump them.
            $model = new AnalyseConstants();
            $model->setName('Constants')
                ->setType('class internals')
                ->addParameter('refConst', $refConst);

            return SkinRender::renderExpandableChild($model);
        }

        // Nothing to see here, return an empty string.
        return '';
    }

    /**
     * Gets the properties from a reflection property of the object.
     *
     * @param array $refProps
     *   The list of the reflection properties.
     * @param \ReflectionClass $ref
     *   The reflection of the object we are currently analysing.
     * @param object $data
     *   The object we are currently analysing.
     * @param string $label
     *   The additional part of the template file.
     *
     * @return string
     *   The generated markup.
     */
    protected function getReflectionPropertiesData(array $refProps, \ReflectionClass $ref, $data, $label)
    {
        // We are dumping public properties direct into the main-level, without
        // any "abstraction level", because they can be accessed directly.
        $model = new IterateThroughProperties();
        $model->addParameter('refProps', $refProps)
            ->addParameter('ref', $ref)
            ->addParameter('orgObject', $data);

        if (strpos(strtoupper($label), 'PUBLIC') === false) {
            // Protected or private properties.
            $model->setName($label)
                ->setType('class internals');
            return SkinRender::renderExpandableChild($model);
        } else {
            // Public properties.
            $model->setAdditional($label);
            return SkinRender::renderExpandableChild($model);
        }
    }
}