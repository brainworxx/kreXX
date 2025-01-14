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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\TimerController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(TimerController::class, 'timerEndAction')]
#[CoversMethod(TimerController::class, 'miniBenchTo')]
#[CoversMethod(TimerController::class, 'timerAction')]
#[CoversMethod(TimerController::class, '__construct')]
class TimerControllerTest extends AbstractController
{
    public const  COUNTER_CACHE = 'counterCache';
    public const  TIME_KEEPING = 'timekeeping';
    /**
     * @var TimerController
     */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a clean timer controller.
        $this->controller = new TimerController(Krexx::$pool);
        $microtime = $this->getFunctionMock('\\Brainworxx\\Krexx\\Controller\\', 'microtime');
        $microtime->expects($this->any())
            ->willReturn(3000);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the timekeeping stuff.
        $this->setValueByReflection(static::COUNTER_CACHE, [], $this->controller);
        $this->setValueByReflection(static::TIME_KEEPING, [], $this->controller);
    }

    /**
     * Testing the setting of the pool
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->controller));

        $exception = false;
        // The __construct from the abstract controller must not be called.
        try {
            $this->assertEquals(null, $this->retrieveValueByReflection('callerFinder', $this->controller));
        } catch (\Throwable $exception) {
            $exception = true;
        }
        $this->assertTrue($exception, 'The __construct from the abstract controller must not be called.');
    }

    /**
     * Testing the timer action.
     */
    public function testTimerAction()
    {
        $first = 'first';
        $second = 'second';

        // Adding a first entry.
        $this->controller->timerAction($first);
        $this->assertEquals([$first => 1], $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller));
        $this->assertArrayHasKey($first, $this->retrieveValueByReflection(static::TIME_KEEPING, $this->controller));

        // Adding a second entry.
        $this->controller->timerAction($second);
        $this->assertEquals(
            [$first => 1, $second => 1],
            $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller)
        );
        $this->assertArrayHasKey($first, $this->retrieveValueByReflection(static::TIME_KEEPING, $this->controller));
        $this->assertArrayHasKey($second, $this->retrieveValueByReflection(static::TIME_KEEPING, $this->controller));

        // Adding the first entry again.
        $this->controller->timerAction($first);
        $this->assertEquals(
            [$first => 2, $second => 1],
            $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller)
        );
        $this->assertArrayHasKey('[2]' . $first, $this->retrieveValueByReflection(static::TIME_KEEPING, $this->controller));
        $this->assertArrayHasKey($second, $this->retrieveValueByReflection(static::TIME_KEEPING, $this->controller));
    }

    /**
     * Testing the output of the timer.
     */
    public function testTimerEndAction()
    {
        $dumpMock = $this->createMock(DumpController::class);
        $dumpMock->expects($this->once())
            ->method('dumpAction')
            ->willReturnCallback(
                function ($bench, $headline) {
                    $this->assertEquals('kreXX timer', $headline);
                    $this->assertEquals(
                        [
                            'Total time' => '2000000ms',
                            'first->second' => '50%',
                            'second->End' => '50%'
                        ],
                        $bench
                    );
                    return new DumpController(new Pool());
                }
            );

        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('createClass')
            ->with(DumpController::class)
            ->willReturn($dumpMock);
        $poolMock->messages = \Krexx::$pool->messages;

        $this->setValueByReflection('pool', $poolMock, $this->controller);
        $this->setValueByReflection(static::TIME_KEEPING, ['first' => 1000, 'second' => 2000], $this->controller);
        $this->controller->timerEndAction();
    }
}
