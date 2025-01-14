<?php

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\OpaqueRessource;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(OpaqueRessource::class, 'callMe')]
class OpaqueRessourceTest extends AbstractHelper implements CallbackConstInterface
{
     /**
     * What the method name says. Call it with a simulated wrong php version.
     */
    public function testCallMeWrongPhpVersion()
    {
        $versionCompareMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects',
            'version_compare'
        );
        $versionCompareMock->expects($this->once())
            ->willReturn(true);

        // We only expect the start event, nothing more.
        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque]
        );

        $this->assertEquals('', $opaque->callMe());
    }

    /**
     * Test the analysis of the so-called opaque ressource class analysis.
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::analysisEnd', $opaque]
        );

        $fixture = [self::PARAM_DATA => curl_init()];
        $opaque->setParameters($fixture);
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $opaque->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $result = $renderNothing->model['renderExpandableChild'][0]->getParameters()[static::PARAM_DATA];
        // Getting a quick glance at the results.
        $this->assertEquals('', $result['url']);
        $this->assertEquals(0, $result['http_code']);
        $this->assertEquals(0, $result['redirect_count']);
    }
}
