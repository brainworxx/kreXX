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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ProcessInteger::class, 'handle')]
#[CoversMethod(AbstractRouting::class, 'dispatchProcessEvent')]
#[CoversMethod(ProcessInteger::class, 'canHandle')]
class ProcessIntegerTest extends AbstractHelper
{
    /**
     * Testing the integer value processing.
     */
    public function testProcessNormal()
    {
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);
        $fixture = 42;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessInteger(Krexx::$pool);
        $this->mockEventService(
            [ProcessInteger::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $processor->canHandle($model);
        $processor->handle();

        $this->assertEquals($fixture, $model->getData());
        $this->assertEquals($fixture, $model->getNormal());
        $this->assertCount(0, $model->getJson(), 'No other info in this one.');
    }

    /**
     * Testing the integer value with timestamp processing.
     */
    public function testProcessWithTimestamp()
    {
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);
        $fixture = 1583926619;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessInteger(Krexx::$pool);
        $processor->canHandle($model);
        $processor->handle();

        $this->assertStringStartsWith(
            '11.Mar 2020',
            $model->getJson()['Timestamp'],
            'Looking for the timestamp.'
        );
    }

    /**
     * Test the check if we can handle the array processing.
     */
    public function testCanHandle()
    {
        $processor = new ProcessInteger(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = 1234;

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
        $fixture = 'abc';
        $this->assertFalse($processor->canHandle($model->setData($fixture)));
    }
}
