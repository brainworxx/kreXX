<?php
/**
 * @file
 *   Model for the view rendering, hosting iterating through array closure.
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

namespace Brainworxx\Krexx\Model\Closure\Variables;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\Analysis\Hive;

class IterateThrough extends Simple
{
    /**
     * @return string
     */
    public function renderMe()
    {
        $output = '';
        $data = $this->parameters['data'];
        $isObject = is_object($data);

        $recursionMarker = Hive::getMarker();

        // Recursion detection of objects are handled in the hub.
        if (is_array($data) && Hive::isInHive($data)) {
            return SkinRender::renderRecursion(new Simple());
        }

        // Remember, that we've already been here.
        Hive::addToHive($data);

        // Keys?
        $keys = array_keys($data);

        $output .= SkinRender::renderSingeChildHr();

        // Iterate through.
        foreach ($keys as $k) {
            // Skip the recursion marker.
            if ($k === $recursionMarker) {
                continue;
            }

            // Get real value.
            if ($isObject) {
                $v = &$data->$k;
            } else {
                $v = &$data[$k];
            }

            $output .= Variables::analysisHub($v, $k, '[', '] =');
        }
        $output .= SkinRender::renderSingeChildHr();
        return $output;
    }
}