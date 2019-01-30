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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Messages;

class ConfigSectionTest extends AbstractTest
{
    /**
     * Testing if the configuration is rendered correctly.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection::callMe
     */
    public function testCallMe()
    {
        // Prepare the fixture.
        $noRender = new Model();
        $renderEditable = new Model();
        $renderNotEditable = new Model();

        $noRender->setSection('some Section')
            ->setEditable(true)
            ->setSource('some source')
            ->setType(Fallback::RENDER_TYPE_NONE)
            ->setValue('some value');

        $renderEditable->setSection('some Section')
            ->setEditable(true)
            ->setSource('some source')
            ->setType(Fallback::RENDER_TYPE_INPUT)
            ->setValue('some value');

        $renderNotEditable->setSection('some Section')
            ->setEditable(false)
            ->setSource('some source')
            ->setType(Fallback::RENDER_TYPE_INPUT)
            ->setValue('some value');

        $data = ['data' =>
            [
                'noRender' => $noRender,
                'renderEditable' => $renderEditable,
                'renderNotEditable' => $renderNotEditable
            ]
        ];

        $configSection = new ConfigSection(\Krexx::$pool);

        $configSection->setParams($data);
        // Test if start event has fired
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\ConfigSection::callMe::start', $configSection]
        );

        // Test Render Type None
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->exactly(4))
            ->method('getHelp')
            ->withConsecutive(
                ['renderEditableHelp'],
                ['renderEditableReadable'],
                ['renderNotEditableHelp'],
                ['renderNotEditableReadable']
            )
            ->will($this->returnValue('some help text'));
        \Krexx::$pool->messages = $messageMock;

        // Test if editable or not
        $renderMock = $this->createMock(AbstractRender::class);
        $renderMock->expects($this->once())
            ->method('renderSingleEditableChild')
            ->with($this->anything())
            ->will($this->returnValue('some string'));
        $renderMock->expects($this->once())
            ->method('renderSingleChild')
            ->with($this->anything())
            ->will($this->returnValue('some string'));
        \Krexx::$pool->render = $renderMock;

        // Run it!
        $configSection->callMe();
    }
}