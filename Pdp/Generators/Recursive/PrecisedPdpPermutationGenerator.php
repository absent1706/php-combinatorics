<?php
namespace Litvinenko\Combinatorics\Pdp\Generators\Recursive;

use Litvinenko\Common\App;
class PrecisedPdpPermutationGenerator extends PdpPermutationGenerator
{
    protected $dataRules = array(
        'generating_elements' => 'required|array',
        'tuple_length'        => 'required|integer_strict',
        'initial_object'      => 'not_null|array',

        'enable_logs' => 'boolean',

        // PDP specific params
        'weight_capacity' => 'required|float_strict',                       // vehicle weight capacity
        'load_area'       => 'required|array',                              // vehicle load area
        // this class specific params
        'precise' => 'required|float_strict',
        'metrics' => 'required|object:\Litvinenko\Combinatorics\Pdp\Metrics\AbstractMetric',

        'log_steps' => 'required|boolean'
    );

    protected function _getSuccessiveElements($tuple)
    {
        $result = [];

        if ($allCandidates = parent::_getSuccessiveElements($tuple))
        {
            $childrenCount = max(1, round($this->getPrecise() * count($allCandidates)/100));
            $result = $this->_getNElementsNearestTo(last($tuple), $allCandidates, $childrenCount);
        }

        return $result;
    }


    protected function _generate($object)
    {
        if ($this->objectIsComplete($object))
        {
            $this->_data['generatedObjects'][] = $object;
        }
        else
        {
            foreach ($this->generateNextObjects($object) as $nextObject)
            {
                if ($this->getLogSteps())
                {
                    App::dispatchEvent("new_path_generated", ['tuple' => $nextObject]);
                }
                $this->_generate($nextObject);
            }
        }
    }

    protected function _getTuplePointIds($tuple)
    {
        $result = [];
        foreach($tuple as $element)
        {
            $result[] = $element['value']->getId();
        }

        return $result;
    }

    protected function _getNElementsNearestTo($targetElement, $allElements, $n)
    {
        $metrics = $this->getMetrics();

        $distancesToTarget = [];

        // create assoc array where keys are element ids
        $allElements = array_combine(array_column($allElements, 'id'), array_values($allElements));
        foreach ($allElements as $elementId => $element)
        {
            $distancesToTarget[strval($elementId)] = $metrics->getDistanceBetweenPoints($element['value'], $targetElement['value']);
            // we keep string indexes in array because we want to sort array with PHP functuion asort which clears numeric keys but oreserve string ones
        }

        asort($distancesToTarget); // sort element ids by distance
        $result = [];
        foreach (array_slice($distancesToTarget, 0, $n, true) as $elementIdString => $distance)
        {
            $result[] = $allElements[intval($elementIdString)];
        }

        return $result;
    }
}