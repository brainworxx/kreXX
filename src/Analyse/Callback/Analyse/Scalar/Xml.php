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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use SimpleXMLElement;

/**
 * Doing a deep XML analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Xml extends AbstractScalarAnalysis
{
    /**
     * @var \SimpleXMLElement
     */
    protected $decodedXml;

    /**
     * The model, so far.
     *
     * @var Model
     */
    protected $model;

    /**
     * The original, un-pretty-print XML string.
     *
     * @var string
     */
    protected $originalXml = '';

    /**
     * constants for the XML2array parser
     */
    const XML_AT_ATTRIBUTE = '@attributes';
    const XML_VALUE = 'value';

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return function_exists('libxml_use_internal_errors') &&
            function_exists('simplexml_load_string') &&
            class_exists(\DOMDocument::class, false);
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
        // Get a first impression, we check the midetype of the model.
        $metaStuff = $model->getJson();

        if (
            empty($metaStuff[static::META_MIME_TYPE]) === true ||
            strpos($metaStuff[static::META_MIME_TYPE], 'text/xml;') === false
        ) {
            // Was not identified as xml before.
            // Early return.
            return false;
        }

        // We try to decode it.
        $prevXmlErrorHandling = libxml_use_internal_errors(true);
        $this->decodedXml = simplexml_load_string($string, null, LIBXML_NOCDATA);
        if ($this->decodedXml === false) {
            // Unable to decode this one.
            return false;
        }
        libxml_use_internal_errors($prevXmlErrorHandling);

        // Huh, everything went better than expected.
        $this->model = $model;
        $this->originalXml = $string;
        return true;
    }

    /**
     * Generate the meta data from the XML-
     *
     * @return array
     */
    protected function handle(): array
    {
        $meta = [];
        $meta[static::META_DECODED_XML] = $this->simpleXML2Array($this->decodedXml);

        // The pretty print is a lillte bit more complex.
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->originalXml);
        $meta[static::META_PRETTY_PRINT] = $this->pool->encodingService
            ->encodeString($dom->saveXML());

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[static::META_CONTENT] = $this->model->getData();
        }

        return $meta;
    }

    /**
     * Iterate through the simpleXML object and turn it into an array.
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected function simpleXML2Array(SimpleXMLElement $xml): array
    {
        $array = array();

        foreach ($xml->children() as $key => $node) {
            $child = $this->assignXmlValues($this->simpleXML2Array($node), $node);

            if (!in_array($key, array_keys($array))) {
                $array[$key] = $child;
            } elseif (isset($array[$key][0])) {
                $array[$key][] = $child;
            } else {
                // This node is already there.
                // And since arrays can not have the same key twice,
                // we use an array to display it all.
                $array[$key] = [$array[$key]];
                $array[$key][] = $child;
            }
        }

        return $array;
    }

    /**
     * What the method name says. We assign the attributes and values to the
     * child array.
     *
     * @param array $child
     *   The child array, so far,
     * @param \SimpleXMLElement $node
     *   The node we are currently processing.
     *
     * @return array|string
     *   Either an array, or the straight value of a node.
     */
    protected function assignXmlValues(array $child, SimpleXMLElement $node)
    {
        // Assign the value.
        $child[static::XML_VALUE] = (string)$node;

        // Iterate through the attributes.
        foreach ($node->attributes() as $attributeKey => $attributeValue) {
            if (isset($child[static::XML_AT_ATTRIBUTE]) === false) {
                $child[static::XML_AT_ATTRIBUTE] = [];
            }

            $child[static::XML_AT_ATTRIBUTE][$attributeKey] = (string)$attributeValue;
        }

        return $child;
    }
}
