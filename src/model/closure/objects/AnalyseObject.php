<?php
/**
 * @file
 *   Model for the view rendering, hosting the object analysis closure.
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

namespace Brainworxx\Krexx\Model\Closure\Objects;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\Codegen;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Analysis\Objects\Flection;
use Brainworxx\Krexx\Analysis\Objects\Properties;

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
            $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Public properties');
            // Adding a HR to reflect that the following stuff are not public
            // properties anymore.
            $output .= SkinRender::renderSingeChildHr();
        }

        // Dumping protected properties.
        if (Config::getConfigValue('properties', 'analyseProtected') == 'true' || Codegen::isInScope()) {
            $refProps = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
            usort($refProps, $sortingCallback);

            if (count($refProps)) {
                $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Protected properties');
            }
        }

        // Dumping private properties.
        if (Config::getConfigValue('properties', 'analysePrivate') == 'true' || Codegen::isInScope()) {
            $refProps = $ref->getProperties(\ReflectionProperty::IS_PRIVATE);
            usort($refProps, $sortingCallback);
            if (count($refProps)) {
                $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Private properties');
            }
        }

        // Dumping class constants.
        if (Config::getConfigValue('properties', 'analyseConstants') == 'true') {
            $output .= Properties::getReflectionConstantsData($ref);
        }

        // Dumping all methods.
        $output .= $this->getMethodData($data);

        // Dumping traversable data.
        if (Config::getConfigValue('properties', 'analyseTraversable') == 'true') {
            $output .= Objects::getTraversableData($data, $name);
        }

        // Dumping all configured debug functions.
        $output .= Objects::pollAllConfiguredDebugMethods($data);

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
            $model = new AnalyseMethods();
            $model->setName('Methods')
                ->setType('class internals')
                ->addParameter('ref', $ref)
                ->addParameter('methods', $methods);

            return SkinRender::renderExpandableChild($model);
        }
        return '';
    }
}