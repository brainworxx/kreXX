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

namespace Brainworxx\Krexx\Analysis;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Model\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\Config\Config;

/**
 * "Routing" for kreXX
 *
 * The analysisHub decides what to do next with the model.
 * The other method ara also used, in case it is known how
 * to proceed next.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class Routing
{
    /**
     * Dump information about a variable.
     *
     * This function decides what functions analyse the data
     * and acts as a hub.
     *
     * @param Simple $model
     *   The variable we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    public static function analysisHub(Simple $model)
    {
        // Check memory and runtime.
        if (!OutputActions::$emergencyHandler->checkEmergencyBreak()) {
            return '';
        }
        $data = $model->getData();
        $name = $model->getName();

        // Check nesting level
        OutputActions::$nestingLevel++;
        if (OutputActions::$nestingLevel >= (int)Config::getConfigValue('runtime', 'level')) {
            OutputActions::$nestingLevel--;
            $text = gettype($data) . ' => ' . OutputActions::$render->getHelp('maximumLevelReached');
            $model->setData($text)
                ->setName($name);
            return Routing::analyseString($model);
        }

        // Check for recursion.
        if (is_object($data) || is_array($data)) {
            if (OutputActions::$recursionHandler->isInHive($data)) {
                // Render recursion.
                $model->setNormal($model->getName())
                    ->setDomid(self::generateDomIdFromObject($data))
                    ->setType($model->getAdditional() . 'class');
                $result = OutputActions::$render->renderRecursion($model);
                OutputActions::$nestingLevel--;
                return $result;
            }
            // Remember that we've been here before.
            OutputActions::$recursionHandler->addToHive($data);
        }

        $connector1 = $model->getConnector1();
        $connector2 = $model->getConnector2();

        // If we are currently analysing an array, we might need to add stuff to
        // the connector.
        if ($connector1 == '[' && is_string($name)) {
            $connector1 = $connector1 . "'";
            $connector2 = "'" . $connector2;
        }
        $model->setConnector1($connector1)
            ->setConnector2($connector2);


        // Object?
        // Closures are analysed separately.
        if (is_object($data) && !is_a($data, '\Closure')) {
            $result = self::analyseObject($model);
            OutputActions::$nestingLevel--;
            return $result;
        }

        // Closure?
        if (is_object($data) && is_a($data, '\Closure')) {
            if ($connector2 == '] =') {
                $connector2 = ']';
                $model->setConnector2($connector2);
            }
            $result = self::analyseClosure($model);
            OutputActions::$nestingLevel--;
            return $result;
        }

        // Array?
        if (is_array($data)) {
            $result = Routing::analyseArray($model);
            OutputActions::$nestingLevel--;
            return $result;
        }

        // Resource?
        if (is_resource($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseResource($model);
        }

        // String?
        if (is_string($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseString($model);
        }

        // Float?
        if (is_float($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseFloat($model);
        }

        // Integer?
        if (is_int($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseInteger($model);
        }

        // Boolean?
        if (is_bool($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseBoolean($model);
        }

        // Null ?
        if (is_null($data)) {
            OutputActions::$nestingLevel--;
            return Routing::analyseNull($model);
        }

        // Still here? This should not happen. Return empty string, just in case.
        OutputActions::$nestingLevel--;
        return '';
    }

    /**
     * Render a 'dump' for a NULL value.
     *
     * @param Simple $model
     *   The model with the data for the output.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseNull(Simple $model)
    {
        $json = array();
        $json['type'] = 'NULL';
        $data = 'NULL';

        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'null')
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Render a dump for an array.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseArray(Simple $model)
    {
        $json = array();
        $json['type'] = 'array';
        $json['count'] = (string)count($model->getData());

        // Dumping all Properties.
        $model->setType($model->getAdditional() . 'array')
            ->setAdditional($json['count'] . ' elements')
            ->setJson($json)
            ->addParameter('data', $model->getData())
            ->initCallback('Iterate\ThroughArray');

        return OutputActions::$render->renderExpandableChild($model);
    }

    /**
     * Analyses a resource.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseResource(Simple $model)
    {
        $json = array();
        $json['type'] = 'resource';
        $data = get_resource_type($model->getData());

        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'resource')
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Render a dump for a bool value.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseBoolean(Simple $model)
    {
        $json = array();
        $json['type'] = 'boolean';
        $data = $model->getData() ? 'TRUE' : 'FALSE';

        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'boolean')
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Render a dump for a integer value.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseInteger(Simple $model)
    {
        $json = array();
        $json['type'] = 'integer';

        $model->setNormal($model->getData())
            ->setType($model->getAdditional() . 'integer')
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Render a dump for a float value.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseFloat(Simple $model)
    {
        $json = array();
        $json['type'] = 'float';

        $model->setNormal($model->getData())
            ->setType($model->getAdditional() . 'float')
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Render a dump for a string value.
     *
     * @param Simple $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public static function analyseString(Simple $model)
    {
        $json = array();
        $json['type'] = 'string';
        $data = $model->getData();

        // Extra ?
        if (strlen($data) > 50) {
            $cut = substr(Toolbox::encodeString($data), 0, 50 - 3) . '...';
        } else {
            $cut = Toolbox::encodeString($data);
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

        $data = Toolbox::encodeString($data);

        $model->setData($data)
            ->setNormal($cut)
            ->setType($model->getAdditional() . 'string' . ' ' . $strlen)
            ->setJson($json);

        return OutputActions::$render->renderSingleChild($model);
    }

    /**
     * Analyses a closure.
     *
     * @param Simple $model
     *   The closure we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public static function analyseClosure(Simple $model)
    {
        $ref = new \ReflectionFunction($model->getData());

        $result = array();

        // Adding comments from the file.
        $methodclass = new ThroughMethods();
        $result['comments'] =  $methodclass->prettifyComment($ref->getDocComment());

        // Adding the sourcecode
        $highlight = $ref->getStartLine() -1;
        $from = $highlight - 3;
        $to = $ref->getEndLine() -1;
        $file = $ref->getFileName();
        $result['source'] = Toolbox::readSourcecode($file, $highlight, $from, $to);

        // Adding the place where it was declared.
        $result['declared in'] = $ref->getFileName() . "\n";
        $result['declared in'] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (strlen($namespace) > 0) {
            $result['namespace'] = $namespace;
        }

        // Adding the parameters.
        $parameters = $ref->getParameters();
        $paramList = '';
        foreach ($parameters as $parameter) {
            preg_match('/(.*)(?= \[ )/', $parameter, $key);
            $parameter = str_replace($key[0], '', $parameter);
            $result[$key[0]] = trim($parameter, ' []');
            $paramList .= trim(str_replace(array(
                    '&lt;optional&gt;',
                    '&lt;required&gt;'
                ), array('', ''), $result[$key[0]])) . ', ';
        }
        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';
        $model->setType($model->getAdditional() . ' closure')
            ->setAdditional('. . .')
            ->setConnector2($model->getConnector2() . '(' . $paramList . ') =')
            ->addParameter('data', $result)
            ->initCallback('Iterate\ThroughMethodAnalysis');

        return OutputActions::$render->renderExpandableChild($model);

    }

    /**
     * Render a dump for an object.
     *
     * @param Simple $model
     *   The object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public static function analyseObject(Simple $model)
    {
        static $level = 0;

        $output = '';
        $level++;
        $model->setType($model->getAdditional() . 'class')
            ->addParameter('data', $model->getData())
            ->addParameter('name', $model->getName())
            ->setAdditional(get_class($model->getData()))
            ->setDomid(self::generateDomIdFromObject($model->getData()))
            ->initCallback('Analyse\Object');

        // Output data from the class.
        $output .= OutputActions::$render->renderExpandableChild($model);
        // We've finished this one, and can decrease the level setting.
        $level--;
        return $output;
    }

    /**
     * Analysis a backtrace.
     *
     * We need to format this one a little bit different than a
     * normal array.
     *
     * @param array $backtrace
     *   The backtrace.
     * @param int $offset
     *   For some reason, we have an offset of -1 for fatel error backtrace
     *   line number.
     *
     * @return string
     *   The rendered backtrace.
     */
    public static function analysisBacktrace(array &$backtrace, $offset = 0)
    {
        $output = '';

        foreach ($backtrace as $step => $stepData) {
            $model = new Simple();
            $model->setName($step)
                ->setType('Stack Frame')
                ->addParameter('stepData', $stepData)
                ->addParameter('offset', $offset)
                ->initCallback('Analyse\BacktraceStep');

            $output .= OutputActions::$render->renderExpandableChild($model);
        }

        return $output;
    }

        /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is the object hash as well as the kruXX call number, to avoid
     * collisions (even if they are unlikely).
     *
     * @param mixed $data
     *   The object from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected static function generateDomIdFromObject($data)
    {
        if (is_object($data)) {
            return 'k' . OutputActions::$KrexxCount . '_' . spl_object_hash($data);
        } else {
            // Do nothing.
            return '';
        }
    }
}
