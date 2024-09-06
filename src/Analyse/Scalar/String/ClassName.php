<?php

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Throwable;

class ClassName extends AbstractScalarAnalysis
{
    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Is always active
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * @param string $string
     *   The possible class name we are looking at
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     *
     * @return bool
     *   Is this a class name?
     */
    public function canHandle($string, Model $model): bool
    {
        set_error_handler($this->pool->retrieveErrorCallback());
        try {
            if (class_exists($string)) {
                $this->handledValue = $string;
                $this->model = $model;
                restore_error_handler();
                return true;
            }
        } catch (Throwable $throwable) {
        }

        restore_error_handler();
        return false;
    }

    /**
     * Add the decoded json and a pretty-print-json to the output.
     *
     * @return string[]
     *   The array for the meta callback.
     */
    protected function handle(): array
    {
        $messages = $this->pool->messages;
        $meta = [
            $messages->getHelp('metaReflection') => new ReflectionClass($this->handledValue)
        ];

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[$messages->getHelp('metaContent')] = $this->model->getData();
        }

        return $meta;
    }
}
