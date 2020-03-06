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

/**
 * Deep analysis for json strings.
 *
 * @uses string data
 *   The only feasible way to test a string is by decoding it, which is done in
 *   the canHandle(). And once we have decoded it, we dump it.
 * @uses Model model
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Json extends AbstractScalarAnalysis
{
    /**
     * @var \stdClass
     */
    protected $decodedJson;

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return function_exists('json_decode');
    }

    /**
     * Test, if this is a json, and if we can decode it.
     *
     * @param bool|int|string $string
     *   The possible json.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle($string): bool
    {
        // Get a fist impression.
        $first = substr($string, 0, 1);
        if (($first === '{' xor $first === '[') === false) {
            return false;
        }

        // The only way to test a valid json, is to decode it.
        $this->decodedJson = json_decode($string);
        if (json_last_error() === JSON_ERROR_NONE || $this->decodedJson !== null) {
            return true;
        }

        return false;
    }

    /**
     * Add the decode json and a pretty-print-json to the output.
     *
     * @return array
     *   The array for the meta callback.
     */
    protected function handle(): array
    {
        $meta = [];
        $meta[static::META_DECODED_JSON] = $this->decodedJson;
        $meta[static::META_PRETTY_PRINT] = $this->pool->encodingService
            ->encodeString(json_encode($this->decodedJson, JSON_PRETTY_PRINT));

        // Move the extra part into a nest, for better readability.
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $this->parameters[static::PARAM_MODEL];
        if ($model->hasExtra()) {
            $model->setHasExtra(false);
            $meta[static::META_CONTENT] = $model->getData();
        }

        return $meta;
    }

}
