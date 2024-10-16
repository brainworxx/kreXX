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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Getter;

use Brainworxx\Krexx\Analyse\Getter\ByMethodName;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DeepGetterFixture;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class ByMethodNameTest extends AbstractHelper
{
    /**
     * Test the retrieval of the possible getter by the method name, simple.
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::retrieveReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::prepareResult
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::preparePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::convertToSnakeCase
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::foundSomething
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::getReflectionProperty
     */
    public function testRetrieveItSimple()
    {
        $instance = new GetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getSomething'),
                'prefix' => 'get',
                'expectation' => 'something',
                'propertyName' => 'something'
            ],
            [
                // The simple ByMethodName analysis should not be able to tackle this one.
                'reflection' => $classReflection->getMethod('isGood'),
                'prefix' => 'is',
                'expectation' => null,
                'propertyName' => null
            ],
            [
                'reflection' => $classReflection->getMethod('hasValue'),
                'prefix' => 'has',
                'expectation' => false,
                'propertyName' => 'value'
            ],
            [
                 // There is no result whatsoever.
                'reflection' => $classReflection->getMethod('getProtectedStuff'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null
            ],
        ];

        $this->validateResults($fixture, $classReflection);
    }

    /**
     * Test the retrieval of the possible getter by the method name and by, deep
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::retrieveReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::prepareResult
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::preparePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::convertToSnakeCase
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::foundSomething
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByMethodName::getReflectionProperty
     */
    public function testRetieveItDeep()
    {
        $instance = new DeepGetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getMyPropertyOne'),
                'prefix' => 'get',
                'expectation' => 'one',
                'propertyName' => 'myPropertyOne'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyTwo'),
                'prefix' => 'get',
                'expectation' => 'two',
                'propertyName' => '_myPropertyTwo'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyThree'),
                'prefix' => 'get',
                'expectation' => 'three',
                'propertyName' => 'MyPropertyThree'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFour'),
                'prefix' => 'get',
                'expectation' => 'four',
                'propertyName' => '_MyPropertyFour'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFive'),
                'prefix' => 'get',
                'expectation' => 'five',
                'propertyName' => 'mypropertyfive'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySix'),
                'prefix' => 'get',
                'expectation' => 'six',
                'propertyName' => '_mypropertysix'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySeven'),
                'prefix' => 'get',
                'expectation' => 'seven',
                'propertyName' => 'my_property_seven'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyEight'),
                'prefix' => 'get',
                'expectation' => 'eight',
                'propertyName' => '_my_property_eight'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyNine'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null
            ],
            [
                'reflection' => $classReflection->getMethod('_getMyPropertyTen'),
                'prefix' => 'get',
                'expectation' => 'ten',
                'propertyName' => 'myPropertyTen'
            ],
            [
                'reflection' => $classReflection->getMethod('getMyStatic'),
                'prefix' => 'get',
                'expectation' => 'eleven',
                'propertyName' => 'myStatic'
            ],
            [
                'reflection' => $classReflection->getMethod('getNull'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => 'null'
            ],
            [
                'reflection' => $classReflection->getMethod('getAnotherGetter'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null
            ],
            [
                'reflection' => $classReflection->getMethod('getLiterallyNoting'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null
            ],
            [
                'reflection' => $classReflection->getMethod('isMyPropertyTwelve'),
                'prefix' => 'is',
                'expectation' => true,
                'propertyName' => 'myPropertyTwelve'
            ],
            [
                'reflection' => $classReflection->getMethod('hasMyPropertyThirteen'),
                'prefix' => 'has',
                'expectation' => false,
                'propertyName' => 'myPropertyThirteen'
            ],
            [
                'reflection' => $classReflection->getMethod('hasMyPropertyOne'),
                'prefix' => 'has',
                'expectation' => true,
                'propertyName' => 'myPropertyOne'
            ]
        ];
        $this->validateResults($fixture, $classReflection);
    }

    protected function validateResults(array $fixture, ReflectionClass $classReflection)
    {
        $byMethodName = new ByMethodName();

        foreach ($fixture as $items) {
            $result = $byMethodName->retrieveIt(
                $items['reflection'],
                $classReflection,
                $items['prefix']
            );

            $message = 'Check the result: ' . $items['reflection']->getName();
            $this->assertEquals($items['expectation'], $result, $message);
            if ($items['propertyName'] === null) {
                $this->assertFalse($byMethodName->foundSomething(), $message);
                $this->assertNull($byMethodName->getReflectionProperty(), $message);
            } else {
                $this->assertTrue($byMethodName->foundSomething());
                $this->assertEquals($items['propertyName'], $byMethodName->getReflectionProperty()->getName(), $message);
            }
        }
    }
}
