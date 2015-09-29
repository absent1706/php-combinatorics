<?php

namespace Litvinenko\Combinatorics\Common\Generators\Recursive\RegularSet;

class PermutationWithRepetitionsGenerator extends RegularSetGenerator
{
    protected $dataRules = array(
        'generating_elements' => 'required|array',
        'tuple_length'        => 'required|integer_strict',
        'initial_object'      => 'not_null|array',
        'enable_logs' => 'boolean'
    );

    protected function _getSuccessiveElements($tuple)
    {
        return $this->getGeneratingElements();
    }
}