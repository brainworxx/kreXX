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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

/**
 * Array keys that are directly rendered into the frontend.
 */
interface ViewConstInterface
{
    /**
     * @var string
     */
    public const META_DECLARED_IN = 'Declared in';

    /**
     * @var string
     */
    public const META_COMMENT = 'Comment';

    /**
     * @var string
     */
    public const META_SOURCE = 'Source';

    /**
     * @var string
     */
    public const META_NAMESPACE = 'Namespace';

    /**
     * @var string
     */
    public const META_PARAM_NO = 'Parameter #';

    /**
     * @var string
     */
    public const META_HELP = 'Help';

    /**
     * @var string
     */
    public const META_LENGTH = 'Length';

    /**
     * @var string
     */
    public const META_METHOD_COMMENT = 'Method comment';

    /**
     * @var string
     */
    public const META_HINT = 'Hint';

    /**
     * @var string
     */
    public const META_ENCODING = 'Encoding';

    /**
     * @var string
     */
    public const META_MIME_TYPE = 'Mimetype';

    /**
     * @var string
     */
    public const META_METHODS = 'Methods';

    /**
     * @var string
     */
    public const META_CLASS_DATA = 'Meta class data';

    /**
     * @var string
     */
    public const META_CLASS_NAME = 'Classname';

    /**
     * @var string
     */
    public const META_INTERFACES = 'Interfaces';

    /**
     * @var string
     */
    public const META_TRAITS = 'Traits';

    /**
     * @var string
     */
    public const META_INHERITED_CLASS = 'Inherited class';

    /**
     * @var string
     */
    public const META_PREDECLARED = 'n/a, is predeclared';

    /**
     * @var string
     */
    public const META_UNDECLARED = 'undeclared';

    /**
     * @var string
     */
    public const META_IN_TRAIT =  'in trait: ';

    /**
     * @var string
     */
    public const META_IN_LINE = 'in line: ';

    /**
     * @var string
     */
    public const META_IN_CLASS = 'in class: ';

    /**
     * @var string
     */
    public const META_PRETTY_PRINT = 'Pretty print';

    /**
     * @var string
     */
    public const META_DECODED_JSON = 'Decoded json';

    /**
     * @var string
     */
    public const META_DECODED_XML = 'Decoded xml';

    /**
     * @var string
     */
    public const META_CONTENT = 'Content';

    /**
     * @var string
     */
    public const META_TIMESTAMP = 'Timestamp';

    /**
     * @var string
     */
    public const META_RETURN_TYPE = 'Return type';

    /**
     * Css class name.
     *
     * @var string
     */
    public const STYLE_HIDDEN = 'khidden';

    /**
     * Css class name.
     *
     * @var string
     */
    public const STYLE_ACTIVE = 'kactive';
}
