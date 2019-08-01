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

namespace Brainworxx\Krexx\Tests\Service\Config;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Config\Security;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;

class ConfigTest extends AbstractTest
{

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        Pool::createPool();
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        unset($_SERVER[Config::REMOTE_ADDRESS]);
    }

    /**
     * Test the initialisation of the configuration class.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::__construct
     * @covers \Brainworxx\Krexx\Service\Config\Config::getChunkDir
     * @covers \Brainworxx\Krexx\Service\Config\Config::getLogDir
     * @covers \Brainworxx\Krexx\Service\Config\Config::getPathToIniFile
     * @covers \Brainworxx\Krexx\Service\Config\Config::isRequestAjaxOrCli
     * @covers \Brainworxx\Krexx\Service\Config\Config::isAllowedIp
     */
    public function testConstructNormal()
    {
        // Setup some fixtures.
        $chunkPath = 'chunks path';
        $configPath = 'config path';
        $logPath = 'log path';
        $evilClassOne = 'some classname';
        $evilClassTwo ='another classname';

        // Assign them
        Registration::setChunksFolder($chunkPath);
        Registration::setConfigFile($configPath);
        Registration::setLogFolder($logPath);
        Registration::addClassToDebugBlacklist($evilClassOne);
        Registration::addClassToDebugBlacklist($evilClassTwo);
        Registration::addClassToDebugBlacklist($evilClassOne);

        // Simulate a normal call (not cli or ajax).
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Config\\', 'php_sapi_name');
        $sapiMock->expects($this->exactly(2))
            ->will($this->returnValue('not cli'));

        // Create the test subject.
        $config = new Config(Krexx::$pool);

        // Setting of the pool
        $this->assertAttributeSame(Krexx::$pool, 'pool', $config);

        // Setting of the three folders
        $this->assertEquals($chunkPath, $config->getChunkDir());
        $this->assertEquals($configPath, $config->getPathToIniFile());
        $this->assertEquals($logPath, $config->getLogDir());

        // Creation of the security class.
        $this->assertInstanceOf(Security::class, $config->security);

        // Assigning itself to the pool.
        $this->assertSame($config, Krexx::$pool->config);

        // Creation of the ini and cookie loader.
        $this->assertAttributeInstanceOf(Ini::class, 'iniConfig', $config);
        $this->assertAttributeInstanceOf(Cookie::class, 'cookieConfig', $config);

        // kreXX should not be disabled.
        $this->assertEquals(false, $config->getSetting($config::SETTING_DISABLED));
    }

    /**
     * Test the browser output on cli.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::__construct
     * @covers \Brainworxx\Krexx\Service\Config\Config::isRequestAjaxOrCli
     */
    public function testConstructCliBrowser()
    {
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Config\\', 'php_sapi_name');
        $sapiMock->expects($this->exactly(2))
            ->will($this->returnValue('cli'));
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with CLI and browser'
        );
    }

    /**
     * Test the browser output on ajax.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::__construct
     * @covers \Brainworxx\Krexx\Service\Config\Config::isRequestAjaxOrCli
     */
    public function testConstructAjaxBrowser()
    {
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Config\\', 'php_sapi_name');
        $sapiMock->expects($this->exactly(1))
            ->will($this->returnValue('not cli'));
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with ajax and browser'
        );
    }

    /**
     * Test the file output on cli.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::__construct
     * @covers \Brainworxx\Krexx\Service\Config\Config::isRequestAjaxOrCli
     */
    public function testConstructCliFile()
    {
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Config\\', 'php_sapi_name');
        $sapiMock->expects($this->exactly(2))
            ->will($this->returnValue('cli'));
        ConfigSupplier::$overwriteValues = [
            Config::SETTING_DESTINATION => 'file'
        ];
        Krexx::$pool->rewrite[Ini::class] = ConfigSupplier::class;
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            false,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with CLI and file'
        );
    }

    /**
     * Test the access from different ips.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::__construct
     * @covers \Brainworxx\Krexx\Service\Config\Config::isRequestAjaxOrCli
     * @covers \Brainworxx\Krexx\Service\Config\Config::isAllowedIp
     */
    public function testConstructIpRange()
    {
        ConfigSupplier::$overwriteValues = [
            Config::SETTING_IP_RANGE => '1.2.3.4.5, 127.0.0.1'
        ];
        Krexx::$pool->rewrite[Ini::class] = ConfigSupplier::class;
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Config\\', 'php_sapi_name');
        $sapiMock->expects($this->exactly(4))
            ->will($this->returnValue('not cli'));

        // Testing coming from the wrong ip
        $_SERVER[Config::REMOTE_ADDRESS] = '5.4.3.2.1';

        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting(Config::SETTING_DISABLED),
            'Testing coming from the wrong ip'
        );

        // Testing coming from the right ip
        $_SERVER[Config::REMOTE_ADDRESS] = '1.2.3.4.5';

        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            false,
            $config->getSetting(Config::SETTING_DISABLED),
            'Testing coming from the right ip'
        );
    }

    /**
     * Test the disabling, directly in the configuration.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::setDisabled
     */
    public function testSetDisabled()
    {
        $config = new Config(Krexx::$pool);
        $config->setDisabled(true);

        $setting = $config->settings[$config::SETTING_DISABLED];
        $this->assertEquals(true, $setting->getValue());
        $this->assertEquals('Internal flow', $setting->getSource());
    }

    /**
     * Test the retrieval of the dev handler fro mthe cooie configuration.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::getDevHandler
     */
    public function testGetDevHandler()
    {
        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method('getConfigFromCookies')
            ->will($this->returnValue('whatever'));
        $config = new Config(Krexx::$pool);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertEquals('whatever', $config->getDevHandler());
    }

    /**
     * Test setting getter.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::getSetting
     */
    public function testGetSetting()
    {
        $config = new Config(Krexx::$pool);
        $config->settings[$config::SETTING_DESTINATION]->setValue('nowhere');
        $this->assertEquals('nowhere', $config->getSetting($config::SETTING_DESTINATION));
    }

    /**
     * Test the loading of a config value from fallback.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::loadConfigValue
     */
    public function testLoadConfigValueFromFallback()
    {
        $config = new Config(Krexx::$pool);
        $iniMock = $this->createMock(Ini::class);
        $iniMock->expects($this->once())
            ->method('getFeIsEditable')
            ->with($config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(true));
        $iniMock->expects($this->once())
            ->method('getConfigFromFile')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(null));

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method('getConfigFromCookies')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(null));

        // Inject them.
        $this->setValueByReflection('iniConfig', $iniMock, $config);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DEBUG_METHODS));
        $model = $config->settings[$config::SETTING_DEBUG_METHODS];
        $this->assertEquals('Factory settings', $model->getSource());
        $this->assertEquals($config::VALUE_DEBUG_METHODS, $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_INPUT, $model->getType());
    }

    /**
     * Test the loading of a config value from ini.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::loadConfigValue
     */
    public function testLoadConfigValueFromIni()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(Ini::class);
        $iniMock->expects($this->once())
            ->method('getFeIsEditable')
            ->with($config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(true));
        $iniMock->expects($this->once())
            ->method('getConfigFromFile')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue('some methods'));

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method('getConfigFromCookies')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(null));

        // Inject them.
        $this->setValueByReflection('iniConfig', $iniMock, $config);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DEBUG_METHODS));
        $model = $config->settings[$config::SETTING_DEBUG_METHODS];
        $this->assertEquals('Krexx.ini settings', $model->getSource());
        $this->assertEquals('some methods', $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_INPUT, $model->getType());
    }

    /**
     * Test the loading of a config value from cookies.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::loadConfigValue
     */
    public function testLoadConfigValueFromCookies()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(Ini::class);
        $iniMock->expects($this->once())
            ->method('getFeIsEditable')
            ->with($config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(true));
        $iniMock->expects($this->never())
            ->method('getConfigFromFile')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS);

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method('getConfigFromCookies')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue('cookie methods'));

        // Inject them.
        $this->setValueByReflection('iniConfig', $iniMock, $config);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DEBUG_METHODS));
        $model = $config->settings[$config::SETTING_DEBUG_METHODS];
        $this->assertEquals('Local cookie settings', $model->getSource());
        $this->assertEquals('cookie methods', $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_INPUT, $model->getType());
    }

    /**
     * Ignoring the cookie config, because the demanded value is uneditable.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::loadConfigValue
     */
    public function testLoadConfigValueUneditable()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(Ini::class);
        $iniMock->expects($this->once())
            ->method('getFeIsEditable')
            ->with($config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue(false));
        $iniMock->expects($this->once())
            ->method('getConfigFromFile')
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->will($this->returnValue('some methods'));

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->never())
            ->method('getConfigFromCookies');

        // Inject them.
        $this->setValueByReflection('iniConfig', $iniMock, $config);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DEBUG_METHODS));
        $model = $config->settings[$config::SETTING_DEBUG_METHODS];
        $this->assertEquals('Krexx.ini settings', $model->getSource());
        $this->assertEquals('some methods', $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_INPUT, $model->getType());
    }

    /**
     * Testing that reenabling kreXX with cookies does not work.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::loadConfigValue
     */
    public function testLoadConfigValueReenableWithCoookies()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(Ini::class);
        $iniMock->expects($this->once())
            ->method('getFeIsEditable')
            ->with($config::SETTING_DISABLED)
            ->will($this->returnValue(true));
        $iniMock->expects($this->once())
            ->method('getConfigFromFile')
            ->with($config::SECTION_OUTPUT, $config::SETTING_DISABLED)
            ->will($this->returnValue('true'));

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method('getConfigFromCookies')
            ->with($config::SECTION_OUTPUT, $config::SETTING_DISABLED)
            ->will($this->returnValue('false'));

        // Inject them.
        $this->setValueByReflection('iniConfig', $iniMock, $config);
        $this->setValueByReflection('cookieConfig', $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DISABLED));
        $model = $config->settings[$config::SETTING_DISABLED];
        $this->assertEquals('Krexx.ini settings', $model->getSource());
        $this->assertEquals(true, $model->getValue(), 'It is still disabled!');
        $this->assertEquals($config::SECTION_OUTPUT, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_SELECT, $model->getType());
    }

    /**
     * Mocking of a registered skin, that is actually selected.
     *
     * @return \Brainworxx\Krexx\Service\Config\Config
     */
    protected function mockSkin()
    {
        Registration::registerAdditionalskin('some skin', 'some class', 'some directory');
        $config = new Config(Krexx::$pool);
        $config->settings[$config::SETTING_SKIN]->setValue('some skin');
        return $config;
    }

    /**
     * Test the getting of the class name of the currently selected skin.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::getSkinClass
     */
    public function testGetSkinClass()
    {
        $config = $this->mockSkin();
        $this->assertEquals('some class', $config->getSkinClass());
    }

    /**
     * Test the getting of the directory of the currently selected skin.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::getSkinDirectory
     */
    public function testGetSkinDirectory()
    {
        $config = $this->mockSkin();
        $this->assertEquals('some directory', $config->getSkinDirectory());
    }

    /**
     * Test the getting of the human readable names for the skins.
     *
     * @covers \Brainworxx\Krexx\Service\Config\Config::getSkinList
     */
    public function testGetSkinList()
    {
        $config = $this->mockSkin();
        $this->assertEquals(
            [$config::SKIN_SMOKY_GREY, $config::SKIN_HANS, 'some skin'],
            $config->getSkinList()
        );
    }
}
