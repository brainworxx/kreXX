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

namespace {

    use phpmock\phpunit\PHPMock;

    define('KREXX_TEST_IN_PROGRESS', true);

    // Make sure, that we are able to mock the living hell out of this baby.
    PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
    PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Factory\\', 'is_writable');
    PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'ini_get');
    PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'time');
    PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'file_put_contents');

    // Register a shutdown method to die, so we get no output on the shell.
    register_shutdown_function(function () {
        die();
    });
}

namespace Brainworxx\Krexx\Service\Misc {

    /**
     * Mocking the file unlinking. We also store called parameters and return
     * them when we are done mocking.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return array|bool
     */
    function unlink(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;
        // Remembering the parameters right here.
        static $parameters = [];

        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
                return true;
            }

            if ($startMock === false) {
                return $parameters;
            }
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \unlink($filename);
    }

    /**
     * Simply mocking the chmod function.
     *
     * @param string $filename
     * @param $mode
     * @param bool|null $startMock
     *
     * @return array|bool
     */
    function chmod(string $filename, $mode, bool $startMock = null)
    {
        static $mockingInProgress = false;
        // Remembering the parameters right here.
        static $parameters = [];

        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
            }

            if ($startMock === false) {
                return $parameters;
            }

            return true;
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \chmod($filename, $mode);
    }

    /**
     * Mocking the realpath.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return bool|string
     */
    function realpath(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return $filename;
        }

        return \realpath($filename);
    }

    /**
     * Mocking the is_file.
     *
     * @param string $filename
     * @param bool|null $startMock
     * @return bool|string
     */
    function is_file(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return true;
        }

        return \is_file($filename);
    }

    /**
     * Mocking the ir_readable function.
     *
     * @param string $filename
     * @param bool|null $startMock
     * @return bool|array
     */
    function is_readable(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;
        static $parameters = [];


        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
                return true;
            }

            if ($startMock === false) {
                return $parameters;
            }
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \is_readable($filename);
    }

    /**
     * Mocking the file time.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return bool|false|int
     */
    function filemtime(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return 42;
        }

        return \filemtime($filename);
    }

    /**
     * Mocking the time.
     *
     * Take that, time!
     *
     * @param bool|null $startMock
     *
     * @return int
     */
    function time(bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return 41;
        }

        return \time();
    }
}
