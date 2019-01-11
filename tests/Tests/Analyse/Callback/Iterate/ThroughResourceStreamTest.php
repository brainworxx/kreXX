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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResourceStream;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class ThroughResourceStreamTest extends AbstractTest
{

    /**
     * Testing the analysis of a resource stream.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResourceStream::callMe
     */
    public function testCallMe()
    {
        $throughResourceStream = new ThroughResourceStream(\Krexx::$pool);
        // Test start event.
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughResourceStream::callMe::start', $throughResourceStream]
        );

        // Inject the nothing route
        $routeNothing = new RoutingNothing(\Krexx::$pool);
        \Krexx::$pool->routing = $routeNothing;
        $this->mockEmergencyHandler();

        // Create a fixture
        $myStream = fopen('https://www.google.com/', 'r');
        $fixture = [
            ThroughResourceStream::PARAM_DATA => $myStream
        ];

        // Run the test.

        $throughResourceStream->setParams($fixture)
            ->callMe();
        fclose($myStream);

        // The result comes directly from the stream_get_meta_data()
        // Not much to test here, exept to check runtime stuff.
        $this->assertTrue(count($routeNothing->model) > 9);
    }
}
