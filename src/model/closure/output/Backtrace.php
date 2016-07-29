<?php
/**
 * @file
 *   Model for the view rendering, hosting the backtrace closure.
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

namespace Brainworxx\Krexx\Model\Closure\Output;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Analysis\Variables;

class Backtrace extends Simple
{
    /**
     * Renders a backtrace.
     *
     * @return string
     */
    public function renderMe()
    {
        $output = '';
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $stepData = $this->parameters['stepData'];
        // File.
        if (isset($stepData['file'])) {
            $fileModel = new Simple();
            $fileModel->setData($stepData['file'])
                ->setName('File')
                ->setNormal($stepData['file'])
                ->setType('string ' . strlen($stepData['file']));

            $output .= SkinRender::renderSingleChild($fileModel);
        }
        // Line.
        if (isset($stepData['line'])) {
            $lineModel = new Simple();
            $lineModel->setData($stepData['line'])
                ->setName('Line no.')
                ->setNormal($stepData['line'])
                ->setType('integer');

            $output .= SkinRender::renderSingleChild($lineModel);
        }
        // Sourcecode, is escaped by now.
        if (isset($stepData['sourcecode'])) {
            $sourceModel = new Simple();
            $sourceModel->setData($stepData['sourcecode'])
                ->setIsEscaped(true)
                ->setName('Sourcecode')
                ->setNormal('. . .')
                ->setType('PHP');

            $output .= SkinRender::renderSingleChild($sourceModel);
        }
        // Function.
        if (isset($stepData['function'])) {
            $functionModel = new Simple();
            $functionModel->setData($stepData['function'])
                ->setName('Last called function')
                ->setNormal($stepData['function'])
                ->setType('string ' . strlen($stepData['function']));

            $output .= SkinRender::renderSingleChild($functionModel);
        }
        // Object.
        if (isset($stepData['object'])) {
            $output .= Objects::analyseObject($stepData['object'], 'Calling object');
        }
        // Type.
        if (isset($stepData['type'])) {
            $typeModel = new Simple();
            $typeModel->setData($stepData['type'])
                ->setName('Call type')
                ->setNormal($stepData['type'])
                ->setType('string ' . strlen($stepData['type']));

            $output .= SkinRender::renderSingleChild($typeModel);
        }
        // Args.
        if (isset($stepData['args'])) {
            $output .= Variables::analyseArray($stepData['args'], 'Arguments from the call');
        }

        return $output;
    }
}