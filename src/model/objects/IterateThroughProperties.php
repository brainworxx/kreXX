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

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\Help;

/**
 * Class properties analysis methods.
 *
 * @package Brainworxx\Krexx\Model\Objects
 */
class IterateThroughProperties extends Simple
{
    /**
     * Renders the properties of a class.
     *
     * @return string
     */
    public function renderMe()
    {
        // I need to preprocess them, since I do not want to render a
        // reflection property.
        $refProps = $this->parameters['refProps'];
        /* @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];
        $orgObject = $this->parameters['orgObject'];
        $output = '';
        $default = $ref->getDefaultProperties();

        foreach ($refProps as $refProperty) {
            /* @var \ReflectionProperty $refProperty */
            $refProperty->setAccessible(true);

            // Getting our values from the reflection.
            $value = $refProperty->getValue($orgObject);
            $propName = $refProperty->name;
            if (is_null($value) && $refProperty->isDefault()) {
                // We might want to look at the default value.
                $value = $default[$propName];
            }

            // Check memory and runtime.
            if (!OutputActions::checkEmergencyBreak()) {
                // No more took too long, or not enough memory is left.
                Messages::addMessage("Emergency break for large output during analysis process.");
                return '';
            }
            // Recursion tests are done in the analyseObject and
            // iterateThrough (for arrays).
            // We will not check them here.
            // Now that we have the key and the value, we can analyse it.
            // Stitch together our additional info about the data:
            // public, protected, private, static.
            $additional = '';
            $connector1 = '->';
            if ($refProperty->isPublic()) {
                $additional .= 'public ';
            }
            if ($refProperty->isPrivate()) {
                $additional .= 'private ';
            }
            if ($refProperty->isProtected()) {
                $additional .= 'protected ';
            }
            if (is_a($refProperty, '\Brainworxx\Krexx\Analysis\Flection')) {
                /* @var \Brainworxx\Krexx\Analysis\Flection $refProperty */
                $additional .= $refProperty->getWhatAmI() . ' ';
            }
            if ($refProperty->isStatic()) {
                $additional .= 'static ';
                $connector1 = '::';
                // There is always a $ in front of a static property.
                $propName = '$' . $propName;
            }

            // Object?
            // Closures are analysed separately.
            if (is_object($value) && !is_a($value, '\Closure')) {
                OutputActions::$nestingLevel++;
                if (OutputActions::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                    $model = new Simple();
                    $model->setData($value)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $result = Variables::analyseObject($model);
                    OutputActions::$nestingLevel--;
                    $output .= $result;
                } else {
                    OutputActions::$nestingLevel--;
                    $model = new Simple();
                    $text = 'Object => ' . Help::getHelp('maximumLevelReached');
                    $model->setData($text)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $output .= Variables::analyseString($model);
                }
            }

            // Closure?
            if (is_object($value) && is_a($value, '\Closure')) {
                OutputActions::$nestingLevel++;
                if (OutputActions::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                    $model = new Simple();
                    $model->setData($value)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $result = Variables::analyseClosure($model);
                    OutputActions::$nestingLevel--;
                    $output .= $result;
                } else {
                    OutputActions::$nestingLevel--;
                    $model = new Simple();
                    $text = 'Closure => ' . Help::getHelp('maximumLevelReached');
                    $model->setData($text)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $output .= Variables::analyseString($model);
                }
            }

            // Array?
            if (is_array($value)) {
                OutputActions::$nestingLevel++;
                if (OutputActions::$nestingLevel <= (int)Config::getConfigValue('runtime', 'level')) {
                    $model = new Simple();
                    $model->setData($value)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $result = Variables::analyseArray($model);
                    OutputActions::$nestingLevel--;
                    $output .= $result;
                } else {
                    OutputActions::$nestingLevel--;
                    $model = new Simple();
                    $text = 'Array => ' . Help::getHelp('maximumLevelReached');
                    $model->setData($text)
                        ->setName($propName)
                        ->setAdditional($additional)
                        ->setConnector1($connector1);
                    $output .= Variables::analyseString($model);
                }
            }

            // Resource?
            if (is_resource($value)) {
                $model = new Simple();
                $model->setData($value)
                    ->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseResource($model);
            }

            // String?
            if (is_string($value)) {
                $model = new Simple();
                $model->setData($value)
                    ->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseString($model);
            }

            // Float?
            if (is_float($value)) {
                $model = new Simple();
                $model->setData($value)
                    ->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseFloat($model);
            }

            // Integer?
            if (is_int($value)) {
                $model = new Simple();
                $model->setData($value)
                    ->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseInteger($model);
            }

            // Boolean?
            if (is_bool($value)) {
                $model = new Simple();
                $model->setData($value)
                    ->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseBoolean($model);
            }

            // Null ?
            if (is_null($value)) {
                $model = new Simple();
                $model->setName($propName)
                    ->setAdditional($additional)
                    ->setConnector1($connector1);
                $output .= Variables::analyseNull($model);
            }
        }

        return $output;
    }
}
