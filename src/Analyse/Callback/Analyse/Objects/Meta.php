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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Comment\Classes;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;

/**
 * Class Meta
 *
 * @uses ref \ReflectionClass
 *   Here we get all out data.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 */
class Meta extends AbstractObjectAnalysis
{
    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Meta';

    /**
     * Dump the Meta stuff from a class.
     *
     * - Fully qualified class name
     * - Class comment
     * - Filename and line from/to
     * - Implemented interfaces
     * - Class list from where the objects inherits stuff from
     * - Used traits
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        // We need to check, if we have a meta recursion here.
        $domId = $this->generateDomIdFromClassname($ref->getName());
        if ($this->pool->recursionHandler->isInMetaHive($domId) === true) {
            // We have been here before.
            // We skip this one, and leave it to the js recursion handler!
            return $output .
                $this->pool->render->renderRecursion(
                    $this->dispatchEventWithModel(
                        static::EVENT_MARKER_RECURSION,
                        $this->pool->createClass(Model::class)
                            ->setDomid($domId)
                            ->setNormal(static::META_CLASS_DATA)
                            ->setName(static::META_CLASS_DATA)
                            ->setType(static::TYPE_INTERNALS)
                    )
                );
        }

        return $this->analyseMeta($output, $domId, $ref);
    }

    /**
     * Do the actual analysis.
     *
     * @param $output
     *   The output so far.
     * @param $domId
     *   The dom id for the recursion handler.
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     *   The reflection class, the main source of information.
     *
     * @return string
     *   The generated DOM.
     */
    protected function analyseMeta($output, $domId, ReflectionClass $ref)
    {
        $this->pool->recursionHandler->addToMetaHive($domId);

        $data = [];
        if ($ref->isFinal() === true) {
            $data[static::META_CLASS_NAME] = 'final class ' . $ref->getName();
        } else {
            $data[static::META_CLASS_NAME] = $ref->getName();
        }

        $data[static::META_COMMENT] = $this->pool
            ->createClass(Classes::class)
            ->getComment($ref);

        if ($ref->isInternal()) {
            $data[static::META_DECLARED_IN] = 'n/a, is predeclared';
        } else {
            $data[static::META_DECLARED_IN] = $this->pool
                ->fileService
                ->filterFilePath($ref->getFileName()) .
                ', line ' . $ref->getStartLine() . ' to ' . $ref->getEndLine();
        }
        $interfaces = $ref->getInterfaceNames();
        if (empty($interfaces)) {
            $interfaces = 'n/a';
        }
        $data[static::META_INTERFACES] = $interfaces;

        // Now to collect the inheritance stuff.
        $previousClass = $ref->getParentClass();
        $classList = [];
        $traitList = [];
        while ($previousClass !== false) {
            $classList[] = $previousClass->getName();
            $traits = $previousClass->getTraitNames();
            if (empty($traits) === false) {
                $traitList = array_merge($traitList, $previousClass->getTraitNames());
            }
            $previousClass = $previousClass->getParentClass();
        }

        if (!empty($traitList)) {
            $data[static::META_TRAITS] = $traitList;
        }
        if (!empty($classList)) {
            $data[static::META_INHERITED_CLASSES] = $classList;
        }

        return $output .
            $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    static::EVENT_MARKER_ANALYSES_END,
                    $this->pool->createClass(Model::class)
                        ->setName(static::META_CLASS_DATA)
                        ->setDomid($domId)
                        ->setType(static::TYPE_INTERNALS)
                        ->addParameter(static::PARAM_DATA, $data)
                        ->injectCallback(
                            $this->pool->createClass(ThroughMeta::class)
                        )
                )
            );
    }

    /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is simply the md5 hash of the classname with the namespace.
     *
     * @param string $data
     *   The object name from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromClassname($data)
    {
        return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_c_' . md5($data);
    }
}
