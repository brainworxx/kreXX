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

namespace Brainworxx\Krexx\Service\Config;

/**
 * Security measures for the configuration
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Security extends Fallback
{

    /**
     * Evaluate a single setting from the cookies or the ini file.
     *
     * @param string $group
     *   The group value in the ini.
     * @param string $name
     *   The name of the setting.
     * @param string $value
     *   The value to evaluate.
     *
     * @return bool
     *   If it was evaluated.
     */
    public function evaluateSetting($group, $name, $value)
    {
        if ($group === 'feEditing') {
            // Logging options can never be changed in the frontend.
            // The debug methods will also not be editable.
            if (in_array($name, $this->feConfigNoEdit)) {
                return false;
            } else {
                return true;
            }
        }


        // We must evaluate it.
        $result = false;
        switch ($name) {
            case 'analyseMethodsAtall':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage($this->storage->messages->getHelp('configErrorMethods'));
                    $this->storage->messages->addKey('methods.analyseMethodsAtall.error');
                }
                break;

            case 'analyseProtectedMethods':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorMethodsProtected')
                    );
                    $this->storage->messages->addKey('methods.analyseProtectedMethods.error');
                }
                break;

            case 'analysePrivateMethods':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorMethodsPrivate')
                    );
                    $this->storage->messages->addKey('methods.analysePrivateMethods.error');
                }
                break;

            case 'analyseProtected':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorPropertiesProtected')
                    );
                    $this->storage->messages->addKey('properties.analyseProtected.error');
                }
                break;

            case 'analysePrivate':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorPropertiesPrivate')
                    );
                    $this->storage->messages->addKey('properties.analysePrivate.error');
                }
                break;

            case 'analyseConstants':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorPropertiesConstants')
                    );
                    $this->storage->messages->addKey('properties.analyseConstants.error');
                }
                break;


            case 'analyseTraversable':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorTraversable')
                    );
                    $this->storage->messages->addKey('properties.analyseTraversable.error');
                }
                break;

            case 'debugMethods':
                // String that can get exploded, separated by a comma,
                // might as well be a single function.
                // We are not going to check this one.
                $result = true;
                break;

            case 'level':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorLevel')
                    );
                    $this->storage->messages->addKey('runtime.level.error');
                }
                break;

            case 'maxCall':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorMaxCall')
                    );
                    $this->storage->messages->addKey('runtime.maxCall.error');
                }
                break;

            case 'disabled':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorDisabled')
                    );
                    $this->storage->messages->addKey('runtime.disabled.error');
                }
                break;

            case 'detectAjax':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorDetectAjax')
                    );
                    $this->storage->messages->addKey('runtime.detectAjax.error');
                }
                break;

            case 'destination':
                // We expect 'frontend' or 'file'
                if ($value === 'frontend' || $value === 'file') {
                    $result = true;
                }
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorDestination')
                    );
                    $this->storage->messages->addKey('output.destination.error');
                }
                break;

            case 'maxfiles':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorMaxfiles')
                    );
                    $this->storage->messages->addKey('output.maxfiles.error');
                }
                break;

            case 'folder':
                // Directory with write access.
                // We also need to check, if the folder is properly protected.
                $isWritable = is_writable($this->storage->config->krexxdir . $value);
                $isProtected = $this->isFolderProtected($this->storage->config->krexxdir . $value);
                if ($isWritable && $isProtected) {
                    $result = true;
                }
                if (!$isWritable) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorFolderWritable')
                    );
                    $this->storage->messages->addKey('output.folder.error.writable');
                }
                if (!$isProtected) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorFolderProtection')
                    );
                    $this->storage->messages->addKey('output.folder.error.protected');
                }
                break;

            case 'skin':
                // We check the directory and one of the files for readability.
                if (is_readable($this->krexxdir . 'resources/skins/' . $value . '/header.html')) {
                    $result = true;
                }
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorSkin')
                    );
                    $this->storage->messages->addKey('output.skin.error');
                }
                break;

            case 'Local open function':
                // The Developer handle, we check it for values that are not
                // a-z and A-Z.
                $devHandle = preg_match('/[^a-zA-Z]/', $value);
                if (empty($devHandle)) {
                    $result = true;
                } else {
                    $result = false;
                }
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorHandle')
                    );
                    $this->storage->messages->addKey('output.haqndle.error');
                }
                break;

            case 'traceFatals':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorTraceFatals')
                    );
                    $this->storage->messages->addKey('errorHandling.traceFatals.error');
                }
                break;

            case 'traceWarnings':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorTraceWarnings')
                    );
                    $this->storage->messages->addKey('errorHandling.traceWarnings.error');
                }
                break;

            case 'traceNotices':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorTraceNotices')
                    );
                    $this->storage->messages->addKey('errorHandling.traceNotices.error');
                }
                break;

            case 'registerAutomatically':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorRegisterAuto')
                    );
                    $this->storage->messages->addKey('backtraceAndError.registerAutomatically.error');
                }
                // We also expect the php version to be lower than 7.
                if ($result) {
                    $result = $this->evalPhp();
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->messages->getHelp('configErrorPhp7')
                        );
                        $this->storage->messages->addKey('backtraceAndError.registerAutomatically.php7');
                    }
                }
                break;

            case 'backtraceAnalysis':
                // We expect "normal" or "deep"
                if ($value === 'normal' || $value === 'deep') {
                    $result = true;
                }
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorBacktraceAnalysis')
                    );
                    $this->storage->messages->addKey('backtraceAndError.backtraceAnalysis.error');
                }
                break;

            case 'memoryLeft':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->storage->messages->addMessage(
                        $this->storage->messages->getHelp('configErrorMemory')
                    );
                    $this->storage->messages->addKey('runtime.memoryLeft.error');
                }
                break;

            case 'maxRuntime':
                // We expect an integer not greater than the max runtime of the
                // server.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->storage->messages->addMessage($this->storage->messages->getHelp('configErrorMaxRuntime'));
                    $this->storage->messages->addKey('runtime.maxRuntime.error');
                } else {
                    // OK, we got an int, now to see if it is smaller than the
                    // configured max runtime.
                    $maxTime = (int)ini_get('max_execution_time');
                    $value = (int)$value;
                    if ($maxTime > 0 && $maxTime < $value) {
                        // Too big!
                        $this->storage->messages->addMessage(
                            $this->storage->messages->getHelp('configErrorMaxRuntimeBig1') .
                            $maxTime .
                            $this->storage->messages->getHelp('configErrorMaxRuntimeBig2')
                        );
                        $this->storage->messages->addKey('runtime.maxRuntime.error.maximum', array($maxTime));
                        $result = false;
                    }
                }
                break;

            default:
                // Unknown settings,
                // return false, just in case.
                break;
        }

        return $result;
    }

    /**
     * Evaluates a string of 'true' or 'false'.
     *
     * @param string $value
     *   The string we want to evaluate.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalBool($value)
    {
        if ($value === 'true' || $value === 'false') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the php version is lower then 7.0.0.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalPhp()
    {
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Evaluates a string of integer.
     *
     * It must be greater than 0 and smaller than 101.
     *
     * @param string $value
     *   The string we want to evaluate.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalInt($value)
    {
        $value = (int)$value;
        if ($value > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks for a .htaccess file with a 'deny from all' statement.
     *
     * @param string $path
     *   The path we want to check.
     *
     * @return bool
     *   Whether the path is protected.
     */
    protected function isFolderProtected($path)
    {
        $result = false;
        if (is_readable($path . '/.htaccess')) {
            $content = file($path . '/.htaccess');
            foreach ($content as $line) {
                // We have what we are looking for, a
                // 'deny from all', not to be confuse with
                // a '# deny from all'.
                if (strtolower(trim($line)) === 'deny from all') {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Determines if a debug function is blacklisted in s specific class.
     *
     * @param object $data
     *   The class we are analysing.
     * @param string $call
     *   The function name we want to call.
     *
     * @return bool
     *   Whether the function is allowed to be called.
     */
    public function isAllowedDebugCall($data, $call)
    {

        foreach ($this->debugMethodsBlacklist as $classname => $method) {
            if (is_a($data, $classname) && $call === $method) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }
}
