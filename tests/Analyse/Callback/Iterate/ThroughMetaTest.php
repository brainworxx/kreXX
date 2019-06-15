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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ThroughMetaTest extends AbstractTest
{
    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMeta::callMe::start';

    /**
     * @var string
     */
    protected $noneRefEevent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMeta::handleNoneReflections';

    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta
     */
    protected $throughMeta;

    /**
     * @var RenderNothing
     */
    protected $renderNothing;

    public function setUp()
    {
        parent::setUp();

        $this->throughMeta = new ThroughMeta(Krexx::$pool);
        $this->renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $this->renderNothing;
    }

    /**
     * Test the meta iteration with a simple array and an unspecific key.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta::handleNoneReflections
     */
    public function testCallMeArray()
    {
        $key = 'some array';
        $this->mockEventService(
            [$this->startEvent, $this->throughMeta],
            [$this->noneRefEevent . $key . $this->throughMeta::EVENT_MARKER_END, $this->throughMeta]
        );

        $array = ['empty array whatever'];
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                $key => $array
            ]
        ];
        $this->throughMeta->setParams($fixture)->callMe();

        $this->assertCount(1, $this->renderNothing->model['renderExpandableChild']);
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $this->renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($key, $model->getName());
        $this->assertEquals($this->throughMeta::TYPE_REFLECTION, $model->getType());
        $parameters = $model->getParameters();
        $this->assertEquals($array, $parameters[$this->throughMeta::PARAM_DATA]);
    }

    /**
     * Test with a comment string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta::handleNoneReflections
     */
    public function testCallMeComment()
    {
        $key = $this->throughMeta::META_COMMENT;
        $this->mockEventService(
            [$this->startEvent, $this->throughMeta],
            [$this->noneRefEevent . $key . $this->throughMeta::EVENT_MARKER_END, $this->throughMeta]
        );

        $comment = 'Look at me, I\'m a comment!';
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                $key => $comment
            ]
        ];
        $this->throughMeta->setParams($fixture)->callMe();

        $this->assertCount(1, $this->renderNothing->model['renderSingleChild']);
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $this->renderNothing->model['renderSingleChild'][0];
        $this->assertEquals($comment, $model->getData());
        $this->assertEquals($key, $model->getName());
        $this->assertEquals($this->throughMeta::TYPE_REFLECTION, $model->getType());
        $this->assertEquals($this->throughMeta::UNKNOWN_VALUE, $model->getNormal());
        $this->assertTrue($model->getHasExtra());
    }

    public function testCallMeDeclaredIn()
    {
        $this->markTestIncomplete('Write me!');
    }

    public function testCallMeSource()
    {
        $this->markTestIncomplete('Write me!');
    }

    public function testCallMeInterfaces()
    {
        $this->markTestIncomplete('Write me!');
    }

    public function testCallMeTraits()
    {
        $this->markTestIncomplete('Write me!');
    }

    public function testCallMeInherited()
    {
        $this->markTestIncomplete('Write me!');
    }
}
