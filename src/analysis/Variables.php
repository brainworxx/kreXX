<?php
/**
 * @file
 *   Variables analysis functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
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

namespace Brainworxx\Krexx\Analysis;

use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Framework\Internals;
use Brainworxx\Krexx\Model\Closure\Variables\AnalyseArray;
use Brainworxx\Krexx\Model\Closure\Variables\IterateThrough;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Config;

/**
 * This class hosts the variable analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class Variables
{
    /**
     * Dump information about a variable.
     *
     * This function decides what functions analyse the data
     * and acts as a hub.
     *
     * @param mixed $data
     *   The variable we are analysing.
     * @param string $name
     *   The name of the variable, if available.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The generated markup.
     */
    public static function analysisHub(&$data, $name = '', $connector1 = '', $connector2 = '')
    {

        // Check memory and runtime.
        if (!Internals::checkEmergencyBreak()) {
            // No more took too long, or not enough memory is left.
            Messages::addMessage("Emergency break for large output during analysis process.");
            return '';
        }

        // If we are currently analysing an array, we might need to add stuff to
        // the connector.
        if ($connector1 == '[' && is_string($name)) {
            $connector1 = $connector1 . "'";
            $connector2 = "'" . $connector2;
        }

        // Object?
        // Closures are analysed separately.
        if (is_object($data) && !is_a($data, '\Closure')) {
            Internals::$nestingLevel++;
            if (Internals::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                $result = Objects::analyseObject($data, $name, '', $connector1, $connector2);
                Internals::$nestingLevel--;
                return $result;
            } else {
                Internals::$nestingLevel--;
                return Variables::analyseString('Object => ' . Help::getHelp('maximumLevelReached'), $name);
            }
        }

        // Closure?
        if (is_object($data) && is_a($data, '\Closure')) {
            Internals::$nestingLevel++;
            if (Internals::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                if ($connector2 == '] =') {
                    $connector2 = ']';
                }
                $result = Objects::analyseClosure($data, $name, '', $connector1, $connector2);
                Internals::$nestingLevel--;
                return $result;
            } else {
                Internals::$nestingLevel--;
                return Variables::analyseString('Closure => ' . Help::getHelp('maximumLevelReached'), $name);
            }
        }

        // Array?
        if (is_array($data)) {
            Internals::$nestingLevel++;
            if (Internals::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                $result = Variables::analyseArray($data, $name, '', $connector1, $connector2);
                Internals::$nestingLevel--;
                return $result;
            } else {
                Internals::$nestingLevel--;
                return Variables::analyseString('Array => ' . Help::getHelp('maximumLevelReached'), $name);
            }
        }

        // Resource?
        if (is_resource($data)) {
            return Variables::analyseResource($data, $name, '', $connector1, $connector2);
        }

        // String?
        if (is_string($data)) {
            return Variables::analyseString($data, $name, '', $connector1, $connector2);
        }

        // Float?
        if (is_float($data)) {
            return Variables::analyseFloat($data, $name, '', $connector1, $connector2);
        }

        // Integer?
        if (is_int($data)) {
            return Variables::analyseInteger($data, $name, '', $connector1, $connector2);
        }

        // Boolean?
        if (is_bool($data)) {
            return Variables::analyseBoolean($data, $name, '', $connector1, $connector2);
        }

        // Null ?
        if (is_null($data)) {
            return Variables::analyseNull($name, '', $connector1, $connector2);
        }

        // Still here? This should not happen. Return empty string, just in case.
        return '';
    }

    /**
     * Render a dump for the properties of an array or object.
     *
     * @param array &$data
     *   The array we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public static function iterateThrough(&$data)
    {
        $model = new IterateThrough();
        $model->addParameter('data', $data);
        return SkinRender::renderExpandableChild($model);
    }

    /**
     * Render a 'dump' for a NULL value.
     *
     * @param string $name
     *   The Name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseNull($name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'NULL';
        $data = 'NULL';

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($data)
            ->setType($additional . 'null')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }

    /**
     * Render a dump for an array.
     *
     * @param array $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseArray(array &$data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'array';
        $json['count'] = (string)count($data);

        // Dumping all Properties.
        $model = new AnalyseArray();
        $model->setName($name)
            ->setType($additional . 'array')
            ->setAdditional(count($data) . ' elements')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json)
            ->addParameter('data', $data);

        return SkinRender::renderExpandableChild($model);
    }

    /**
     * Analyses a resource.
     *
     * @param resource $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseResource($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'resource';
        $data = get_resource_type($data);

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($data)
            ->setType($additional . 'resource')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }

    /**
     * Render a dump for a bool value.
     *
     * @param bool $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseBoolean($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'boolean';
        $data = $data ? 'TRUE' : 'FALSE';

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($data)
            ->setType($additional . 'boolean')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }

    /**
     * Render a dump for a integer value.
     *
     * @param int $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseInteger($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'integer';

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($data)
            ->setType($additional . 'integer')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }

    /**
     * Render a dump for a float value.
     *
     * @param float $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseFloat($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'float';

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($data)
            ->setType($additional . 'float')
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }

    /**
     * Render a dump for a string value.
     *
     * @param string $data
     *   The data we are analysing.
     * @param string $name
     *   The name, what we render here.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseString($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        $json = array();
        $json['type'] = 'string';

        // Extra ?
        $cut = $data;
        if (strlen($data) > 50) {
            $cut = substr($data, 0, 50 - 3) . '...';
        }

        $json['encoding'] = @mb_detect_encoding($data);
        // We need to take care for mixed encodings here.
        $json['length'] = (string)$strlen = @mb_strlen($data, $json['encoding']);
        if ($strlen === false) {
            // Looks like we have a mixed encoded string.
            $json['length'] = '~ ' . strlen($data);
            $strlen = ' broken encoding ' . $json['length'];
            $json['encoding'] = 'broken';
        }

        $model = new Simple();
        $model->setData($data)
            ->setName($name)
            ->setNormal($cut)
            ->setType($additional . 'string' . ' ' . $strlen)
            ->setConnector1($connector1)
            ->setConnector2($connector2)
            ->setJson($json);

        return SkinRender::renderSingleChild($model);
    }
}
