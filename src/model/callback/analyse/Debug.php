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

namespace Brainworxx\Krexx\Model\Callback\Analyse;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Model\Callback\AbstractCallback;

/**
 * Debug method result analysis methods.
 *
 * @package Brainworxx\Krexx\Model\Callback\Analysis
 *
 * @uses mixed result
 *   The result from one single configured debug method.
 */
class Debug extends AbstractCallback
{
    /**
     * Iterate though the result of the polled debug methods.
     *
     * @return string
     */
    public function callMe()
    {
        $model = new Simple($this->storage);
        $model->setData($this->parameters['result'])
            ->setName('result');
        // This could be anything, we need to route it.
        return $this->storage->routing->analysisHub($model);
    }
}