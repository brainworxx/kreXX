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

namespace Brainworxx\Krexx\Tests\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Misc\Cleanup;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Output\Chunks;

class CleanupTest extends AbstractTest
{
    protected $cleanup;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanup = new Cleanup(Krexx::$pool);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->setValueByReflection('chunksDone', false, $this->cleanup);
    }

    /**
     * Test the setting of the pool
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Cleanup::__construct
     */
    public function testConstruct()
    {

        $this->assertAttributeSame(Krexx::$pool, 'pool', $this->cleanup);
    }

    /**
     * Test the cleanup of log folders, when logging is not allowed.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Cleanup::cleanupOldLogs
     */
    public function testCleanupOldLogsNoLogging()
    {
        // Logging is not allowed.
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('getLoggingIsAllowed')
            ->will($this->returnValue(false));
        Krexx::$pool->chunks = $chunksMock;

        // The log directory will not get globbed.
        $glob = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'glob');
        $glob->expects($this->never());

        $this->cleanup->cleanupOldLogs();
    }

    /**
     * Test the cleanup of the log folder, when it is empty.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Cleanup::cleanupOldLogs
     */
    public function testCleanupOldLogsNoLogs()
    {
        $logDir = 'some dir';

        // Logging is allowed.
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('getLoggingIsAllowed')
            ->will($this->returnValue(true));
        Krexx::$pool->chunks = $chunksMock;

        // No logs stored.
        $glob = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'glob');
        $glob->expects($this->once())
            ->with($logDir . '*.Krexx.html')
            ->will($this->returnValue([]));

        // Nothing to sort, because of an early return.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('getSetting');
        $configMock->expects($this->once())
            ->method('getLogDir')
            ->will($this->returnValue($logDir));
        Krexx::$pool->config = $configMock;

        $this->cleanup->cleanupOldLogs();
    }

    /**
     * Test the cleanup of old logfiles, with mocked up files.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Cleanup::cleanupOldLogs
     */
    public function testCleanupOldLogsNormal()
    {
        $this->markTestIncomplete('Write me!');
    }
}
