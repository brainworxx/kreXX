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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\EmptyInterfaceFixture;
use Brainworxx\Krexx\Tests\Fixtures\InterfaceFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Fixtures\MultitraitFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;

class MetaTest extends AbstractTest
{

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Meta::callMe::start';

    /**
     * @var string
     */
    protected $recursionEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Meta::recursion';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Meta::analysisEnd';

    /**
     * Test the recursion handling.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::generateDomIdFromClassname
     */
    public function testCallMeRecursion()
    {
        $meta = new Meta(Krexx::$pool);

        // Setup a fixture
        $ref = new ReflectionClass(SimpleFixture::class);
        $expectedDomId = 'k42_c_' . md5(SimpleFixture::class);

        // Test for the events
        $this->mockEventService(
            [$this->startEvent, $meta],
            [$this->recursionEvent, $meta]
        );

        // Test the Dom Id generation.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getKrexxCount')
            ->will($this->returnValue(42));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        // Make sure that we are testing a recursion.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with($expectedDomId)
            ->will($this->returnValue(true));
        Krexx::$pool->recursionHandler = $recursionMock;

        // Short circuit the rendering process.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $metaName = 'some name';
        $parameters = [
            $meta::PARAM_REF => $ref,
            $meta::PARAM_META_NAME => $metaName
        ];
        $meta->setParameters($parameters)->callMe();

        // Retrieve the model and test the results.
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderRecursion'][0];
        $this->assertEquals($expectedDomId, $model->getDomid());
        $this->assertEquals($metaName, $model->getName());
        $this->assertEquals($metaName, $model->getNormal());
        $this->assertEquals($meta::TYPE_INTERNALS, $model->getType());
    }

    /**
     * Test the start of the meta analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::analyseMeta
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta::generateName
     */
    public function testCallMe()
    {
        $meta = new Meta(Krexx::$pool);

        // Setup a fixture
        $ref = new ReflectionClass(ComplexMethodFixture::class);
        $expectedDomId = 'k42_c_' . md5(ComplexMethodFixture::class);

        // Test for the events
        $this->mockEventService(
            [$this->startEvent, $meta],
            [$this->endEvent, $meta]
        );

        // Test the Dom Id generation.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getKrexxCount')
            ->will($this->returnValue(42));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        // Make sure that we are not testing a recursion.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with($expectedDomId)
            ->will($this->returnValue(false));
        Krexx::$pool->recursionHandler = $recursionMock;

        // Short circuit the rendering process.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $parameters = [
            $meta::PARAM_REF => $ref,
        ];
        $meta->setParameters($parameters)->callMe();

        // Retrieve the model and test the results.
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($expectedDomId, $model->getDomid());
        $this->assertEquals($meta::META_CLASS_DATA, $model->getName());
        $this->assertEquals($meta::TYPE_INTERNALS, $model->getType());

        // Retrieve the parameters and test them.
        $data = $model->getParameters()[$meta::PARAM_DATA];
        $this->assertEquals(
            'class Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture',
            $data[$meta::META_CLASS_NAME]
        );
        $this->assertContains('Just another meaningless class comment.', $data[$meta::META_COMMENT]);
        $this->assertStringEndsWith(
            DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComplexMethodFixture.php, line 40',
            $data[$meta::META_DECLARED_IN]
        );
        $this->assertArrayHasKey(InterfaceFixture::class, $data[$meta::META_INTERFACES]);
        $this->assertArrayNotHasKey(EmptyInterfaceFixture::class, $data[$meta::META_INTERFACES]);
        $this->assertCount(1, $data[$meta::META_INTERFACES]);
        $this->assertArrayHasKey(MultitraitFixture::class, $data[$meta::META_TRAITS]);
        $this->assertCount(1, $data[$meta::META_TRAITS]);
        $this->assertArrayHasKey(MethodsFixture::class, $data[$meta::META_INHERITED_CLASS]);
        $this->assertCount(1, $data[$meta::META_INHERITED_CLASS]);
    }
}