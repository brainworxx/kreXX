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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\FilePath;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use finfo;
use Krexx;
use TypeError;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FilePath::class, '__construct')]
#[CoversMethod(FilePath::class, 'isActive')]
#[CoversMethod(FilePath::class, 'canHandle')]
#[CoversMethod(FilePath::class, 'retrieveFileInfo')]
#[CoversMethod(FilePath::class, 'callMe')]
#[CoversMethod(FilePath::class, 'handle')]
class FilePathTest extends AbstractHelper
{
    /**
     * Test the assigning of the finfo class.
     */
    public function testConstruct()
    {
        $filePath = new FilePath(Krexx::$pool);
        $this->assertInstanceOf(finfo::class, $this->retrieveValueByReflection('bufferInfo', $filePath));
    }

    /**
     * Test the recognition of the finfo class in the system.
     */
    public function testIsActive()
    {
        $classExistsMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Scalar\\String\\',
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

        $this->assertTrue(FilePath::isActive());
        $this->assertFalse(FilePath::isActive());
    }

    /**
     * Test, if we can identify a file path.
     */
    public function testCanHandle()
    {
        $filePath = new FilePath(Krexx::$pool);
        $this->assertFalse($filePath->canHandle(
            'just another string',
            new Model(Krexx::$pool)),
            'This file does not exist.'
        );
        $this->assertFalse($filePath->canHandle('0', new Model(Krexx::$pool)), 'Nothing in here.');

        $mimeInfo = 'some mime info';
        $finfoMock = $this->createMock(finfo::class);
        $finfoMock->expects($this->once())
            ->method('file')
            ->willReturn($mimeInfo);
        $filePath = new FilePath(Krexx::$pool);
        $this->setValueByReflection('bufferInfo', $finfoMock, $filePath);

        $this->mockEmergencyHandler();
        $model = new Model(Krexx::$pool);
        $this->assertFalse(
            $filePath->canHandle(__FILE__, $model),
            'Always false. We add the stuff directly to the model.'
        );

        $result = $model->getJson();
        $this->assertEquals($mimeInfo, $result['Mimetype file'], 'Mime info was added');
        $this->assertArrayNotHasKey(
            'Real path',
            $result,
            'No real path available, because it is the same as the __FILE__'
        );

        // We do this a second time, to test the internal caching.
        $model = new Model(Krexx::$pool);
        $filePath->canHandle(__FILE__, $model);
        $result = $model->getJson();
        $this->assertEquals(
            $mimeInfo,
            $result['Mimetype file'],
            'Mime info was added, but the mock above was only called once.'
        );
    }

    /**
     * Test the adding of the realpath, if it differs.
     */
    public function testCanHandleRealpath()
    {
        $filePath = new FilePath(Krexx::$pool);
        $realpathMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Scalar\\String',
            'realpath'
        );
        $myPath = __DIR__ . '/XmlTest.php';
        $realpathMock->expects($this->once())
            ->with($myPath)
            ->willReturn('theRealPath');
        $model = new Model(Krexx::$pool);
        $filePath->canHandle($myPath, $model);
        $result = $model->getJson();
        $this->assertEquals(
            'theRealPath',
            $result['Real path'],
            'The real path was added.'
        );
    }

    /**
     * Test, if we can handle some errors.
     */
    public function testCanHandleErrors()
    {
        $isFileMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Scalar\\String\\', 'is_file');
        $isFileMock->expects($this->once())
            ->willReturnCallback(function () {
                // Meh, the willThrowException does not allow \Error's.
                throw new TypeError();
            });

        $fixture = 'whateveribu bibuibuivuvuvzppviz';
        $filePath = new FilePath(Krexx::$pool);
        $this->assertFalse($filePath->canHandle($fixture, new Model(Krexx::$pool)), 'Catching an error.');
    }

    /**
     * We literally expect it to do nothing.
     */
    public function testCallMe()
    {
        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $filePath = new FilePath(Krexx::$pool);
        $filePath->callMe();

        $this->assertArrayNotHasKey(0, CallbackCounter::$staticParameters);
    }
}
