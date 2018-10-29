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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class TraversableTest  extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties
     */
    protected $publicProperties;

    /**
     * Create the class to test and inject the callback counter.
     *
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Create in instance of the class to test
        $this->publicProperties = new Traversable(\Krexx::$pool);

        // Inject the callback counter
        \Krexx::$pool->rewrite = [
            ThroughArray::class => CallbackCounter::class,
            ThroughLargeArray::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Test, if we do not ignore the nesting level in the emergency handler.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     */
    public function testCallMeNoMoreNesting()
    {
        $this->markTestIncomplete('Write me: '.  __METHOD__);
    }

    /**
     * Test, if the traversable analysis can handle some errors and warnings.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::getTaversableData
     */
    public function testCallMeWithErrors()
    {
        $this->markTestIncomplete('Write me: '.  __METHOD__);
    }

    /**
     * Test, if the normal array analysis is called.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::getTaversableData
     */
    public function testMeWithSmallArray()
    {
        $this->markTestIncomplete('Write me: '.  __METHOD__);
    }

    /**
     * Test if the large array analysis is called.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::getTaversableData
     */
    public function testMeWithLargeArray()
    {
        $this->markTestIncomplete('Write me: '.  __METHOD__);
    }
}
