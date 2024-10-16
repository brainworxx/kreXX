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

namespace Brainworxx\Krexx\Analyse\Getter;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ByRegExProperty extends ByMethodName
{
    /**
     * Here we memorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected int $deep = 0;

    /**
     * This is the pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected Pool $pool;

    /**
     * Inject the pool,
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ) {
        $this->deep = 0;
        return parent::retrieveIt($reflectionMethod, $reflectionClass, $currentPrefix);
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * This time we are analysing the source code itself!
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection class oof the object we are analysing.
     * @param ReflectionClass $reflectionClass
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function retrieveReflectionProperty(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): ?ReflectionProperty {
        if ($reflectionMethod->isInternal()) {
            // There is no code for internal methods.
            return null;
        }
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
            // Check if this is a property and return the last we find.
            $result = $this->analyseRegexResult($propertyName, $reflectionClass, $currentPrefix);
        }

        // Nothing?
        return $result;
    }

    /**
     * Searching for stuff via regex.
     * Yay, dynamic regex stuff for fun and profit!
     *
     * @param string[] $searchArray
     *   The search definition.
     * @param string $haystack
     *   The haystack, obviously.
     *
     * @return string[]|int[]
     *   The findings.
     */
    protected function findIt(array $searchArray, string $haystack): array
    {
        $findings = [];
        preg_match_all(
            str_replace(
                ['###0###', '###1###'],
                [preg_quote($searchArray[0]), preg_quote($searchArray[1])],
                '/(?<=###0###).*?(?=###1###)/'
            ),
            $haystack,
            $findings
        );

        // Return the file name as well as stuff from the path.
        return $findings[0];
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
    protected function analyseRegexResult(
        string $propertyName,
        ReflectionClass $classReflection,
        string $currentPrefix
    ): ?ReflectionProperty {
        // Check if this is a property and return the first we find.
        $result = $this->retrievePropertyByName($propertyName, $classReflection);
        if ($result !== null) {
            return $result;
        }

        // Check if this is a method and go deeper!
        $methodName = rtrim($propertyName, '()');
        if ($classReflection->hasMethod($methodName) && ++$this->deep < 3) {
            return $this->retrieveReflectionProperty(
                $classReflection->getMethod($methodName),
                $classReflection,
                $currentPrefix
            );
        }

        return null;
    }

    /**
     * Retrieve the property by name from a reflection class.
     *
     * @param string $propertyName
     *   The name of the property.
     * @param \ReflectionClass $parentClass
     *   The class where it may be located.
     *
     * @return \ReflectionProperty|null
     *   The reflection property, if found.
     */
    protected function retrievePropertyByName(string $propertyName, \ReflectionClass $parentClass): ?ReflectionProperty
    {
        while ($parentClass !== false) {
            // Check if it was declared somewhere deeper in the
            // class structure.
            if ($parentClass->hasProperty($propertyName)) {
                return $parentClass->getProperty($propertyName);
            }
            $parentClass = $parentClass->getParentClass();
        }

        return null;
    }
}
