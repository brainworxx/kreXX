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

namespace Brainworxx\Krexx\Tests\View\Output;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Output\Chunks;

/**
 * The second most important part. Here we save memory by avoiding large string
 * in memory.
 *
 * @package Brainworxx\Krexx\Tests\View\Output
 */
class ChunksTest extends AbstractTest
{
    /**
     * Test the initialization of a new chunks class.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::__construct
     */
    public function testConstruct()
    {
        $pool = Krexx::$pool;
        // Mock the configuration.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getChunkDir')
            ->will($this->returnValue('chunkdir'));
        $configMock->expects($this->once())
            ->method('getLogDir')
            ->will($this->returnValue('logdir'));
        $pool->config = $configMock;

        // Mock the microtime.
        $microtime = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'microtime');
        $microtime->expects($this->once())
            ->will($this->returnValue('0.96136800 1565016056'));

        $chunks = new Chunks($pool);
        // Setting of the pool.
        $this->assertAttributeSame($pool, 'pool', $chunks);
        // Retrieval of the directories.
        $this->assertAttributeEquals('chunkdir', 'chunkDir', $chunks);
        $this->assertAttributeEquals('logdir', 'logDir', $chunks);
        // Setting of the file stamp.
        $this->assertAttributeEquals('156501605696136800', 'fileStamp', $chunks);
        // Assigning itself to the pool.
        $this->assertSame($chunks, $pool->chunks);
    }

    /**
     * Test the adding of a small chunk string
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::chunkMe
     */
    public function testChunkMeSmall()
    {
        $chunks = new Chunks(Krexx::$pool);
        $fixture = 'very small string';

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->never())
            ->method('putFileContents');
        Krexx::$pool->fileService = $fileServiceMock;

        $this->assertEquals($fixture, $chunks->chunkMe($fixture));
    }

    /**
     * Test the adding of a large chunk string, without chunking.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::chunkMe
     */
    public function testChunkMeLargeNochunk()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setChunksAreAllowed(false);

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->never())
            ->method('putFileContents');
        Krexx::$pool->fileService = $fileServiceMock;

        $fixture = 'chunkable string';
        $fixture = str_pad($fixture, 10005, '*');

        $this->assertEquals($fixture, $chunks->chunkMe($fixture));
    }

    /**
     * Test the adding of a large chunk string.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::chunkMe
     * @covers \Brainworxx\Krexx\View\Output\Chunks::genKey
     */
    public function testChunkMeLarge()
    {
        $chunks = new Chunks(Krexx::$pool);
        $fixture = 'chunkable string';
        $fixture = str_pad($fixture, 10005, '*');
        $fileStamp = '12345';

        $this->setValueByReflection('fileStamp', $fileStamp, $chunks);
        $this->setValueByReflection('chunkDir', 'chunkDir/', $chunks);

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method('putFileContents')
            ->with(
                $this->callback(
                    function ($fileName) use ($fileStamp) {
                        $this->assertContains($fileStamp, $fileName);
                        return true;
                    }
                ),
                $this->callback(
                    function ($contents) use ($fixture) {
                        $this->assertContains($fixture, $contents);
                        return true;
                    }
                )
            );

        Krexx::$pool->fileService = $fileServiceMock;

        $this->assertContains('@@@12345_', $chunks->chunkMe($fixture));
    }
}
