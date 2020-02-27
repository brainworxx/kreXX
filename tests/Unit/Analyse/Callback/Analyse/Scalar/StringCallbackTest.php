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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class StringCallbackTest extends AbstractTest
{
    /**
     * Test if the callback analyser can identify a callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback::canHandle
     */
    public function testCanHandle()
    {
        $stringCallback = new StringCallback(\Krexx::$pool);
        $this->assertTrue($stringCallback->canHandle('strpos'), 'This ia a predefinedphp function.');
        $this->assertFalse($stringCallback->canHandle('sdfsd dsf sdf '), 'Just a random string.');
    }

    /**
     * Test the analysis of a callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback::retrieveDeclarationPlace
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback::insertParameters
     */
    public function testCallMeNormal()
    {
        $this->mockEmergencyHandler();

        // Prepare the guinea pig.
        $stringCallback = new StringCallback(\Krexx::$pool);
        $fixture = [StringCallback::PARAM_DATA => 'myLittleCallback'];
        $stringCallback->setParameters($fixture);

        // Test the calling of the events.
        $this->mockEventService(
            [StringCallback::class . PluginConfigInterface::START_EVENT, $stringCallback],
            [StringCallback::class . '::callMe' . StringCallback::EVENT_MARKER_END, $stringCallback]
        );

        \Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $stringCallback->callMe();
        $result = CallbackCounter::$staticParameters[0][StringCallback::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);

        $this->assertStringStartsWith('Fixture for the callback analysis.', $result['Comment']);
        $this->assertContains('tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Callback.php', $result['Declared in']);
        $this->assertContains('in line: 45', $result['Declared in']);
        $this->assertEquals('string $justAString', $result['Parameter #1']);
    }

    /**
     * Test the error handling in the callMe.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\StringCallback::callMe
     */
    public function testCallMeError()
    {
        // Create a fixture that is supposed to trigger a ReflectionException.
        $stringCallback = new StringCallback(\Krexx::$pool);
        $fixture = [StringCallback::PARAM_DATA => 'dgdg dsf '];
        $stringCallback->setParameters($fixture);

        // Test the early return.
        $eventServiceMock = $this->createMock(Event::class);
        $eventServiceMock->expects($this->never())
            ->method('dispatch');
        \Krexx::$pool->eventService = $eventServiceMock;

        $stringCallback->callMe();
    }
}