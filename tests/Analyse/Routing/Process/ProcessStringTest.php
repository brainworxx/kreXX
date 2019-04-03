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

namespace Brainworxx\Krexx\Tests\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\FileinfoDummy;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class ProcessStringTest extends AbstractTest
{
    /**
     * Testing the setting of the pool and of the file info class.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::__construct
     */
    public function test__construct()
    {
        // Mock the class_exists method, to return always false.
        \Brainworxx\Krexx\Analyse\Routing\Process\class_exists('', true, true);
        $processor = new ProcessString(Krexx::$pool);
        $this->assertAttributeEquals(Krexx::$pool, 'pool', $processor);
        $this->assertAttributeInstanceOf(FileinfoDummy::class, 'bufferInfo', $processor);

        // Un-Mock the class_exist function.
        \Brainworxx\Krexx\Analyse\Routing\Process\class_exists('', true, false);
        $processor = new ProcessString(Krexx::$pool);
        $this->assertAttributeInstanceOf(\finfo::class, 'bufferInfo', $processor);
    }

    /**
     * Testing the string processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     */
    public function testProcess()
    {
        $this->markTestIncomplete('Write me!');

        // Mock bufferService and inject it
        // Mock encodingService and inject it.

        // Test with normal string.
        // Test with unicode string.
        // Test with large string
        // Test with a linebreak in the string
        // Test with callback
        // Test with mimetyped string
    }
}