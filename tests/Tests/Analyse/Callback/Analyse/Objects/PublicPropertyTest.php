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

namespace Tests\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class PublicPropertyTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ProtectedProperties
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
        $this->protectedProperties = new PublicProperties(\Krexx::$pool);

        // Inject the callback counter
        \Krexx::$pool->rewrite = [
            ThroughProperties::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Test the public property asnalysis, without any public ones in the
     * fixture
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe
     */
    public function testCallMeNoPublic()
    {

        $this->markTestIncomplete('Write me: ' . __FUNCTION__);
    }

    /**
     * Test the public property analysis, with public ones in the fixture.
     * We also add some undeclared ones to the mix.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe
     */
    public function testCallMeWithPublic()
    {
        $this->markTestIncomplete('Write me: ' . __FUNCTION__);
    }

    /**
     * Test the public property analysis, with public properties that are
     * unsettet before the analysis.
     */
    public function testCallMeWithUnsettedProperties()
    {
        $this->markTestIncomplete('Write me: ' . __FUNCTION__);
    }
}
