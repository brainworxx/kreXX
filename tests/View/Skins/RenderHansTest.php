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
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Skins\RenderHans;

class RenderHansTest extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';
    const GET_NAME = 'getName';
    const GET_DOMID = 'getDomid';
    const GET_NORMAL = 'getNormal';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const GET_JSON = 'getJson';
    const GET_HAS_EXTRAS = 'getHasExtra';
    const GET_DATA = 'getData';
    const GET_IS_CALLBACK = 'getIsCallback';
    const GET_TYPE = 'getType';
    const RENDER_ME = 'renderMe';

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
     * Nice, huh?
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
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_EDIT_CHILD . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_SOURCE .
                    $this->renderHans::MARKER_HELP
                ],
                // singleInput.html
                [
                    static::PATH_TO_SKIN . 'singleInput' . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ID .
                    $this->renderHans::MARKER_VALUE .
                    '<input'
                ],
                // singleSelect.html
                [
                    static::PATH_TO_SKIN . 'single' . Fallback::RENDER_TYPE_SELECT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ID .
                    $this->renderHans::MARKER_OPTIONS
                ],
                // singleSelectOption.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_SELECT_OPTIONS . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VALUE .
                    $this->renderHans::MARKER_SELECTED .
                    $this->renderHans::MARKER_TEXT
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_BUTTON . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_TYPE_CLASSES .
                    $this->renderHans::MARKER_CLASS .
                    $this->renderHans::MARKER_TEXT .
                    $this->renderHans::MARKER_HELP
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FATAL_MAIN . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ERROR_STRING .
                    $this->renderHans::MARKER_FILE .
                    $this->renderHans::MARKER_LINE .
                    $this->renderHans::MARKER_SOURCE
                ],
                // fatalHeader.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FATAL_HEADER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VERSION .
                    $this->renderHans::MARKER_ENCODING .
                    $this->renderHans::MARKER_CSS_JS .
                    $this->renderHans::MARKER_SEARCH .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_KREXX_ID
                ],
                // messages.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_MESSAGE . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_MESSAGE
                ],
                // backtraceSourceLine
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_BACKTRACE_SOURCELINE . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CLASS_NAME .
                    $this->renderHans::MARKER_LINE_NO .
                    $this->renderHans::MARKER_SOURCE_CODE
                ],
                // singleChildHr.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_HR . $fileSuffix,
                    true,
                    'HR does not mean human resources'
                ],
                // br.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_BR . $fileSuffix,
                    true,
                    'Breaking the line! Breaking the line!'
                ]
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
        $this->mockModel(static::GET_NAME, 'some name');
        $this->mockModel(static::GET_DOMID, 'the DOM ID');
        $this->mockModel(static::GET_NORMAL, 'normal stuff');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'connector left');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'connector right');
        $this->mockModel(static::GET_JSON, ['Jason', 'and the testonauts']);

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
        $this->mockModel(static::GET_HAS_EXTRAS, true);
        $this->mockModel(static::GET_DATA, 'extra data');
        $this->mockModel(static::GET_IS_CALLBACK, true);
        $this->mockModel(static::GET_TYPE, 'type01 type02');
        $this->mockModel(static::GET_NAME, 'my name');
        $this->mockModel(static::GET_NORMAL, 'just normal');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'lefty');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'righty');
        $this->mockModel(static::GET_JSON, ['someKey', 'informative text']);

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

        $this->mockModel(static::GET_TYPE, 'Stringh In-Tee-Ger');
        $this->mockModel(static::GET_NAME, 'another name');
        $this->mockModel(static::GET_NORMAL, 'not normal');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'some conn');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'any conn');
        $this->mockModel(static::RENDER_ME, 'model html');
        $this->mockModel(static::GET_DOMID, 'x12345');

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->will($this->returnValue('generated source'));
        Krexx::$pool->codegenHandler = $codegenMock;

        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->once())
            ->method('chunkMe')
            ->with($this->anything())
            ->willReturnArgument(0);
        Krexx::$pool->chunks = $chunkMock;

        $result = $this->renderHans->renderExpandableChild($this->modelMock, true);
        $this->assertContains('Stringh', $result);
        $this->assertContains('In-Tee-Ger', $result);
        $this->assertContains('another name', $result);
        $this->assertContains('not normal', $result);
        $this->assertContains('some conn', $result);
        $this->assertContains('another name', $result);
        $this->assertContains('any conn', $result);
        $this->assertContains('generated source', $result);
        // Stuff from the nest.
        $this->assertContains('model html', $result);
        $this->assertContains('x12345', $result);
        $this->assertNotContains('khidden', $result);
    }

    /**
     * Test the rendering of a editable input field.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderSingleEditableChild
     */
    public function testRenderSingleEditableChildInput()
    {
        $this->mockModel(static::GET_DOMID, 'nullachtwhatever');
        $this->mockModel(static::GET_NAME, 'myinputvalue');
        $this->mockModel(static::GET_TYPE, 'Input');
        $this->mockModel(static::GET_DATA, 'myData');
        $this->mockModel(static::GET_NORMAL, 'myNormal');

        // A single input field mus not ask for a skin list.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('getSkinList');
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertContains('nullachtwhatever', $result);
        $this->assertContains('myinputvalue', $result);
        $this->assertContains('myData', $result);
        $this->assertContains('myNormal', $result);
        $this->assertContains('<input', $result);
    }

    /**
     * Test the rendering of a editable dropdown field., the skin list
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderSingleEditableChild
     */
    public function testRenderSingleEditableChildSelect()
    {
        $selectedSkin = 'selectedSkin';
        $this->mockModel(static::GET_DOMID, Fallback::SETTING_SKIN);
        $this->mockModel(static::GET_NAME, $selectedSkin);
        $this->mockModel(static::GET_TYPE, Fallback::RENDER_TYPE_SELECT);
        $this->mockModel(static::GET_DATA, 'more data');
        $this->mockModel(static::GET_NORMAL, 'not normal');

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSkinList')
            ->will($this->returnValue([
                $selectedSkin,
                'Herbert'
            ]));
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertContains(Fallback::SETTING_SKIN, $result);
        $this->assertContains($selectedSkin, $result);
        $this->assertContains('Herbert', $result);
        $this->assertContains('more data', $result);
        $this->assertContains('not normal', $result);
        $this->assertContains('selected="selected"', $result);
    }

    /**
     * Test the rendering of a button.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderButton
     */
    public function testRenderButton()
    {
        $this->mockModel(static::GET_NAME, 'clickme');
        $this->mockModel(static::GET_NORMAL, 'doit');

        $result = $this->renderHans->renderButton($this->modelMock);
        $this->assertContains('clickme', $result);
        $this->assertContains('doit', $result);
    }

    /**
     * Test the rendering of the main part of the error handler
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderFatalMain
     */
    public function testRenderFatalMain()
    {
        $errorString = 'Dev oops error';
        $inFile = 'deplyoment.php';
        $line = 456;

        $this->fileServiceMock->expects($this->once())
            ->method('readSourcecode')
            ->with($inFile, $line -1, $line -6, $line+4)
            ->will($this->returnValue('faulty code line'));

        $result = $this->renderHans->renderFatalMain($errorString, $inFile, $line);
        $this->assertContains($errorString, $result);
        $this->assertContains($inFile, $result);
        $this->assertContains((string)$line, $result);
        $this->assertContains('faulty code line', $result);
    }

    /**
     * Test the rendering of the header of the error handler.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderFatalHeader
     */
    public function testRenderFatalHeader()
    {
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->exactly(2))
            ->method('getMarker')
            ->will($this->returnValue('Marky Mark'));
        Krexx::$pool->recursionHandler = $recursionMock;

        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->once())
            ->method('getOfficialEncoding')
            ->will($this->returnValue('Ute Efacht'));
        Krexx::$pool->chunks = $chunkMock;

        $cssJs = 'some content';
        $errorType = 'Oops an error occured.';

        $result = $this->renderHans->renderFatalHeader($cssJs, $errorType);
        $this->assertContains(Krexx::$pool->config->version, $result);
        $this->assertContains('Marky Mark', $result);
        $this->assertContains('Ute Efacht', $result);
        $this->assertContains($cssJs, $result);
        $this->assertContains($errorType, $result);
    }

    /**
     * Test the message rendering.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderMessages
     */
    public function testRenderMessages()
    {
        $fixture = [
            'How do I activate SMS?',
            'How can I readSMS?',
            'What is a messager?',
            'Why am I writing this?'
        ];

        $result = $this->renderHans->renderMessages($fixture);
        foreach ($fixture as $message) {
            $this->assertContains($message, $result);
        }
    }

    /**
     * Test the rendering of a single source code line for the backtrace.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderBacktraceSourceLine
     */
    public function testRenderBacktraceSourceLine()
    {
        $className = 'first class';
        $lineNumber = '92';
        $sourceCode = 'some code we want to display';

        $result = $this->renderHans->renderBacktraceSourceLine($className, $lineNumber, $sourceCode);
        $this->assertContains($className, $result);
        $this->assertContains($lineNumber, $result);
        $this->assertContains($sourceCode, $result);
    }

    /**
     * Test the rendering of a HR tag.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderSingeChildHr
     */
    public function testRenderSingeChildHr()
    {
        $this->assertContains('HR does not mean human resources', $this->renderHans->renderSingeChildHr());
    }

    /**
     * Test the rednering of a line break.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderHans::renderLinebreak
     */
    public function testRenderLineBreak()
    {
        $this->assertContains('Breaking the line! Breaking the line!', $this->renderHans->renderLinebreak());
    }
}
