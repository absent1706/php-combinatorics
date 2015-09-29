<?php

namespace Litvinenko\Combinatorics\Common\Generators;

abstract class AbstractGenerator extends \Litvinenko\Common\Object
{
    protected $dataRules = array(
        'generating_elements' => 'not_null|array'
    );


    public function generateAll()
    {
        // if ($this->validate())
        // {
            return $this->_generateAll();
        // }
    }

    abstract protected function _generateAll();
}
