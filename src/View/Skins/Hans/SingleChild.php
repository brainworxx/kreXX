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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

trait SingleChild
{
    /**
     * {@inheritdoc}
     */
    public function renderSingleChild(Model $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $partExpand = '';
        $partCallable = '';
        $partExtra = '';

        if ($model->getHasExtra() === true) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = 'kexpand';
            // Add the yellow box for large output text.
            $partExtra = str_replace(
                static::MARKER_DATA,
                $model->getData(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_EX)
            );
        }

        $normal = $model->getNormal();
        if ($model->getIsCallback() === true) {
            // Add callable partial.
            $partCallable = str_replace(
                static::MARKER_NORMAL,
                $normal,
                $this->getTemplateFileContent(static::FILE_SI_CHILD_CALL)
            );
        }

        // Stitching the classes together, depending on the types.
        $typeClasses = '';
        $modelTypes = $model->getType();
        foreach (explode(' ', $modelTypes) as $typeClass) {
            $typeClasses .= 'k' . $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->pool->codegenHandler->generateSource($model);

        if (empty($gensource) === true || $this->pool->codegenHandler->getAllowCodegen() === false) {
            // Remove the markers, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // We add the button and the code.
            $sourcebutton = $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
        }

        // Stitching it together.
        return str_replace(
            [
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_EXPAND,
                static::MARKER_CALLABLE,
                static::MARKER_EXTRA,
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_TYPE_CLASSES,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
                static::MARKER_HELP,
            ],
            [
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gensource),
                $sourcebutton,
                $partExpand,
                $partCallable,
                $partExtra,
                $model->getName(),
                $modelTypes,
                $typeClasses,
                $normal,
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight()),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_L,
                    $this->pool->codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_R,
                    $this->pool->codegenHandler->generateWrapperRight()
                ),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_SI_CHILD)
        );
    }
}
