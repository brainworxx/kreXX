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

namespace Brainworxx\Krexx\Tests\View\Skins;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Skins\RenderHans;

class RenderHansTest extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderHans
     */
    protected $renderHans;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->renderHans = new RenderHans(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderHans);
        $this->mockTemplate();
    }

    /**
     * Short circuiting the existing of a specific template file.
     *
     * Thanks to the internal static caching, these may or maqy not be called.
     * That is why we use the expects($this->any().
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     *
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // connectorLeft.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CONNECTOR_LEFT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CONNECTOR
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CONNECTOR_RIGHT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CONNECTOR
                ],
                // helprow.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HELPROW . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_HELP_TITLE . $this->renderHans::MARKER_HELP_TEXT
                ],
                // help.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HELP . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_HELP
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_RECURSION . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_DOM_ID .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_HELP
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HEADER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VERSION .
                    $this->renderHans::MARKER_KREXX_COUNT .
                    $this->renderHans::MARKER_HEADLINE .
                    $this->renderHans::MARKER_CSS_JS .
                    $this->renderHans::MARKER_KREXX_ID .
                    $this->renderHans::MARKER_SEARCH .
                    $this->renderHans::MARKER_MESSAGES .
                    $this->renderHans::MARKER_ENCODING
                ],
                // search.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SEARCH . $fileSuffix,
                    true,
                    ''
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FOOTER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CALLER .
                    $this->renderHans::MARKER_CONFIG_INFO.
                    $this->renderHans::MARKER_PLUGINS
                ],
                // caller.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CALLER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CALLER_FILE .
                    $this->renderHans::MARKER_CALLER_DATE .
                    $this->renderHans::MARKER_CALLER_LINE
                ],
                // singlePlugin.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_PLUGIN . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_PLUGIN_ACTIVE_CLASS .
                    $this->renderHans::MARKER_PLUGIN_ACTIVE_TEXT .
                    $this->renderHans::MARKER_PLUGIN_TEXT
                ],
                // cssJs.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CSSJS . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CSS .
                    $this->renderHans::MARKER_JS
                ],
                // singleChild.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_GEN_SOURCE .
                    $this->renderHans::MARKER_SOURCE_BUTTON .
                    $this->renderHans::MARKER_EXPAND .
                    $this->renderHans::MARKER_CALLABLE .
                    $this->renderHans::MARKER_EXTRA .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_TYPE_CLASSES .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderHans::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderHans::MARKER_HELP,
                ],
                // singelChildCallable.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD_CALL . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_NORMAL
                ],
                // singleChildExtra.html
                [
                  static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD_EX . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_DATA
                ],
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SOURCE_BUTTON . $fileSuffix,
                    true,
                    'sourcebutton'
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_EX_CHILD_NORMAL . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_GEN_SOURCE .
                    $this->renderHans::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderHans::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderHans::MARKER_IS_EXPANDED .
                    $this->renderHans::MARKER_K_TYPE .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_SOURCE_BUTTON .
                    $this->renderHans::MARKER_HELP .
                    $this->renderHans::MARKER_NEST
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_NEST . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_STYLE .
                    $this->renderHans::MARKER_MAIN_FUNCTION .
                    $this->renderHans::MARKER_DOM_ID
                ],
            ]));

        Krexx::$pool->fileService = $this->fileServiceMock;
    }

    /**
     * The great Moddelmock is not a wizard from Harry Potter.
     *
     * @param $methodName
     * @param $returnValue
     */
    protected function mockModel($methodName, $returnValue)
    {
        if (empty($this->modelMock)) {
            $this->modelMock = $this->createMock(Model::class);
        }
        $this->modelMock->expects($this->once())
            ->method($methodName)
            ->will($this->returnValue($returnValue));
    }

    /**
     * Test the rendering of a recursion.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderRecursion
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderConnectorLeft
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderConnectorRight
     * @covers \Brainworxx\Krexx\View\AbstractRender::generateDataAttribute
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderHelp
     * @covers \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     */
    public function testRenderRecursion()
    {
        // Prepare the model
        $this->mockModel('getName', 'some name');
        $this->mockModel('getDomid', 'the DOM ID');
        $this->mockModel('getNormal', 'normal stuff');
        $this->mockModel('getConnectorLeft', 'connector left');
        $this->mockModel('getConnectorRight', 'connector right');
        $this->mockModel('getJson', ['Jason', 'and the testonauts']);

        // Run the test.
        $result = $this->renderHans->renderRecursion($this->modelMock);
        $this->assertContains('some name', $result);
        $this->assertContains('the DOM ID', $result);
        $this->assertContains('normal stuff', $result);
        $this->assertContains('connector left', $result);
        $this->assertContains('connector right', $result);
        $this->assertContains('Jason', $result);
        $this->assertContains('and the testonauts', $result);
    }

    /**
     * Test the rendering of the kreXX header.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderHeader
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderSearch
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderMessages
     */
    public function testRenderHeader()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getKrexxCount')
            ->will($this->returnValue(42));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $recursionMock = $this->createMock(Recursion::class);
        // Two times fro msearch and header itself.
        $recursionMock->expects($this->exactly(2))
            ->method('getMarker')
            ->will($this->returnValue('recursion Marker'));
        Krexx::$pool->recursionHandler = $recursionMock;

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('outputMessages')
            ->will($this->returnValue('mess ages'));
        Krexx::$pool->messages = $messageMock;

        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->once())
            ->method('getOfficialEncoding')
            ->will($this->returnValue('encoding'));
        krexx::$pool->chunks = $chunkMock;

        // Run the test.
        $result = $this->renderHans->renderHeader('Headliner', 'CSS Wanne Eickel');
        $this->assertContains('42', $result);
        $this->assertContains('recursion Marker', $result);
        $this->assertContains('mess ages', $result);
        $this->assertContains('encoding', $result);
        $this->assertContains('Headliner', $result);
        $this->assertContains('CSS Wanne Eickel', $result);
    }

    /**
     * Test the rendering of the footer.
     *
     * We test the renderExpandableChild separately to keep this one al least
     * a little bit sane.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderFooter
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderExpandableChild
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderCaller
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderPluginList
     */
    public function testRenderFooter()
    {
        // Mock the caller
        $caller = [
            $this->renderHans::TRACE_FILE => 'filename',
            $this->renderHans::TRACE_LINE => 'line 123',
            $this->renderHans::TRACE_DATE => 'yesteryear'
        ];

        // Mock the model for the renderExpandableChild, which we will not test
        // here.
        $model = new Model(Krexx::$pool);

        // Mock the plugin list.
        $pluginList = [
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::PLUGIN_NAME => 'Plugin 1',
                SettingsGetter::PLUGIN_VERSION => '1.0.0.',
            ],
            [
                SettingsGetter::IS_ACTIVE => false,
                SettingsGetter::PLUGIN_NAME => 'Plugin 2',
                SettingsGetter::PLUGIN_VERSION => '2.0.0.',
            ],
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::PLUGIN_NAME => 'Plugin 3',
                SettingsGetter::PLUGIN_VERSION => '3.0.0.',
            ]
        ];
        $this->setValueByReflection('plugins', $pluginList, SettingsGetter::class);

        $result = $this->renderHans->renderFooter($caller, $model);
        $this->assertContains('Plugin 1', $result);
        $this->assertContains('1.0.0.', $result);
        $this->assertContains('Plugin 2', $result);
        $this->assertContains('2.0.0.', $result);
        $this->assertContains('Plugin 3', $result);
        $this->assertContains('3.0.0.', $result);
        $this->assertContains('kisactive', $result);
        $this->assertContains('kisinactive', $result);
        $this->assertContains('active', $result);
        $this->assertContains('inactive', $result);
        $this->assertContains('filename', $result);
        $this->assertContains('line 123', $result);
        $this->assertContains('yesteryear', $result);
    }

    /**
     * Testing the inserting of css and js.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderCssJs
     */
    public function testRenderCssJs()
    {
        $css = 'soem styles';
        $javaScript = 'onClick="alert(\'xss\');"';

        $result = $this->renderHans->renderCssJs($css, $javaScript);
        $this->assertContains($css, $result);
        $this->assertContains($javaScript, $result);
    }

    /**
     * Single child rendering testing.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderSingleChild
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderHelp
     * @covers \Brainworxx\Krexx\View\AbstractRender::generateDataAttribute
     */
    public function testRenderSingleChild()
    {
        $this->mockModel('getHasExtra', true);
        $this->mockModel('getData', 'extra data');
        $this->mockModel('getIsCallback', true);
        $this->mockModel('getType', 'type01 type02');
        $this->mockModel('getName', 'my name');
        $this->mockModel('getNormal', 'just normal');
        $this->mockModel('getConnectorLeft', 'lefty');
        $this->mockModel('getConnectorRight', 'righty');
        $this->mockModel('getJson', ['someKey', 'informative text']);

        $codeGenMock = $this->createMock(Codegen::class);
        $codeGenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->will($this->returnValue('generated code'));
        $codeGenMock->expects($this->once())
            ->method('getAllowCodegen')
            ->will($this->returnValue(true));
        $codeGenMock->expects($this->once())
            ->method('generateWrapperLeft')
            ->will($this->returnValue(''));
        $codeGenMock->expects($this->once())
            ->method('generateWrapperRight')
            ->will($this->returnValue(''));
        Krexx::$pool->codegenHandler = $codeGenMock;

        $result = $this->renderHans->renderSingleChild($this->modelMock);
        $this->assertContains('extra data', $result);
        $this->assertContains('type01', $result);
        $this->assertContains('type02', $result);
        $this->assertContains('my name', $result);
        $this->assertContains('just normal', $result);
        $this->assertContains('lefty', $result);
        $this->assertContains('righty', $result);
        $this->assertContains('someKey', $result);
        $this->assertContains('informative text', $result);
        $this->assertContains('generated code', $result);
    }

    /**
     * Test the rendering of an expandable child.
     *
     * On hindsight, these names are just silly. Then again, we do have a skin
     * with the name 'Hans'.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderExpandableChild
     * @covers \Brainworxx\Krexx\View\AbstractRender::renderNest
     */
    public function testRenderExpandableChild()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(false));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $this->mockModel('getType', 'string integer');
        $this->mockModel('getName', 'another name');
        $this->mockModel('getNormal', 'not normal');
        $this->mockModel('getConnectorLeft', 'some conn');
        $this->mockModel('getConnectorRight', 'any conn');

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->will($this->returnValue('generated source'));
        Krexx::$pool->codegenHandler = $codegenMock;

        // @todo Mock the nest
        // @todo Mock the chunkMe in the Chunks class.

        $this->markTestIncomplete('Write me!');
    }
}
