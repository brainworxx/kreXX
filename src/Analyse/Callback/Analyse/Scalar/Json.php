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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\View\ViewConstInterface;

/**
 * Deep analysis for json strings.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Json extends AbstractScalarAnalysis implements ViewConstInterface
{
    /**
     * Code generation for this one is the json encoder.
     *
     * @var string
     */
    protected $codeGenType = CodegenConstInterface::CODEGEN_TYPE_JSON_DECODE;

    /**
     * What the variable name says.
     *
     * @var \stdClass|array
     */
    protected $decodedJson;

    /**
     * The model, so far.
     *
     * @var Model
     */
    protected $model;

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
     * @param string $string
     *   The string we want to take a look at.
     * @param Model $model
     *   The model, so far.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle($string, Model $model): bool
    {
        // Get a fist impression.
        $first = substr($string, 0, 1);
        if (($first === '{' xor $first === '[') === false) {
            return false;
        }

        // The only way to test a valid json, is to decode it.
        $this->decodedJson = json_decode($string);
        if (json_last_error() === JSON_ERROR_NONE || $this->decodedJson !== null) {
            $this->model = $model;
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
        $meta = [
            static::META_DECODED_JSON => $this->decodedJson,
            static::META_PRETTY_PRINT => $this->pool->encodingService
                ->encodeString(json_encode($this->decodedJson, JSON_PRETTY_PRINT))
        ];

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra() === true) {
            $this->model->setHasExtra(false);
            $meta[static::META_CONTENT] = $this->model->getData();
        }

        return $meta;
    }
}
