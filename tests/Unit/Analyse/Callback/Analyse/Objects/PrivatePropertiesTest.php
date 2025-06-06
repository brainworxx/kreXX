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

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PrivateProperties;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Fixtures\PrivateFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use ReflectionProperty;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(PrivateProperties::class, 'callMe')]
#[CoversMethod(AbstractObjectAnalysis::class, 'getReflectionPropertiesData')]
#[CoversMethod(AbstractObjectAnalysis::class, 'reflectionSorting')]
class PrivatePropertiesTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PrivateProperties
     */
    protected $privateProperties;

    /**
     * Create the class to test and inject the callback counter.
     *
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create in instance of the class to test
        $this->privateProperties = new PrivateProperties(Krexx::$pool);

        // Inject the callback counter
        Krexx::$pool->rewrite = [
            ThroughProperties::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Test the private property analysis, without any private ones in the
     * fixture.
     */
    public function testCallMeNoPrivates()
    {
        // Test start event
        // Set up the events
        $this->mockEventService(
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PrivateProperties::callMe::start',
                $this->privateProperties
            ]
        );

        // Fixture without any private properties.
        $data = new MethodsFixture();
        $fixture = [
            'data' => $data,
            'name' => 'some name',
            'ref' => new ReflectionClass($data)
        ];

        // Run the test.
        $this->privateProperties
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test, if the private analysis gets all privates, including the
     * "inherited"  ones.
     */
    public function testCallMeWithPrivates()
    {
        // Set up the events
        $this->mockEventService(
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PrivateProperties::callMe::start',
                $this->privateProperties
            ],
            [
                'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PrivateProperties::analysisEnd',
                $this->privateProperties
            ]
        );

        // Create a fixture with several private properties with inheritance.
        $data = new PrivateFixture();
        $fixture = [
            'data' => $data,
            'name' => 'some name',
            'ref' => new ReflectionClass($data)
        ];

         // Run the test.
        $this->privateProperties
            ->setParameters($fixture)
            ->callMe();

        // Check if called
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check if parameters are set.
        $params = CallbackCounter::$staticParameters[0];
        $this->assertEquals($fixture['ref'], $params['ref']);

        // Create the expectations.
        $expectations = [
            new ReflectionProperty(PrivateFixture::class, 'myProperty'),
            new ReflectionProperty(PrivateFixture::class, 'nullProperty'),
            new ReflectionProperty(PrivateFixture::class, 'value5'),
            new ReflectionProperty(SimpleFixture::class, 'value5'),
        ];

        $this->assertEquals($expectations, $params['data']);
    }
}
