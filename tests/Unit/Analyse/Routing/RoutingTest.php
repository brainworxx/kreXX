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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Routing::class, '__construct')]
#[CoversMethod(Routing::class, 'analysisHub')]
class RoutingTest extends AbstractHelper
{
    public const  ROUTING_MOCK_RETURN_VALUE = 'routing mock success';
    public const  IS_IN_HIVE = 'isInHive';
    public const  ADD_TO_HIVE = 'addToHive';
    public const  NO_ROUTE = 'no routing';
    public const  PROCESSOR = 'processors';

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Routing
     */
    protected $routing;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->routing = new Routing(Krexx::$pool);
        $this->mockEmergencyHandler();
    }

    /**
     * We inject mock routes, to test if they are called, and with what parameter.
     *
     * @param string $allowedRoute
     * @param Model $model
     *
     * @return string
     */
    protected function mockRouting(string $allowedRoute, Model $model)
    {
        /** @var ProcessInterface[] $processors */
        $processors = $this->retrieveValueByReflection(static::PROCESSOR, $this->routing);
        foreach ($processors as $className => $processor) {
            $mock = $this->createMock($className);
            if ($className === $allowedRoute) {
                $mock->expects($this->once())
                    ->method('handle')
                    ->willReturn(static::ROUTING_MOCK_RETURN_VALUE);
                $mock->expects($this->once())
                    ->method('canHandle')
                    ->with($model)
                    ->willReturn(true);
            } else {
                $mock->expects($this->never())
                    ->method('handle');
                $mock->expects($this->any())
                    ->method('canHandle')
                    ->willReturn(false);
            }
            $processors[$className] = $mock;
        }

        $this->setValueByReflection(static::PROCESSOR, $processors, $this->routing);
        return $this->routing->analysisHub($model);
    }

    /**
     * Test if all processors will get set, and that the routing class gets
     * set in the pool.
     */
    public function testConstruct()
    {
        /** @var ProcessInterface $processors */
        $processors = $this->retrieveValueByReflection(static::PROCESSOR, $this->routing);
        foreach ($processors as $processor) {
            $this->assertInstanceOf(ProcessInterface::class, $processor);
        }
    }

    /**
     * Simply test, if an emergency break gets respected.
     */
    public function testAnalysisHubEmergencyBreak()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = true;
        $model->setData($parameter);

        // Make sure to trigger an emergency break.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $this->assertEquals('', $this->mockRouting('no route for you', $model));
    }

    /**
     * Simple routing of a string.
     */
    public function testAnalysisHubString()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = 'some string';
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting(ProcessString::class, $model));
    }

    /**
     * We test the final calling of the ProcessOther after everything else
     * has failed.
     */
    public function testAnalysisHubOther()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Create the model.
        $model = new Model(Krexx::$pool);
        $model->setData('some string');
        $routing = new Routing(Krexx::$pool);
        $this->setValueByReflection('processors', [], $routing);
        $routing->analysisHub($model);

        $this->assertTrue(in_array(Krexx::$pool->messages->getHelp('unhandedOtherHelp'), $model->getJson()));
    }
}
