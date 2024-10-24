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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Model;
use DOMDocument;
use finfo;

/**
 * Doing a deep XML analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Xml extends AbstractScalarAnalysis
{
    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Was the decoding of the XML successful?
     *
     * @var bool
     */
    protected bool $hasErrors = false;

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return function_exists('xml_parser_create') &&
            class_exists(DOMDocument::class) &&
            class_exists(finfo::class) &&
            function_exists('xml_parser_create');
    }

    /**
     * Test, if this is a valid XML structure.
     *
     * @param bool|int|string $string
     *   The possible json.
     * @param Model $model
     *   The model, so far for additional information.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle($string, Model $model): bool
    {
        // Get a first impression, we check the mime type of the model.
        $metaStuff = $model->getJson();
        $mimeType = $this->pool->messages->getHelp('metaMimeTypeString');
        if (
            empty($metaStuff[$mimeType]) ||
            strpos($metaStuff[$mimeType], 'xml;') === false
        ) {
            // Was not identified as xml before.
            // Early return.
            return false;
        }

        $this->model = $model;
        $this->handledValue = $string;
        $this->hasErrors = false;

        return true;
    }

    /**
     * Generate the metadata from the XML-
     *
     * @return array
     */
    protected function handle(): array
    {
        $meta = [];
        $messages = $this->pool->messages;
        // The pretty print done by a dom parser.
        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        set_error_handler($this->pool->retrieveErrorCallback());
        $dom->loadXML($this->handledValue);
        restore_error_handler();

        if ($this->hasErrors) {
            $meta[$messages->getHelp('metaDecodedXml')] = $this->pool->messages->getHelp('metaNoXml');
        } else {
            $meta[$messages->getHelp('metaPrettyPrint')] = $this->pool
                ->encodingService
                ->encodeString($dom->saveXML());
            // Move the extra part into a nest, for better readability.
            $this->model->setHasExtra(false);
            $meta[$messages->getHelp('metaContent')] = $this->model->getData();
        }

        $this->hasErrors = false;

        return $meta;
    }
}
