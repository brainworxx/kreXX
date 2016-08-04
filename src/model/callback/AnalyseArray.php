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

namespace Brainworxx\Krexx\Model\Callback;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Analysis\Variables;

/**
 * Array analysis methods (again).
 *
 * @package Brainworxx\Krexx\Model\Closure
 */
class AnalyseArray extends AbstractCallback
{
    /**
     * Renders the expendable around the array analysis.
     *
     * @return string
     */
    public function callMe()
    {
        return $this->iterateThrough($this->parameters['data']);
    }

    /**
     * Render a dump for the properties of an array.
     *
     * @param array &$data
     *   The array we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected static function iterateThrough(&$data)
    {
        $output = '';
        $recursionMarker = OutputActions::$recursionHandler->getMarker();

        // Recursion detection of objects are handled in the hub.
        if (OutputActions::$recursionHandler->isInHive($data)) {
            return OutputActions::$render->renderRecursion(new Simple());
        }

        // Remember, that we've already been here.
        OutputActions::$recursionHandler->addToHive($data);

        $output .= OutputActions::$render->renderSingeChildHr();

        // Iterate through.
        foreach ($data as $key => &$value) {
            // We will not output our recursion marker.
            // Meh, the only reason for the recursion marker
            // in arrays is because of the $GLOBAL array, which
            // we will only render once.
            if ($key === $recursionMarker) {
                continue;
            }
            $key = Toolbox::encodeString($key);
            $model = new Simple();
            $model->setData($value)
                ->setName($key)
                ->setConnector1('[')
                ->setConnector2('] =');
            $output .= Variables::analysisHub($model);
        }
        $output .= OutputActions::$render->renderSingeChildHr();
        return $output;

    }
}
