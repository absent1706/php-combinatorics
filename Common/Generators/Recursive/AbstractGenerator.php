<?php

namespace Litvinenko\Combinatorics\Common\Generators\Recursive;

abstract class AbstractGenerator extends \Litvinenko\Combinatorics\Common\Generators\AbstractGenerator
{
    protected $dataRules = array(
        'generating_elements' => 'required|array',
        'initial_object'      => 'not_null',

        'enable_logs' => 'boolean'
    );

    /**
     * Generates all child objects of given object and returns them
     *
     * @param  mixed $object
     *
     * @return array
     */
    abstract public function generateNextObjects($object);

    /**
     * Returns TRUE if object is full and can't have child objects (i.e., if we need to generate permutation of N objects and current permutation have N elements)
     *
     * @param  mixed $object
     *
     * @return bool
     */
    abstract public function objectIsComplete($object);

    /**
     * Returns ALL generated objects
     *
     * @return array
     */
    protected function _generateAll()
    {
        $this->_data['generatedObjects'] = [];

        $this->_beforeGenerateBegin();
        $this->_generate($this->getInitialObject());
        $this->_afterGenerateEnd();

        return $this->_data['generatedObjects'];
    }

    protected function _generate($object)
    {
        if ($this->objectIsComplete($object))
        {
            $this->_data['generatedObjects'][] = $object;
            $this->_afterObjectGenerate($object);
        }
        else
        {
            foreach ($this->generateNextObjects($object) as $nextObject)
            {
                $this->_generate($nextObject);
            }
        }
    }

    protected function _afterObjectGenerate($object)
    {
        //
    }

    protected function _beforeGenerateBegin()
    {
        //
    }

    protected function _afterGenerateEnd()
    {
        //
    }
}
