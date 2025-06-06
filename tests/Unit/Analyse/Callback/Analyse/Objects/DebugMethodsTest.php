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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Debug;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DebugMethodFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(DebugMethods::class, 'callMe')]
#[CoversMethod(DebugMethods::class, 'checkIfAccessible')]
#[CoversMethod(DebugMethods::class, 'retrieveValue')]
#[CoversMethod(AbstractCallback::class, 'dispatchStartEvent')]
#[CoversMethod(AbstractCallback::class, 'dispatchEventWithModel')]
class DebugMethodsTest extends AbstractHelper
{
    /**
     * Our prepared class to test.
     *
     * @var DebugMethods
     */
    protected $debugMethods;

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        Krexx::$pool->rewrite = [
            Debug::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();

         // Setup the fixture.
        $fixtureClass = new DebugMethodFixture();
        $fixture = [
            'data' => $fixtureClass,
            'name' => 'some name,',
            'ref' => new ReflectionClass($fixtureClass)
        ];

        // Setup the class to test.
        $this->debugMethods = new DebugMethods(Krexx::$pool);
        $this->debugMethods->setParameters($fixture);
    }

    /**
     * Test if the no-go debug methods got called.
     */
    protected function assertPostConditions(): void
    {
        // The magical __Call and the parameterized method must never be called.
        /** @var DebugMethodFixture $data */
        $data =  $this->debugMethods->getParameters()['data'];
        $this->assertEquals([], $data->callMagicMethod);
        $this->assertEquals(false, $data->callWithParameter);

        parent::assertPostConditions();
    }

    /**
     * Testing the not-existing debug method, the one throwing a exception and
     * the one with the parameters. None of these must get thorough.
     */
    public function testCallMeNothing()
    {
        // Set up the start events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods::callMe::start', $this->debugMethods]
        );

        // Configure the debug methods we want to run.
        $this->setConfigValue(
            Fallback::SETTING_DEBUG_METHODS,
            'notExistingMethod,badDebugMethod,parameterizedDebugMethod'
        );

        // Run the test.
        $this->assertEquals('', $this->debugMethods->callMe());

        // Test if the callback was executed.
        $this->assertEquals(0, CallbackCounter::$counter);
        $this->assertEquals([], CallbackCounter::$staticParameters);
    }

    public function testCallMeError()
    {

        $fixtureClass = new DebugMethodFixture();
        $reflectionMock = $this->createMock(ReflectionClass::class);
        $reflectionMock->expects($this->any())
            ->method('getMethod')
            ->willThrowException(new \ReflectionException());
        $reflectionMock->expects($this->once())
            ->method('getData')
            ->willReturn($fixtureClass);

        $fixture = [
            'data' => $fixtureClass,
            'name' => 'some name,',
            'ref' => $reflectionMock
        ];
        $this->debugMethods = new DebugMethods(Krexx::$pool);
        $this->debugMethods->setParameters($fixture);

        // Configure the debug method we want to run.
        $this->setConfigValue(Fallback::SETTING_DEBUG_METHODS, 'goodDebugMethod,uglyDebugMethod');

        // Set up the start events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods::callMe::start', $this->debugMethods]
        );

        $this->assertEquals('', $this->debugMethods->callMe());
    }

    /**
     * Testing the "good" and "ugly" debug methods.
     */
    public function testCallMeNormal()
    {
        // Setup the start events
        $this->mockEventService(
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods::callMe::start',
                $this->debugMethods
            ],
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods::goodDebugMethod',
                $this->debugMethods
            ],
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\DebugMethods::uglyDebugMethod',
                $this->debugMethods
            ]
        );

        // Configure the debug method we want to run.
        $this->setConfigValue(Fallback::SETTING_DEBUG_METHODS, 'goodDebugMethod,uglyDebugMethod');

        // Run the test.
        $this->debugMethods->callMe();

        // Test if the callback was executed
        $this->assertEquals(2, CallbackCounter::$counter);
        $this->assertEquals(
            [
                ['data' => 'goodDebugMethod'],
                ['data' => 'uglyDebugMethod']
            ],
            CallbackCounter::$staticParameters
        );
    }
}
