<?php
namespace Litvinenko\Combinatorics\Pdp\Generators\Recursive;

use Litvinenko\Combinatorics\Pdp\Helper;
use Litvinenko\Combinatorics\Pdp\Path;
use Litvinenko\Common\App;
use Litvinenko\Common\Object;

class PdpPermutationGenerator extends \Litvinenko\Combinatorics\Common\Generators\Recursive\RegularSetGenerator
{
    protected $dataRules = array(
        'generating_elements' => 'required|array',
        'tuple_length'        => 'required|integer_strict',
        'initial_object'      => 'not_null|array',

        'enable_logs' => 'boolean',

        // PDP specific params
        'current_path'    => 'required|object:\Litvinenko\Combinatorics\Pdp\Path', // current PDP path
        'weight_capacity' => 'required|float_strict',                              // vehicle weight capacity
        'load_area'       => 'required|array',                              // vehicle load area
    );

    protected function _getSuccessiveElements($tuple)
    {
        $result = [];

        // we assume that tuple contain \Litvinenko\Combinatorics\Pdp\Point objects
        $currentPath  = $this->_getCurrentPath($tuple);
        foreach($this->getGeneratingElements() as $element)
        {
            $point = $element['value'];

            // if current path does not contain this point
            if (!$currentPath->doesContain($point))
            {
                     // add pickup point if whether vehicle can take box at this point
                if ( $point->isPickup() && (($currentPath->getCurrentWeight() + $point->getBoxWeight()) <= $this->getWeightCapacity())  && ( ($currentPath->getCurrentVolume() + $point->getBoxVolume()) <= Helper::getLoadAreaVolume($this->getLoadArea()) )
                     ||
                     // add delivery point if corresponding pickup ALREADY exists in current path
                     $point->isDelivery() && $currentPath->doesContain($point->getPairId())
                   )
                {
                    $resultContainer = new \stdClass(); // event observers will write info to this object
                    App::dispatchEvent('point_add_before', ['point' => $point, 'point_sequence' => $tuple, 'result_container' => $resultContainer]);

                    if (!isset($resultContainer->result) || isset($resultContainer->result) && ($resultContainer->result !== false))
                    {
                        $result[] = $element;
                    }
                }
            }
        }

        return $result;
    }

    protected function _getCurrentPath($tuple)
    {
        $result = null;
        //if (!($result = $this->getCurrentPath())) !!! Bug. Cache is not up to date!
        {
            $result = new Path(['points' => array_column($tuple, 'value')]);
        }

        return $result;
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

    public function objectIsComplete($tuple)
    {
        return ((count($tuple) - 1) == $this->getTupleLength()); // -1 because first pont is depot
    }
}