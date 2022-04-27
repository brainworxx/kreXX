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

namespace Brainworxx\Krexx\Tests\Unit\Declaration;

use Brainworxx\Krexx\Analyse\Declaration\FunctionDeclaration;
use Brainworxx\Krexx\Tests\Fixtures\ReturnTypeFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Tests\Fixtures\MethodUnionParameterFixture;
use ReflectionClass;

class AbstractDeclarationTest extends AbstractTest
{
    /**
     * Test the injection of the pool
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::__construct
     */
    public function testConstruct()
    {
        $functionDeclaration = new FunctionDeclaration(\Krexx::$pool);
        $this->assertEquals(\Krexx::$pool, $this->retrieveValueByReflection('pool', $functionDeclaration));
    }

    /**
     * Testing the retrieval of the return type by reflections.
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::retrieveNamedType
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::formatNamedType
     */
    public function testRetrieveReturnType()
    {
        $fixture = new ReturnTypeFixture();
        $returnType = new MethodDeclaration(\Krexx::$pool);
        $refClass = new ReflectionClass($fixture);
        $refMethod = $refClass->getMethod('returnBool');
        $this->assertEquals('bool', $returnType->retrieveNamedType($refMethod->getReturnType()));

        // Doing PHP 8+ specific tests.
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $fixture = new MethodUnionParameterFixture();
            $refClass = new ReflectionClass($fixture);
            $refMethod = $refClass->getMethod('unionParameter');
            $this->assertEquals('array|int|bool ', $returnType->retrieveNamedType($refMethod->getReturnType()));
        }
    }
}
