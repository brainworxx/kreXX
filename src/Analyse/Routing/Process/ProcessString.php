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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Scalar\ScalarString;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\FileinfoDummy;
use finfo;

/**
 * Processing of strings.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessString extends AbstractRouting implements ProcessInterface
{
    /**
     * The buffer info class. We use it to get the mimetype from a string.
     *
     * @var \finfo|\Brainworxx\Krexx\Service\Misc\FileinfoDummy
     */
    protected $bufferInfo;

    /**
     * The deeper string analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\Scalar\AbstractScalar;
     */
    protected $scalarString;

    /**
     * Length threshold, where we do a buffer-info analysis.
     *
     * @var int
     */
    protected $bufferInfoThreshold = 20;

    /**
     * Inject the pool and initialize the buffer-info class.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Init the fileinfo class.
        if (class_exists(finfo::class, false) === true) {
            $this->bufferInfo = new finfo(FILEINFO_MIME);
        } else {
            // Use a "polyfill" dummy, tell the dev that we have a problem.
            $this->bufferInfo = $pool->createClass(FileinfoDummy::class);
            $pool->messages->addMessage('fileinfoNotInstalled');
        }

        $this->scalarString = $pool->createClass(ScalarString::class);
    }

    /**
     * Render a dump for a string value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function process(Model $model): string
    {
        $data = $model->getData();

        // Check, if we are handling large string, and if we need to use a
        // preview (which we call "extra").
        // We also need to check for linebreaks, because the preview can not
        // display those.
        $length = $this->retrieveLengthAndEncoding($data, $model);
        if ($length > 50 || strstr($data, PHP_EOL) !== false) {
            $cut = $this->pool->encodingService->encodeString(
                $this->pool->encodingService->mbSubStr($data, 0, 50)
            ) . static::UNKNOWN_VALUE;

            $data = $this->pool->encodingService->encodeString($data);

            $model->setHasExtra(true)
                ->setNormal($cut)
                ->setData($data);
        } else {
            $model->setNormal($this->pool->encodingService->encodeString($data));
        }

        return $this->handleStringScalar($model->addToJson(static::META_LENGTH, $length));
    }

    /**
     * Inject the scalar analysis callback and handle possible recursions.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     *
     * @return string
     *   The generated DOM.
     */
    protected function handleStringScalar(Model $model): string
    {
        $this->scalarString->handle($model);
        $domId = $model->getDomid();
        if ($domId !== '' && $this->pool->recursionHandler->isInMetaHive($domId) === true) {
            return $this->pool->render->renderRecursion($model);
        }

        $this->pool->recursionHandler->addToMetaHive($domId);
        return $this->pool->render->renderExpandableChild($this->dispatchProcessEvent($model));
    }

    /**
     * Retrieve the length and set the encoding in the model.
     *
     * @param string $data
     *   The string of which we want ot know the length and encoding.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     *
     * @return int
     *   the length of the string.
     */
    protected function retrieveLengthAndEncoding(string $data, Model $model): int
    {
        $encoding = $this->pool->encodingService->mbDetectEncoding($data);
        if ($encoding === false) {
            // Looks like we have a mixed encoded string.
            $length = $this->pool->encodingService->mbStrLen($data);
        } else {
            // Normal encoding, nothing special here.
            $length = $this->pool->encodingService->mbStrLen($data, $encoding);
        }

        // Long string or with broken encoding.
        if ($length > $this->bufferInfoThreshold) {
            // Let's see, what the buffer-info can do with it.
            $model->addToJson(static::META_MIME_TYPE, $this->bufferInfo->buffer($data));
        } elseif ($encoding === false) {
            // Short string with broken encoding.
            $model->addToJson(static::META_ENCODING, 'broken');
        } else {
            // Short string with normal encoding.
            $model->addToJson(static::META_ENCODING, $encoding);
        }

        $model->setType(static::TYPE_STRING . $length);

        return $length;
    }
}
