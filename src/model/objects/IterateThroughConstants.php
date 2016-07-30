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

namespace Brainworxx\Krexx\Model\Objects;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Variables;

/**
 * Constants analysis functions.
 *
 * @package Brainworxx\Krexx\Model\Objects
 */
class IterateThroughConstants extends Simple
{

    /**
     * Iterates through the object constants.
     *
     * @return string
     */
    public function renderMe()
    {
        $output = '';
        $data = $this->parameters['data'];

        $output .= SkinRender::renderSingeChildHr();

        // We do not need to check the hive, this is ome class internal stuff.
        // Is it even possible to create a recursion here?
        // Iterate through.
        foreach ($data as $k => $v) {
            $v = &$data[$k];
            $output .= Variables::analysisHub($v, $k, '::', ' =');
        }

        $output .= SkinRender::renderSingeChildHr();
        return $output;
    }
}
