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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Getter method analysis methods.
 *
 * @uses array normalGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'get'
 * @uses array isGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'is'
 * @uses array hasGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'has'
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection class of the object we are analysing.
 * @uses object data
 *   The object we are currently analysing
 * @uses string currentPrefix
 *   The current prefix we are analysing (get, is, has).
 *   Does not get set from the outside.
 * @uses mixed value
 *   Store the retrieved value from the getter analysis here and give
 *   event subscribers the opportunity to do something with it.
 */
class ThroughGetter extends AbstractThroughGetter
{
    /**
     * The parameter name of the prefix we ara analysing.
     *
     * @var string
     */
    public const CURRENT_PREFIX = 'currentPrefix';

    /**
     * Here we memorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected int $deep = 0;

    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        if (!empty($this->parameters[static::PARAM_NORMAL_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'get';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_NORMAL_GETTER]);
        }

        if (!empty($this->parameters[static::PARAM_IS_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'is';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_IS_GETTER]);
        }

        if (!empty($this->parameters[static::PARAM_HAS_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'has';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_HAS_GETTER]);
        }

        return $output;
    }

    /**
     * Iterating through a list of reflection methods.
     *
     * @param \ReflectionMethod[] $methodList
     *   The list of methods we are going through, consisting of \ReflectionMethod
     *
     * @return string
     *   The generated DOM.
     */
    protected function goThroughMethodList(array $methodList): string
    {
        $output = '';

        foreach ($methodList as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($this->commentAnalysis->getComment(
                $reflectionMethod,
                $this->parameters[static::PARAM_REF]
            ));

            /** @var Model $model */
            $model = $this->pool->createClass(Model::class)
                ->setName($reflectionMethod->getName())
                ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC)
                ->addToJson($this->pool->messages->getHelp('metaMethodComment'), $comments);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic()) {
                $model->setConnectorType(static::CONNECTOR_STATIC_METHOD);
            } else {
                $model->setConnectorType(static::CONNECTOR_METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue(
                $reflectionMethod,
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $model
                )
            );
        }

        return $output;
    }

    /**
     * Try to get a possible return value and render the result.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   A reflection ot the method we are analysing
     * @param Model $model
     *   The model so far.
     *
     * @return string
     *   The rendered markup.
     */
    protected function retrievePropertyValue(ReflectionMethod $reflectionMethod, Model $model): string
    {
        $this->resetParameters($reflectionMethod);
        try {
            $refProp = $this->getReflectionProperty($reflectionMethod);
        } catch (ReflectionException $e) {
            // We ignore this one.
            return '';
        }

        if ($refProp !== null) {
            $this->prepareResult($refProp, $model);
        } else {
            $this->retrieveContainerValue($reflectionMethod, $model);
        }

        $this->dispatchEventWithModel(__FUNCTION__ . '::resolving', $model);

        if ($this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND]) {
            $messages = $this->pool->messages;
            // Found nothing  :-(
            // We literally have no info. We need to tell the user.
            // We render this right away, without any routing.
            return $this->pool->render->renderExpandableChild($this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $model->setType($messages->getHelp('getterValueUnknown'))
                    ->setNormal($messages->getHelp('getterValueUnknown'))
                    ->addToJson($messages->getHelp('metaHint'), $messages->getHelp('getterUnknown'))
            ));
        }

        return $this->pool->routing->analysisHub(
            $this->dispatchEventWithModel(__FUNCTION__ . static::EVENT_MARKER_END, $model)
        );
    }

    /**
     * Doing a deep dive with using regex to parse for possible results.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection method to analyse.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     */
    protected function retrieveContainerValue(ReflectionMethod $reflectionMethod, Model $model): void
    {
        if ($reflectionMethod->isInternal()) {
            // There is no code for internal methods.
            return;
        }

        // Read the sourcecode into a string.
        $sourcecode = $this->pool->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );

        // Identify the container.
        // We are looking for something like this:
        // $this->$container['key'];
        $results = $this->findIt(['return $this->', '];'], $sourcecode);
        if (empty($results)) {
            return;
        }

        // We take the first one that we get.
        // There may others in there, but when the developer uses static
        // caching, this is where the value should be.
        $parts = explode('[', $results[0]);
        if (count($parts) !== 2) {
            return;
        }

        $result = $this->extractContainerKeyNames($parts);
        if ($result !== null) {
            $model->setData($result);
            // Give the plugins the opportunity to do something with the value, or
            // try to resolve it, if nothing was found.
            // We also add the stuff, that we were able to do so far.
            $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND] = false;
            $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_VALUE] = $result;
        }
    }

    /**
     * Extract the value from the parsed source code.
     *
     * @param array $parts
     *   The parsed source code, organised in parts.
     * @return mixed|null
     *   The extracted value. Null means that we were unable to find anything
     *   with certainty.
     */
    protected function extractContainerKeyNames(array $parts)
    {
        // There may (or may not) be gibberish in there, but it does not matter.
        $containerName = $parts[0];
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        if (!$reflectionClass->hasProperty($containerName)) {
            return null;
        }

        $key = trim($parts[1], '\'"');
        $container = $reflectionClass->retrieveValue($reflectionClass->getProperty($containerName));
        if (!isset($container[$key])) {
            return null;
        }

        return $container[$key];
    }

    /**
     * Prepare the retrieved result for output.
     *
     * @param \ReflectionProperty $refProp
     *   The reflection of the property that it may return.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     */
    protected function prepareResult(ReflectionProperty $refProp, Model $model): void
    {
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];

        // We've got ourselves a possible result.
        $value = $reflectionClass->retrieveValue($refProp);
        // If we are handling a getter, we retrieve the value itself
        // If we are handling an is'er of has'er, we return a boolean.
        if ($this->parameters[static::CURRENT_PREFIX] !== 'get' && !is_bool($value)) {
            $value = $value !== null;
        }
        $model->setData($value);

        if ($value === null) {
            // A NULL value might mean that the values does not
            // exist, until the getter computes it.
            $model->addToJson(
                $this->pool->messages->getHelp('metaHint'),
                $this->pool->messages->getHelp('getterNull')
            );
        }

        // Give the plugins the opportunity to do something with the value, or
        // try to resolve it, if nothing was found.
        // We also add the stuff, that we were able to do so far.
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND] = false;
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_VALUE] = $value;
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_REFLECTION_PROPERTY] = $refProp;
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * We try to guess the corresponding property in the class.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionProperty(
        ReflectionMethod $reflectionMethod
    ): ?ReflectionProperty {
        $reflectionClass = $this->parameters[static::PARAM_REF];
        // We may be facing different writing styles.
        // The property we want from getMyProperty() should be named myProperty,
        // but we can not rely on this.
        // Old php 4 coders sometimes add an underscore before a protected
        // property.

        // We will check:
        $names = [
            // myProperty
            $propertyName = $this->preparePropertyName($reflectionMethod),
            // _myProperty
            '_' . $propertyName,
            // MyProperty
            ucfirst($propertyName),
            // _MyProperty
            '_' . ucfirst($propertyName),
            // myproperty
            strtolower($propertyName),
            // _myproperty
            '_' . strtolower($propertyName),
            // my_property
            $this->convertToSnakeCase($propertyName),
            // _my_property
            '_' . $this->convertToSnakeCase($propertyName)
        ];

        foreach ($names as $name) {
            if ($reflectionClass->hasProperty($name)) {
                return $reflectionClass->getProperty($name);
            }
        }

        // Time to do some deep stuff. We parse the sourcecode via regex!
        return $reflectionMethod->isInternal() ? null :
            $this->getReflectionPropertyDeep($reflectionClass, $reflectionMethod);
    }

    /**
     * Get a first impression ot the possible property name for the getter.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   A reflection of the getter method we are analysing.
     *
     * @return string
     *   The first impression of the property name.
     */
    protected function preparePropertyName(ReflectionMethod $reflectionMethod): string
    {
        $currentPrefix = $this->parameters[static::CURRENT_PREFIX];

         // Get the name and remove the 'get' . . .
        $getterName = $reflectionMethod->getName();
        if (strpos($getterName, $currentPrefix) === 0) {
            return lcfirst(substr($getterName, strlen($currentPrefix)));
        }

        // . . .  or the '_get'.
        if (strpos($getterName, '_' . $currentPrefix) === 0) {
            return lcfirst(substr($getterName, strlen($currentPrefix) + 1));
        }

        // Still here?!? At least make the first letter lowercase.
        return lcfirst($getterName);
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * This time we are analysing the source code itself!
     *
     * @param ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionPropertyDeep(
        ReflectionClass $classReflection,
        ReflectionMethod $reflectionMethod
    ): ?ReflectionProperty {
        // Read the sourcecode into a string.
        $sourcecode = $this->pool->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );

        // Execute our search pattern.
        // Right now, we are trying to get to properties that way.
        // Later on, we may also try to parse deeper for stuff.
        $result = null;
        foreach ($this->findIt(['return $this->', ';'], $sourcecode) as $propertyName) {
            // Check if this is a property and return the first we find.
            $result = $this->analyseRegexResult($propertyName, $classReflection);
            if ($result !== null) {
                break;
            }
        }

        // Nothing?
        return $result;
    }

    /**
     * Analyse tone of the regex findings.
     *
     * @param string $propertyName
     *   The name of the property.
     * @param ReflectionClass $classReflection
     *   The current class reflection
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   The reflection of the property, or null if we found nothing.
     */
    protected function analyseRegexResult(string $propertyName, ReflectionClass $classReflection): ?ReflectionProperty
    {
        // Check if this is a property and return the first we find.
        $result = $this->retrievePropertyByName($propertyName, $classReflection);
        if ($result !== null) {
            return $result;
        }

        // Check if this is a method and go deeper!
        $methodName = rtrim($propertyName, '()');
        if ($classReflection->hasMethod($methodName) && ++$this->deep < 3) {
            // We need to be careful not to goo too deep, we might end up
            // in a loop.
            return $this->getReflectionProperty($classReflection->getMethod($methodName));
        }

        return null;
    }
}
