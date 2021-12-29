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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Comment\Properties;
use Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class properties' analysis methods.
 *
 * @uses array data
 *   Array of \ReflectionProperties.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class ThroughProperties extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * Renders the properties of a class.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        $pool = $this->pool;
        $messages = $pool->messages;

        // I need to preprocess them, since I do not want to render a
        // reflection property.
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        /** @var PropertyDeclaration $propertyRetrieval */
        $propertyRetrieval = $this->pool->createClass(PropertyDeclaration::class);

        foreach ($this->parameters[static::PARAM_DATA] as $refProperty) {
            // Check memory and runtime.
            if ($pool->emergencyHandler->checkEmergencyBreak() === true) {
                return '';
            }

            // Stitch together our model.
            $value = $ref->retrieveValue($refProperty);
            $output .= $pool->routing->analysisHub(
                $this->dispatchEventWithModel(__FUNCTION__ . static::EVENT_MARKER_END, $pool->createClass(Model::class)
                    ->setData($value)
                    ->setName($this->retrievePropertyName($refProperty))
                    ->addToJson(
                        $messages->getHelp('metaComment'),
                        $pool->createClass(Properties::class)->getComment($refProperty)
                    )
                    ->addToJson($messages->getHelp('metaDeclaredIn'), $propertyRetrieval->retrieveDeclaration($refProperty))
                    ->setAdditional($this->getAdditionalData($refProperty, $ref))
                    ->setConnectorType($this->retrieveConnector($refProperty))
                    ->setCodeGenType($refProperty->isPublic() ? static::CODEGEN_TYPE_PUBLIC : ''))
            );
        }

        return $output;
    }

    /**
     * Retrieve the connector type, depending on the property properties
     *
     * @param \ReflectionProperty $refProperty
     *   Reflection of the property we are analysing.
     *
     * @return string
     *   The connector-type.
     */
    protected function retrieveConnector(ReflectionProperty $refProperty): string
    {
        $connectorType = static::CONNECTOR_NORMAL_PROPERTY;

        if ($refProperty->isStatic() === true) {
            $connectorType = static::CONNECTOR_STATIC_PROPERTY;
        } elseif (
            isset($refProperty->isUndeclared) === true &&
            $this->pool->encodingService->isPropertyNameNormal($refProperty->getName()) === false
        ) {
            // This one was undeclared and does not follow the standard naming
            // conventions of PHP. Maybe something for a rest service?
            $connectorType = static::CONNECTOR_SPECIAL_CHARS_PROP;
        }

        return $connectorType;
    }

    /**
     * Retrieval of the property name, and processing it.
     *
     * @param \ReflectionProperty $refProperty
     *   Reflection of the property we are analysing.
     *
     * @return string
     *   The processed property name.
     */
    protected function retrievePropertyName(ReflectionProperty $refProperty): string
    {
        $propName = $refProperty->getName();
        // Static properties are very special.
        if ($refProperty->isStatic() === true) {
            // There is always a $ in front of a static property.
            $propName = '$' . $propName;
        } elseif (
            isset($refProperty->isUndeclared) === true &&
            $this->pool->encodingService->isPropertyNameNormal($refProperty->getName()) === false
        ) {
            // There can be anything in there. We must take special preparations
            // for the code generation.
            $propName = $this->pool->encodingService->encodeString($propName);
        }

        // And  encode it, just in case.
        return $propName;
    }

    /**
     * Adding declaration keywords to our data in the additional field.
     *
     * @param \ReflectionProperty $refProperty
     *   A reflection of the property we ara analysing.
     * @param \ReflectionClass $ref
     *   A reflection of the class we are analysing.
     *
     * @return string
     */
    protected function getAdditionalData(ReflectionProperty $refProperty, ReflectionClass $ref): string
    {
        // Now that we have the key and the value, we can analyse it.
        // Stitch together our additional info about the data:
        // public access, protected access, private access, static declaration.
        $additional = '';

        if ($refProperty->isProtected() === true) {
            $additional .= 'protected ';
        } elseif ($refProperty->isPublic() === true) {
            $additional .= 'public ';
        } elseif ($refProperty->isPrivate() === true) {
            $additional .= 'private ';
        }

        if (empty($refProperty->isUnset) === false) {
            // This one was unset during runtime.
            // We need to tell the dev. Accessing an unset property may trigger
            // a warning.
            $additional .= 'unset ';
        }

        // Test if the property is inherited or not by testing the
        // declaring class
        if ($refProperty->getDeclaringClass()->getName() !== $ref->getName()) {
            // This one got inherited fom a lower level.
            $additional .= 'inherited ';
        }

        // Add the info, if this is static.
        if ($refProperty->isStatic() === true) {
            $additional .= 'static ';
        }

        if (isset($refProperty->isUndeclared) === true) {
            // The property 'isUndeclared' is not a part of the reflectionProperty.
            // @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
            $additional .= 'dynamic property ';
        }

        return $additional;
    }

    /**
     * Retrieve the declaration place of a property.
     *
     * @param \ReflectionProperty $refProperty
     *   A reflection of the property we are analysing.
     *
     * @deprecated since 5.0.0
     *   Will be removed. Use PropertyDeclaration instead.
     * @codeCoverageIgnore
     *   We do not test deprecated methods.
     *
     * @return string
     *   Human-readable string, where the property was declared.
     */
    protected function retrieveDeclarationPlace(ReflectionProperty $refProperty): string
    {
        return $this->pool->createClass(PropertyDeclaration::class)
            ->retrieveDeclaration($refProperty);
    }
}
