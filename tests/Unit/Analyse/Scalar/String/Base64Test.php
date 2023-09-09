<?php

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\Base64;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class Base64Test extends AbstractTest
{
    /**
     * Its always active
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::isActive
     */
    public function testIsActive()
    {
        $this->assertTrue(Base64::isActive());
    }

    /**
     * Test the handling of a normal string and of a base64 string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::canHandle
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::handle
     */
    public function testCanHandle()
    {
        $base64 = new Base64(Krexx::$pool);

        $fixture = 'some string';
        $this->assertFalse($base64->canHandle($fixture, new Model(Krexx::$pool)), 'Plain string.');

        $fixture = base64_encode('Creating an "excessive" long base 64 string.');
        $this->assertTrue($base64->canHandle($fixture, new Model(Krexx::$pool)), 'Long base64 string.');
    }

    /**
     * Test the handling of the json.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::handle
     */
    public function testHandle()
    {
        $base64 = new Base64(Krexx::$pool);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [Base64::class . PluginConfigInterface::START_EVENT, $base64],
            [Base64::class . '::callMe' . CallbackConstInterface::EVENT_MARKER_END, $base64]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $string = 'Just another string that we abuse for unit testing. Nothing special.';
        $encodedString = base64_encode($string);
        $model = new Model(Krexx::$pool);
        $model->setHasExtra(true)
            ->setData($encodedString);

        $base64->canHandle($encodedString, $model);
        $base64->callMe();

        $result = CallbackCounter::$staticParameters[0][Base64::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals($string, $result['Decoded base64']);
        $this->assertEquals($encodedString, $result['Content']);
        $this->assertFalse($model->hasExtra());
    }
}