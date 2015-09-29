<?php
namespace Litvinenko\Combinatorics\Common;

abstract class RegularSetGenerationSolver extends AbstractSolver
{
    protected $dataRules = array(
        'maximize_cost' => 'required|boolean',

        'precise'       => 'required|float_strict',
        'generator'     => 'required|object:\Litvinenko\Combinatorics\Common\Generators\Recursive\RegularSetGenerator'
    );

    public function getSolution()
    {

    }
}