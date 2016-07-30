<?php
/**
 * @file
 *   Object properties analysis functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
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

namespace Brainworxx\Krexx\Analysis\Objects;

use Brainworxx\Krexx\Model\Closure\Objects\Constants;
use Brainworxx\Krexx\Model\Closure\Objects\IterateThroughConstants;
use Brainworxx\Krexx\View\SkinRender;

/**
 * This class hosts the object properties analysis methods.
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Properties
{

    /**
     * Gets the properties from a reflection property of the object.
     *
     * @param array $refProps
     *   The list of the reflection properties.
     * @param \ReflectionClass $ref
     *   The reflection of the object we are currently analysing.
     * @param object $data
     *   The object we are currently analysing.
     * @param string $label
     *   The additional part of the template file.
     *
     * @return string
     *   The generated markup.
     */
    public static function getReflectionPropertiesData(array $refProps, \ReflectionClass $ref, $data, $label)
    {
        // We are dumping public properties direct into the main-level, without
        // any "abstraction level", because they can be accessed directly.
        $model = new \Brainworxx\Krexx\Model\Closure\Objects\Properties();
        $model->addParameter('refProps', $refProps)
            ->addParameter('ref', $ref)
            ->addParameter('orgObject', $data);

        if (strpos(strtoupper($label), 'PUBLIC') === false) {
            // Protected or private properties.
            $model->setName($label)
                ->setType('class internals');
            return SkinRender::renderExpandableChild($model);
        } else {
            // Public properties.
            $model->setAdditional($label);
            return SkinRender::renderExpandableChild($model);
        }
    }

    /**
     * Dumps the constants of a class,
     *
     * @param \ReflectionClass $ref
     *   The already generated reflection of said class
     *
     * @return string
     *   The generated markup.
     */
    public static function getReflectionConstantsData(\ReflectionClass $ref)
    {
        // This is actually an array, we ara analysing. But We do not want to render
        // an array, so we need to process it like the return from an iterator.
        $refConst = $ref->getConstants();

        if (count($refConst) > 0) {
            // We've got some values, we will dump them.
            $model = new Constants();
            $model->setName('Constants')
                ->setType('class internals')
                ->addParameter('refConst', $refConst);

            return SkinRender::renderExpandableChild($model);
        }

        // Nothing to see here, return an empty string.
        return '';
    }


    /**
     * Render a dump for the properties of an array.
     *
     * @param array &$data
     *   The array we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public static function iterateThroughConstants(array &$data)
    {
        $model = new IterateThroughConstants();
        $model->addParameter('data', $data);

        return SkinRender::renderExpandableChild($model);
    }
}
