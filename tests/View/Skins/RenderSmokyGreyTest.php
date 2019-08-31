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
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

class RenderSmokyGreyTest extends AbstractTest
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
    const GET_CONNECTOR_LANGUAGE = 'getConnectorLanguage';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderSmokyGrey
     */
    protected $renderSmokyGrey;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->renderSmokyGrey = new RenderSmokyGrey(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderSmokyGrey);
        $this->mockTemplate();
    }

     /**
     * Short circuiting the existence of a specific template file.
     * We only simulate the differences in the smoky grey skin.
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SOURCE_BUTTON . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_LANGUAGE
                ],
                // singleChild.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_CHILD . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_SOURCE_BUTTON .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_NEST . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_STYLE .
                    $this->renderSmokyGrey::MARKER_MAIN_FUNCTION .
                    $this->renderSmokyGrey::MARKER_DOM_ID
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_EX_CHILD_NORMAL . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_GEN_SOURCE .
                    $this->renderSmokyGrey::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderSmokyGrey::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderSmokyGrey::MARKER_IS_EXPANDED .
                    $this->renderSmokyGrey::MARKER_K_TYPE .
                    $this->renderSmokyGrey::MARKER_CONNECTOR_LEFT .
                    $this->renderSmokyGrey::MARKER_CONNECTOR_RIGHT .
                    $this->renderSmokyGrey::MARKER_NAME .
                    $this->renderSmokyGrey::MARKER_NORMAL .
                    $this->renderSmokyGrey::MARKER_TYPE .
                    $this->renderSmokyGrey::MARKER_SOURCE_BUTTON .
                    $this->renderSmokyGrey::MARKER_HELP .
                    $this->renderSmokyGrey::MARKER_NEST .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_CONNECTOR_RIGHT . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_CONNECTOR
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_RECURSION . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_EDIT_CHILD . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_BUTTON . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_CLASS .
                    $this->renderSmokyGrey::MARKER_TEXT .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_HEADER . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_K_DEBUG_CLASSES .
                    $this->renderSmokyGrey::MARKER_K_CONFIG_CLASSES
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_FOOTER . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_K_CONFIG_CLASSES
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_FATAL_MAIN . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_SEARCH .
                    $this->renderSmokyGrey::MARKER_KREXX_ID .
                    $this->renderSmokyGrey::MARKER_PLUGINS
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
     * Test the additional stuff done by smoky grey.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderSingleChild
     * @covers \Brainworxx\Krexx\View\AbstractRender::encodeJson
     * @covers \Brainworxx\Krexx\View\AbstractRender::jsonEscape
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderHelp
     */
    public function testRenderSingleChild()
    {
        $this->mockModel(static::GET_CONNECTOR_LANGUAGE, 'Fortran');
        $this->mockModel(static::GET_JSON, ['Friday' =>'the 12\'th']);

        $codeGenMock = $this->createMock(Codegen::class);
        $codeGenMock->expects($this->once())
            ->method('generateSource')
            ->will($this->returnValue('real, intent(in) :: argument1'));
        $codeGenMock->expects($this->once())
            ->method('getAllowCodegen')
            ->will($this->returnValue(true));
        Krexx::$pool->codegenHandler = $codeGenMock;

        $result = $this->renderSmokyGrey->renderSingleChild($this->modelMock);
        $this->assertContains('Fortran', $result);
        // The \\\\ is the escaping of the escaping.
        // Yo dawg, we heard  . . .
        $this->assertContains('{&#34;Friday&#34;:&#34;the 12\\\\u0022th&#34;}', $result);
    }

    /**
     * Test the rendering of an expandable child.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderExpandableChild
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderConnectorRight
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderHelp
     */
    public function testRenderExpandableChild()
    {
        $this->mockModel(static::GET_NAME, 'Model name');
        $this->mockModel(static::GET_TYPE, 'my type');
        $this->mockModel(static::GET_CONNECTOR_LANGUAGE, 'Turbo Pasquale');
        $this->mockModel(static::GET_NORMAL, 'I am not');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'he who must not be pampered');
        $this->mockModel(static::GET_JSON, ['Voldemort' => 'noNose.']);
        $this->mockModel(static::GET_DOMID, 'passport');
        $this->mockModel(static::RENDER_ME, 'birdnest');

        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(false));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->will($this->returnValue('some meaningful code'));
        $codegenMock->expects($this->once())
            ->method('generateWrapperLeft')
            ->will($this->returnValue(''));
        $codegenMock->expects($this->once())
            ->method('generateWrapperRight')
            ->will($this->returnValue(''));
        Krexx::$pool->codegenHandler = $codegenMock;

        $result = $this->renderSmokyGrey->renderExpandableChild($this->modelMock);
        $this->assertContains('Model name', $result);
        $this->assertContains('my', $result);
        $this->assertContains('type', $result);
        $this->assertContains('Turbo Pasquale', $result);
        $this->assertContains('I am not', $result);
        $this->assertContains('he who must not be pampered', $result);
        $this->assertContains('noNose.', $result);
        $this->assertContains('passport', $result);
        $this->assertContains('birdnest', $result);
    }

    /**
     * Test the additional stuff of the recursion rendering.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderRecursion
     */
    public function testRenderRecursion()
    {
        $this->mockModel(static::GET_JSON, ['jay' => 'son']);
        $result = $this->renderSmokyGrey->renderRecursion($this->modelMock);
        $this->assertContains('jay', $result);
        $this->assertContains('son', $result);
    }

    /**
     * Test the additional stuff of the singel editable child rendering.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderSingleEditableChild
     */
    public function testRenderSingleEditableChild()
    {
        $this->mockModel(static::GET_JSON, ['formless' => 'forming']);
        $result = $this->renderSmokyGrey->renderSingleEditableChild($this->modelMock);
        $this->assertContains('formless', $result);
        $this->assertContains('forming', $result);
    }

    /**
     * Test the rendering of a button. Again we test only the additional stuff.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderButton
     */
    public function testRenderButton()
    {
        $this->mockModel(static::GET_JSON, ['buttonJson' => 'isFun']);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_NAME)
            ->will($this->returnValue('sayMyName'));

        $result = $this->renderSmokyGrey->renderButton($this->modelMock);
        $this->assertContains('sayMyName', $result);
        $this->assertContains('buttonJson', $result);
        $this->assertContains('isFun', $result);
    }

    /**
     * Test the additional stuff in the header rendering.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderHeader
     */
    public function testRenderHeader()
    {
        $result = $this->renderSmokyGrey->renderHeader($this->renderSmokyGrey::HEADLINE_EDIT_SETTINGS, '');
        $this->assertContains($this->renderSmokyGrey::STYLE_HIDDEN, $result);
        $this->assertContains($this->renderSmokyGrey::STYLE_ACTIVE, $result);

        $result = $this->renderSmokyGrey->renderHeader('', '');
        $this->assertNotContains($this->renderSmokyGrey::STYLE_HIDDEN, $result);
        $this->assertContains($this->renderSmokyGrey::STYLE_ACTIVE, $result);
    }

    /**
     * Test the removal of the debug tab, when we are in config mode.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderFooter
     */
    public function testRenderFooter()
    {
        $this->mockEmergencyHandler();
        $model = $this->createMock(Model::class);
        $model->expects($this->exactly(2))
            ->method('getJson')
            ->will($this->returnValue([]));

        $result = $this->renderSmokyGrey->renderFooter([], $model, true);
        $this->assertNotContains($this->renderSmokyGrey::STYLE_HIDDEN, $result);

        $result = $this->renderSmokyGrey->renderFooter([], $model, false);
        $this->assertContains($this->renderSmokyGrey::STYLE_HIDDEN, $result);
    }

    /**
     * Test the additional stuff in the render fatal main.
     *
     * @covers \Brainworxx\Krexx\View\Skins\RenderSmokyGrey::renderFatalMain
     */
    public function testRenderFatalMain()
    {
        $result = $this->renderSmokyGrey->renderFatalMain('', '', 1);
        $this->assertNotContains($this->renderSmokyGrey::MARKER_SEARCH, $result);
        $this->assertNotContains($this->renderSmokyGrey::MARKER_KREXX_ID, $result);
        $this->assertNotContains($this->renderSmokyGrey::MARKER_PLUGINS, $result);
    }
}
