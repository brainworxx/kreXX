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
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ProcessNullTest extends AbstractHelper
{
    /**
     * Testing the float value processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     */
    public function testProcess()
    {
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);
        $fixture = null;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessNull(Krexx::$pool);
        $this->mockEventService(
            [ProcessNull::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $processor->canHandle($model);
        $processor->handle();

        $this->assertEquals('NULL', $model->getData());
        $this->assertEquals('NULL', $model->getNormal());
    }

    /**
     * Test the check if we can handle the array processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull::canHandle
     */
    public function testCanHandle()
    {
        $processor = new ProcessNull(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = null;

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
        $fixture = 'abc';
        $this->assertFalse($processor->canHandle($model->setData($fixture)));
    }
}
