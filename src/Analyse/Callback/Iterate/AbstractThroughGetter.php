<?php

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Helper methods for the getter iterator.
 */
abstract class AbstractThroughGetter extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * Class for the comment analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected Methods $commentAnalysis;

    /**
     * Injects the pool and initializes the comment analysis.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->commentAnalysis = $this->pool->createClass(Methods::class);
    }

    /**
     * Reset the parameters for every getter.
     *
     * We do this for the eventsystem, so a listener can gete additional data
     * from the current analysis process. Or the listener can inject stuff
     * here.
     *
     * @param \ReflectionMethod $reflectionMethod
     * @return void
     */
    protected function resetParameters(ReflectionMethod $reflectionMethod)
    {
        $this->parameters[static::PARAM_ADDITIONAL] = [
            static::PARAM_NOTHING_FOUND => true,
            static::PARAM_VALUE => null,
            static::PARAM_REFLECTION_PROPERTY => null,
            static::PARAM_REFLECTION_METHOD => $reflectionMethod
        ];
    }

    /**
     * Converts a camel case string to snake case.
     *
     * @author Syone
     * @see https://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case/35719689#35719689
     *
     * @param string $string
     *   The string we want to transform into snake case
     *
     * @return string
     *   The de-camelized string.
     */
    protected function convertToSnakeCase(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * Searching for stuff via regex.
     * Yay, dynamic regex stuff for fun and profit!
     *
     * @param string[] $searchArray
     *   The search definition.
     * @param string $haystack
     *   The haystack, obviously.
     *
     * @return string[]|int[]
     *   The findings.
     */
    protected function findIt(array $searchArray, string $haystack): array
    {
        $findings = [];
        preg_match_all(
            str_replace(
                ['###0###', '###1###'],
                [preg_quote($searchArray[0]), preg_quote($searchArray[1])],
                '/(?<=###0###).*?(?=###1###)/'
            ),
            $haystack,
            $findings
        );

        // Return the file name as well as stuff from the path.
        return $findings[0];
    }

    /**
     * Retrieve the property by name from a reflection class.
     *
     * @param string $propertyName
     *   The name of the property.
     * @param \ReflectionClass $parentClass
     *   The class where it may be located.
     *
     * @return \ReflectionProperty|null
     *   The reflection property, if found.
     */
    protected function retrievePropertyByName(string $propertyName, \ReflectionClass $parentClass): ?ReflectionProperty
    {
        while ($parentClass !== false) {
            // Check if it was declared somewhere deeper in the
            // class structure.
            if ($parentClass->hasProperty($propertyName)) {
                return $parentClass->getProperty($propertyName);
            }
            $parentClass = $parentClass->getParentClass();
        }

        return null;
    }
}
