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

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use finfo;
use TypeError;

class FilePathTest extends AbstractTest
{
    /**
     * Test the assigning of the finfo class.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath::__construct
     */
    public function testConstruct()
    {
        $classExistsMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Scalar\\',
            'class_exists'
        );
        $classExistsMock->expects($this->exactly(2))
            ->willReturnCallback(function () {
                static $count = 0;

                ++$count;
                if ($count === 1) {
                    return true;
                }

                return false;
            });

        $filePath = new FilePath(\Krexx::$pool);
        $this->assertInstanceOf(finfo::class, $this->retrieveValueByReflection('bufferInfo', $filePath));

        $filePath = new FilePath(\Krexx::$pool);
        $this->assertNull($this->retrieveValueByReflection('bufferInfo', $filePath));
    }

    /**
     * Test, if we can identify a file path.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath::canHandle()
     */
    public function testCanHandle()
    {
        $filePath = new FilePath(\Krexx::$pool);
        $this->assertFalse($filePath->canHandle('just another string'), 'This file does not exist.');
        $this->assertFalse($filePath->canHandle('0'), 'Nothing in here.');

        $this->assertTrue(
            $filePath->canHandle(__FILE__),
            'This __FILE__ should exist and can therefore get handled.'
        );
    }

    /**
     * Test, if we can handle some errors.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath::canHandle()
     */
    public function testCanHandleErrors()
    {
        $isFileMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Scalar\\', 'is_file');
        $isFileMock->expects($this->once())
            ->willReturnCallback(function () {
                // Meh, the willThrowException does not allow \Error's.
                throw new TypeError();
            });

        $fixture = 'whatever';
        $filePath = new FilePath(\Krexx::$pool);
        $this->assertFalse($filePath->canHandle($fixture), 'Catching an error.');
    }

    /**
     * Test the retrieval of the filepath and the file info.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath::callMe()
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath::handle()
     */
    public function testCallMe()
    {
        \Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $mimeInfo = 'some mime info';
        $finfoMock = $this->createMock(finfo::class);
        $finfoMock->expects($this->once())
            ->method('file')
            ->will($this->returnValue($mimeInfo));
        $filePath = new FilePath(\Krexx::$pool);
        $this->setValueByReflection('bufferInfo', $finfoMock, $filePath);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [FilePath::class . PluginConfigInterface::START_EVENT, $filePath],
            [FilePath::class . '::callMe' . FilePath::EVENT_MARKER_END, $filePath]
        );

        $fixture = [
            FilePath::PARAM_DATA => __FILE__
        ];
        $filePath->setParameters($fixture)->callMe();

        $result = CallbackCounter::$staticParameters[0][FilePath::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter, 'Called once.');
        $this->assertEquals($mimeInfo, $result[FilePath::META_MIME_TYPE]);
        $this->assertEquals(__FILE__, $result['Real path']);
    }
}