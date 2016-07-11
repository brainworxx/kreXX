<?php
/**
 * @file
 *   Code generation functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\Model\Closure\Output\Backtrace;
use Brainworxx\Krexx\Model\Closure\Output\Footer;
use Brainworxx\Krexx\Model\Simple;

/**
 * This class hosts the code generation functions.
 *
 * @package Brainworxx\Krexx\View
 */
class Output
{

    public static $headerSend = false;

    /**
     * Simply outputs the Header of kreXX.
     *
     * @param string $headline
     *   The headline, displayed in the header.
     *
     * @return string
     *   The generated markup
     */
    public static function outputHeader($headline)
    {

        // Do we do an output as file?
        if (!self::$headerSend) {
            // Send doctype and css/js only once.
            self::$headerSend = true;
            return SkinRender::renderHeader('<!DOCTYPE html>', $headline, self::outputCssAndJs());
        } else {
            return SkinRender::renderHeader('', $headline, '');
        }
    }

    /**
     * Simply renders the footer and output current settings.
     *
     * @param array $caller
     *   Where was kreXX initially invoked from.
     * @param bool $isExpanded
     *   Are we rendering an expanded footer?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup.
     */
    public static function outputFooter($caller, $isExpanded = false)
    {

        // Now we need to stitch together the content of the ini file
        // as well as it's path.
        if (!is_readable(Config::getPathToIni())) {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = 'Krexx.ini not found, using factory settings';
            // $config = array();
        } else {
            $path = 'Current configuration';
        }

        $wholeConfig = Config::getWholeConfiguration();
        $source = $wholeConfig[0];
        $config = $wholeConfig[1];

        $parameter = array($config, $source);

        $model = new Footer();
        $model->setName($path)
            ->setType(Config::getPathToIni())
            ->setHelpid('currentSettings')
            ->addParameter('config', $config)
            ->addParameter('source', $source);

        $configOutput = SkinRender::renderExpandableChild($model, $isExpanded);
        return SkinRender::renderFooter($caller, $configOutput, $isExpanded);
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    public static function outputCssAndJs()
    {
        // Get the css file.
        $css = Toolbox::getFileContents(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/skin.css');
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);

        // Adding our DOM tools to the js.
        if (is_readable(Config::$krexxdir . 'resources/jsLibs/kdt.min.js')) {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.min.js';
        } else {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.js';
        }
        $js = Toolbox::getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        if (is_readable(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js')) {
            $jsFile = Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js';
        } else {
            $jsFile = Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.js';
        }
        $js .= Toolbox::getFileContents($jsFile);

        return SkinRender::renderCssJs($css, $js);
    }

    /**
     * Outputs a backtrace.
     *
     * We need to format this one a little bit different than a
     * normal array.
     *
     * @param array $backtrace
     *   The backtrace.
     *
     * @return string
     *   The rendered backtrace.
     */
    public static function outputBacktrace(array $backtrace)
    {
        $output = '';

        // Add the sourcecode to our backtrace.
        $backtrace = Toolbox::addSourcecodeToBacktrace($backtrace);

        foreach ($backtrace as $step => $stepData) {
            $model = new Backtrace();
            $model->setName($step)
                ->setType('Stack Frame')
                ->addParameter('stepData', $stepData);

            $output .= SkinRender::renderExpandableChild($model);
        }

        return $output;
    }
}
