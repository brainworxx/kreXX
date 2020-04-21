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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Code;

/**
 * Constants for the code generation.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
interface CodegenConstInterface
{
    /**
     * Constant identifier for the array multiline code generation.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed. Use CODEGEN_TYPE_ITERATOR_TO_ARRAY.
     */
    const ITERATOR_TO_ARRAY = 'iteratorToArray';

    /**
     * Constant identifier for the json multiline code generation.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed. Use CODEGEN_TYPE_JSON_DECODE.
     */
    const JSON_DECODE = 'jsonDecode';

    /**
     * Identifier for inaccessible array multiline code generation.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed. Use CODEGEN_TYPE_ARRAY_VALUES_ACCESS.
     */
    const ARRAY_VALUES_ACCESS = 'arrayValuesAccess';

    const CODEGEN_TYPE_META_CONSTANTS = 'metaConstants';
    const CODEGEN_TYPE_PUBLIC = 'public';
    const CODEGEN_TYPE_ITERATOR_TO_ARRAY = 'iteratorToArray';
    const CODEGEN_TYPE_JSON_DECODE = 'jsonDecode';
    const CODEGEN_TYPE_ARRAY_VALUES_ACCESS = 'arrayValuesAccess';
    const CODEGEN_TYPE_EMPTY = 'empty';
}