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
use Brainworxx\Krexx\Service\Config\Fallback;

trait SingleEditableChild
{
    /**
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        $domId = $model->getDomid();
        $name = $model->getName();
        $type = $model->getType();

        $element = str_replace(
            [
                static::MARKER_ID,
                static::MARKER_VALUE,
            ],
            [
                $domId,
                $name
            ],
            $this->getTemplateFileContent('single' . $type)
        );
        $options = '';

        // For dropdown elements, we need to render the options.
        if ($type === Fallback::RENDER_TYPE_SELECT) {
            // Here we store what the list of possible values.
            if ($domId === Fallback::SETTING_SKIN) {
                // Get a list of all skin folders.
                $valueList = $this->pool->config->getSkinList();
            } else {
                $valueList = ['true', 'false'];
            }

            // Paint it.
            foreach ($valueList as $value) {
                if ($value === $name) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $options .= str_replace(
                    [static::MARKER_TEXT, static::MARKER_VALUE, static::MARKER_SELECTED],
                    [$value, $value, $selected],
                    $this->getTemplateFileContent(static::FILE_SI_SELECT_OPTIONS)
                );
            }
        }

        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_SOURCE,
                static::MARKER_NORMAL,
                static::MARKER_TYPE,
                static::MARKER_HELP,
            ],
            [
                $model->getData(),
                $model->getNormal(),
                str_replace(static::MARKER_OPTIONS, $options, $element),
                Fallback::RENDER_EDITABLE,
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_SI_EDIT_CHILD)
        );
    }
}
