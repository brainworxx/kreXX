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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Comment;

use ReflectionClass;
use ReflectionMethod;
use Reflector;

/**
 * We get the comment of a method and try to resolve the inheritdoc stuff.
 */
class Methods extends AbstractComment
{
    /**
     * The name of the method we are analysing.
     *
     * @var string
     */
    protected string $methodName;

    /**
     * Get the method comment and resolve the inheritdoc.
     *
     * Simple wrapper around the getMethodComment() to make sure
     * we only escape it once!
     *
     * @param \Reflector $reflection
     *   An already existing reflection of the method.
     * @param \ReflectionClass|null $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return string
     *   The prettified and escaped comment.
     */
    public function getComment(Reflector $reflection, ?ReflectionClass $reflectionClass = null): string
    {
        /** @var \ReflectionMethod $reflection */
        $this->methodName = $reflection->getName();

        return $this->pool->encodingService->encodeString(
            $this->getMethodComment($reflection, $reflectionClass)
        );
    }

    /**
     * Get the method comment and resolve the inheritdoc.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   An already existing reflection of the method.
     * @param \ReflectionClass $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return string
     *   The prettified comment.
     */
    protected function getMethodComment(ReflectionMethod $reflectionMethod, ReflectionClass $reflectionClass): string
    {
        // Get a first impression.
        // Check for interfaces.
        // Check for traits.
        $comment = $this->getTraitComment(
            $this->getInterfaceComment(
                $this->prettifyComment($reflectionMethod->getDocComment()),
                $reflectionClass
            ),
            $reflectionClass
        );

        // Nothing on this level, we need to take a look at the parents.
        $reflectionClass = $reflectionClass->getParentClass();
        if (
            $reflectionClass !== false
            && $reflectionClass->hasMethod($this->methodName)
        ) {
            $comment = $this->replaceInheritComment(
                $comment,
                $this->getMethodComment($reflectionClass->getMethod($this->methodName), $reflectionClass)
            );
        }

        // Tell the dev that we could not resolve the comment.
        return $this->replaceInheritComment($comment, $this->pool->messages->getHelp('commentResolvingFail'));
    }

    /**
     * Gets the comment from all added traits.
     *
     * Iterated through an array of traits, to see
     * if we can resolve the inherited comment. Traits
     * are only supported since PHP 5.4, so we need to
     * check if they are available.
     *
     * @param string $originalComment
     *   The original comment, so far.
     * @param \ReflectionClass $reflection
     *   A reflection of the object we are currently analysing.
     *
     * @return string
     *   The comment from one of the trait.
     */
    protected function getTraitComment(string $originalComment, ReflectionClass $reflection): string
    {
        // Get the traits from this class.
        // Now we should have an array with reflections of all
        // traits in the class we are currently looking at.
        foreach ($reflection->getTraits() as $trait) {
            $originalComment = $this->retrieveComment($originalComment, $trait);
            if ($this->checkComment($originalComment)) {
                // Looks like we've resolved them all.
                return $originalComment;
            }
        }

        // Return what we could resolve so far.
        return $originalComment;
    }

    /**
     * Gets the comment from all implemented interfaces.
     *
     * Iterated through an array of interfaces, to see
     * if we can resolve the inherited comment.
     *
     * @param string $originalComment
     *   The original comment, so far.
     * @param \ReflectionClass $reflectionClass
     *   A reflection of the object we are currently analysing.
     *
     * @return string
     *   The comment from one of the interfaces.
     */
    protected function getInterfaceComment(string $originalComment, ReflectionClass $reflectionClass): string
    {
        foreach ($reflectionClass->getInterfaces() as $interface) {
            $originalComment = $this->retrieveComment($originalComment, $interface);
            if ($this->checkComment($originalComment)) {
                // Looks like we've resolved them all.
                return $originalComment;
            }
        }

        // Return what we could resolve so far.
        return $originalComment;
    }

    /**
     * @param string $originalComment
     *   The comments so far.
     * @param \ReflectionClass $reflection
     *   Reflection of a class, trait or interface.
     *
     * @return string
     *   The string with the comment.
     */
    protected function retrieveComment(string $originalComment, ReflectionClass $reflection): string
    {
        if ($reflection->hasMethod($this->methodName)) {
            $newComment = $this->prettifyComment($reflection->getMethod($this->methodName)->getDocComment());
            // Replace it.
            $originalComment = $this->replaceInheritComment($originalComment, $newComment);
        }

        return $originalComment;
    }
}
