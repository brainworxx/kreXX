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
use Brainworxx\Krexx\Service\Misc\Encoding;
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

    /**
     * Test the sending of the output to the browser.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::sendDechunkedToBrowser
     * @covers \Brainworxx\Krexx\View\Output\Chunks::dechunkMe
     */
    public function testSendDechunkedToBrowser()
    {
        // This one is a little bit tricky, because we need to simulate a few
        // chunk files with pointers in them.
        $chunkDir = 'some dir';
        $fileEnding = '.Krexx.tmp';
        $startChunk = 'Murry @@@1234@@@';
        $chunk1Content = 'had @@@1235@@@';
        $chunk1File = $chunkDir . '1234' . $fileEnding;
        $chunk2Content = 'a @@@1236@@@ lampp';
        $chunk2File = $chunkDir . '1235' . $fileEnding;
        $chunk3Content = 'little';
        $chunk3File = $chunkDir . '1236' . $fileEnding;
        $expected = 'Murry had a little lampp';

        // Simulate the actual files.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            )
            ->will(
                $this->returnValueMap(
                    [
                        [$chunk1File, true, $chunk1Content],
                        [$chunk2File, true, $chunk2Content],
                        [$chunk3File, true, $chunk3Content],
                    ]
                )
            );
        $fileServiceMock->expects($this->exactly(3))
            ->method('deleteFile')
            ->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            );
        Krexx::$pool->fileService = $fileServiceMock;

        // Prevent any flushing, so that unit tests can intercept the output.
        $obFlushMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output', 'ob_flush');
        $obFlushMock->expects($this->exactly(4));
        $flushMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output', 'flush');
        $flushMock->expects($this->exactly(4));

        // Create the chunks class and set the simulated chunks directory.
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('chunkDir', $chunkDir, $chunks);

        // Run the actual test.
        $this->expectOutputString($expected);
        $chunks->sendDechunkedToBrowser($startChunk);
    }

    /**
     * Test the sending of the output to a logfile.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::saveDechunkedToFile
     * @covers \Brainworxx\Krexx\View\Output\Chunks::dechunkMe
     */
    public function testSaveDechunkedToFile()
    {
        // The tings you do to test your code . . .
        $chunkDir = 'some dir';
        $logDir = 'another dear';
        $fileStamp = 'mauritius';
        $fileEnding = '.Krexx.tmp';
        $startChunk = 'Murry @@@1234@@@';
        $chunk1Content = 'had @@@1235@@@';
        $chunk1File = $chunkDir . '1234' . $fileEnding;
        $chunk2Content = 'a @@@1236@@@ lampp';
        $chunk2File = $chunkDir . '1235' . $fileEnding;
        $chunk3Content = 'little';
        $chunk3File = $chunkDir . '1236' . $fileEnding;
        $logFileName = $logDir . $fileStamp . '.Krexx.html';
        $metaFileName = $logFileName . '.json';
        $metaData = new \StdClass();
        $metaData->whatever = 'some data';

        // Simulate the actual files.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            )
            ->will(
                $this->returnValueMap(
                    [
                        [$chunk1File, true, $chunk1Content],
                        [$chunk2File, true, $chunk2Content],
                        [$chunk3File, true, $chunk3Content],
                    ]
                )
            );
        $fileServiceMock->expects($this->exactly(4))
            ->method('deleteFile')
            ->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File],
                [$metaFileName]
            );
        $fileServiceMock->expects($this->exactly(5))
            ->method('putFileContents')
            ->withConsecutive(
                [$logFileName, 'Murry '],
                [$logFileName, 'had '],
                [$logFileName, 'a '],
                [$logFileName, 'little lampp'],
                [$metaFileName, json_encode($metaData)]
            );
        Krexx::$pool->fileService = $fileServiceMock;

        // Create the chunks class and set the simulated chunks directory.
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('chunkDir', $chunkDir, $chunks);
        $this->setValueByReflection('logDir', $logDir, $chunks);
        $this->setValueByReflection('fileStamp', $fileStamp, $chunks);
        $this->setValueByReflection('metadata', $metaData, $chunks);

        // Run the actual test.
        $chunks->saveDechunkedToFile($startChunk);

        // And now with forbidden logging.
        $this->setValueByReflection('loggingIsAllowed', false, $chunks);
        $chunks->saveDechunkedToFile($startChunk);
    }

    /**
     * Test the setter for chunk allowance. Pun intended.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::setChunksAreAllowed
     */
    public function testSetChunksAreAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setChunksAreAllowed(true);
        $this->assertAttributeEquals(true, 'chunksAreAllowed', $chunks);

        $chunks->setChunksAreAllowed(false);
        $this->assertAttributeEquals(false, 'chunksAreAllowed', $chunks);
    }

    /**
     * Test the getter for chunk allowance. Pun intended.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::getChunksAreAllowed
     */
    public function testGetChunksAreAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('chunksAreAllowed', true, $chunks);
        $this->assertTrue($chunks->getChunksAreAllowed());

        $this->setValueByReflection('chunksAreAllowed', false, $chunks);
        $this->assertFalse($chunks->getChunksAreAllowed());
    }

    /**
     * Test the setter fpr the logging allowance. The puns are killing me.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::setLoggingIsAllowed
     */
    public function testSetLoggingIsAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setLoggingIsAllowed(true);
        $this->assertAttributeEquals(true, 'loggingIsAllowed', $chunks);

        $chunks->setLoggingIsAllowed(false);
        $this->assertAttributeEquals(false, 'loggingIsAllowed', $chunks);
    }

    /**
     * Test the getter fpr the logging is allowed. No pun,see?
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::getLoggingIsAllowed
     */
    public function testGetLoggingIsAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('loggingIsAllowed', true, $chunks);
        $this->assertTrue($chunks->getLoggingIsAllowed());

        $this->setValueByReflection('loggingIsAllowed', false, $chunks);
        $this->assertFalse($chunks->getLoggingIsAllowed());
    }

    /**
     * Test the adding of meta data.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::addMetadata
     */
    public function testAddMetaData()
    {
        $metadata = ['some meta stuff'];
        $chunks = new Chunks(Krexx::$pool);
        $chunks->addMetadata($metadata);
        $this->assertAttributeEquals([$metadata], 'metadata', $chunks);
    }

    /**
     * Test cleanup of all currently used chunkfiles, in case there was
     * something left. Actually, this should not happen.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::__destruct
     */
    public function testDestruct()
    {
        $fileList = [
          'file 1',
          'file 2',
          'filofax'
        ];
        $chunkDir = 'chunk dir';
        $fileStamp = 'stampede';

        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('chunkDir', $chunkDir, $chunks);
        $this->setValueByReflection('fileStamp', $fileStamp, $chunks);

        $globMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'glob');
        $globMock->expects($this->once())
            ->with($chunkDir . $fileStamp . '_*')
            ->will($this->returnValue($fileList));

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(count($fileList)))
            ->method('deleteFile')
            ->withConsecutive(
                [$fileList[0]],
                [$fileList[1]],
                [$fileList[2]]
            );
        Krexx::$pool->fileService = $fileServiceMock;

        // We need to call this by hand, because it's a singleton, with references
        // at who-knows-where.
        $chunks->__destruct();
    }

    /**
     * Test the encoding detection.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::detectEncoding
     */
    public function testDetectEncoding()
    {
        $chunks = new Chunks(Krexx::$pool);

        $specialEncoding = 'special stuff';
        $string = 'string';
        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
           ->method('mbDetectEncoding')
           ->will($this->returnValue($specialEncoding));
        Krexx::$pool->encodingService = $encodingMock;
        $chunks->detectEncoding($string);
        $this->assertAttributeEquals($specialEncoding, 'officialEncoding', $chunks);

        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
           ->method('mbDetectEncoding')
           ->will($this->returnValue(false));
        Krexx::$pool->encodingService = $encodingMock;
        $chunks->detectEncoding($string);
        $this->assertAttributeEquals($specialEncoding, 'officialEncoding', $chunks);
    }

    /**
     * Test the getter for the official encoding.
     *
     * @covers \Brainworxx\Krexx\View\Output\Chunks::getOfficialEncoding
     */
    public function testGetOfficialEncoding()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('officialEncoding', 'whatever', $chunks);

        $this->assertEquals('whatever', $chunks->getOfficialEncoding());
    }
}
