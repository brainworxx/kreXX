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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Declaration;

use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use ReflectionClass;
use ReflectionMethod;
use Reflector;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionType;

class MethodDeclaration extends AbstractDeclaration
{
    /**
     * The allowed types from the comment.
     *
     * Because, there may be a lot of BS in the comment.
     *
     * @var string[]
     */
    protected const ALLOWED_TYPES = [
        'int',
        'string',
        'mixed',
        'void',
        'resource',
        'bool',
        'array',
        'null',
        'float',
    ];

    /**
     * Get the declaration place of this method.
     *
     * @param \ReflectionMethod $reflection
     *   Reflection of the method we are analysing.
     *
     * @return string
     *   The analysis result.
     */
    public function retrieveDeclaration(Reflector $reflection): string
    {
        $messages = $this->pool->messages;
        $reflectionClass = $reflection->getDeclaringClass();

        if ($reflectionClass->isInternal()) {
            return $messages->getHelp('metaPredeclared');
        }

        $filename = $this->pool->fileService->filterFilePath((string)$reflection->getFileName());
        if (empty($filename)) {
            // Not sure, if this is possible.
            return $this->pool->messages->getHelp('unknownDeclaration');
        }

        // If the filename of the $declaringClass and the $reflectionMethod differ,
        // we are facing a trait here.
        $secondLine = $messages->getHelp('metaInClass') . $reflection->class . "\n";
        if ($reflection->getFileName() !== $reflectionClass->getFileName()) {
            // There is no real clean way to get the name of the trait that we
            // are looking at.
            $traitName = ':: unable to get the trait name ::';
            $trait = $this->retrieveDeclaringReflection($reflection, $reflectionClass);
            if ($trait !== false) {
                $traitName = $trait->getName();
            }

            $secondLine = $messages->getHelp('metaInTrait') . $traitName . "\n";
        }

        return $filename . "\n" . $secondLine . $messages->getHelp('metaInLine') .
            $reflection->getStartLine();
    }

    /**
     * Simply ask the reflection method for it's return value.
     *
     * @param ReflectionType|null $refMethod
     *   The reflection of the method we are analysing
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    public function retrieveReturnType(?ReflectionType $returnType): string
    {
        $result = '';
        if ($returnType === null) {
            // Nothing found, early return.
            return $result;
        }

        $nullable = $returnType->allowsNull() ? '?' : '';

        // Handling the normal types.
        if ($returnType instanceof ReflectionNamedType) {
            $result = $this->formatReturnTypes($returnType);
        }

        // Union types have several types in them.
        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $namedType) {
                $result .=  $this->formatReturnTypes($namedType) . '|';
            }
            $result = trim($result, '|') . ' ';
        }

        return $nullable . $result;
    }

    /**
     * Format the names type.
     *
     * @param ReflectionNamedType $namedType
     *   The names type.
     *
     * @return string
     *   The formatted name of the type
     */
    protected function formatReturnTypes(ReflectionNamedType $namedType): string
    {
        $result = $namedType->getName();
        if (!in_array($result, static::ALLOWED_TYPES, true) && strpos($result, '\\') !== 0) {
            // Must be e un-namespaced class name.
            $result = '\\' . $result;
        }

        return $result;
    }

    /**
     * Retrieve the declaration class reflection from traits.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass $declaringClass
     *   The original declaring class, the one with the traits.
     *
     * @return bool|\ReflectionClass
     *   false = unable to retrieve something.
     *   Otherwise, return a reflection class.
     */
    protected function retrieveDeclaringReflection(ReflectionMethod $reflectionMethod, ReflectionClass $declaringClass)
    {
        // Get a first impression.
        if ($reflectionMethod->getFileName() === $declaringClass->getFileName()) {
            return $declaringClass;
        }

        // Go through the first layer of traits.
        // No need to recheck the availability for traits. This is done above.
        foreach ($declaringClass->getTraits() as $trait) {
            $result = $this->retrieveDeclaringReflection($reflectionMethod, $trait);
            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }
}
