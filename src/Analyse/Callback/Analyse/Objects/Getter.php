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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionMethod;

/**
 * Analysis of all getter methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses object data
 *   The class we are currently analysing.
 * @uses string name
 *   The name of the object we are analysing.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Getter extends AbstractObjectAnalysis
{

    /**
     * List of the getter methods, that start with 'get'.
     *
     * @var array
     */
    protected $normalGetter = [];

    /**
     * List of hte boolean getter method, that start with 'is'.
     *
     * @var array
     */
    protected $isGetter = [];

    /**
     * List of hte boolean getter method, that start with 'has'.
     *
     * @var array
     */
    protected $hasGetter = [];

    /**
     * Dump the possible result of all getter methods
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        // Get all public methods.
        $this->retrieveMethodList($ref);
        if (empty($this->normalGetter + $this->isGetter + $this->hasGetter) === true) {
            // There are no getter methods in here.
            return $output;
        }

        return $output . $this->pool->render->renderExpandableChild(
            $this->dispatchEventWithModel(
                static::EVENT_MARKER_ANALYSES_END,
                $this->pool->createClass(Model::class)
                    ->setName('Getter')
                    ->setType(static::TYPE_INTERNALS)
                    ->setHelpid('getterHelpInfo')
                    ->addParameter(static::PARAM_REF, $ref)
                    ->addParameter(static::PARAM_NORMAL_GETTER, $this->normalGetter)
                    ->addParameter(static::PARAM_IS_GETTER, $this->isGetter)
                    ->addParameter(static::PARAM_HAS_GETTER, $this->hasGetter)
                    ->injectCallback($this->pool->createClass(ThroughGetter::class))
            )
        );
    }

    /**
     * Filter and then populate the method list. We only dump those that have no
     * parameters and start with has, is or get.
     *
     * @param ReflectionMethod $method
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     */
    protected function populateGetterLists(ReflectionMethod $method, ReflectionClass $ref)
    {
        // Check, if the method is really available, inside the analysis
        // context. A inherited private method can not be called inside the
        // $this context.
        if (($method->isPrivate() === true && $method->getDeclaringClass()->getName() !== $ref->getName()) ||
            empty($method->getParameters()) === false
        ) {
            // We skip this one. Either its an out-of-scope private getter,
            // or it has parameters.
            return;
        }

        if (strpos($method->getName(), 'get') === 0) {
            $this->normalGetter[] = $method;
        } elseif (strpos($method->getName(), 'is') === 0) {
            $this->isGetter[] = $method;
        } elseif (strpos($method->getName(), 'has') === 0) {
            $this->hasGetter[] = $method;
        }
    }

    /**
     * Retrieve the possible getter methos list from the class reflection.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     *   The reflection of the class we are analysing.
     */
    protected function retrieveMethodList(ReflectionClass $ref)
    {
        // Get all public methods.
        $methodList = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        if ($this->pool->scope->isInScope() === true) {
            // Looks like we also need the protected and private methods.
            $methodList = array_merge(
                $methodList,
                $ref->getMethods(ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED)
            );
        }

        if (empty($methodList)) {
            return;
        }

        /** @var \ReflectionMethod $method */
        foreach ($methodList as $method) {
            $this->populateGetterLists($method, $ref);
        }
    }
}
