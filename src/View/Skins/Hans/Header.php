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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Skins\Hans;

/**
 * Renders the header.
 */
trait Header
{
    /**
     * @var string[]
     */
    private array $markerHeader = [
        '{version}',
        '{KrexxCount}',
        '{headline}',
        '{cssJs}',
        '{KrexxId}',
        '{search}',
        '{messages}',
        '{encoding}',
        '{noJavaScript}',
        '{callNumber}',
        '{searchHeadline}'
    ];

    /**
     * {@inheritdoc}
     */
    public function renderHeader(string $headline, string $cssJs): string
    {
        $messages = $this->pool->messages;
        return str_replace(
            $this->markerHeader,
            [
                $this->pool->config->version,
                $this->pool->emergencyHandler->getKrexxCount(),
                $headline,
                $cssJs,
                $this->pool->recursionHandler->getMarker(),
                $this->renderSearch(),
                $messages->outputMessages(),
                $this->pool->chunks->getOfficialEncoding(),
                $messages->getHelp('noJavaScript'),
                $messages->getHelp('callNumber'),
                $messages->getHelp('searchHeadline'),
            ],
            $this->fileCache[static::FILE_HEADER]
        );
    }

    /**
     * Getter of the header for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerHeader(): array
    {
        return $this->markerHeader;
    }
}
