<?php

namespace Litvinenko\Combinatorics\Common\Generators\Recursive;

abstract class RegularSetGenerator extends AbstractGenerator
{
    /**
     * Generating_elements MUST be array, where each generating elements looks like
     * $someGeneratingElement = [
     *     'id'                  => <generating element UNIQUE ID. Needed because generating elements can have the same combinatorial value and we must differentiate them>,
     *     'value'               => <real object CORRESPONDING to generating element. Can be any (object/array/stream... whatever you like). NOT USED in generator, but used by its caller).>,
     *     'combinatorial_value' => <combinatorial value USED by generator. It's combinatorial representation of real object>
     * ];
     *
     * For example,
     * $someGeneratingElement = [
     *     'id'                  => $point->getId(),                // just ID
     *     'value'               => $point,                         // REAL point object
     *     'combinatorial_value' => $point->getCombinatorialValue() // combinatorial representation of real point. For point it can be point number (1,2,...)
     * ];
     *
     * @var array
     */
    protected $dataRules = array(
        'generating_elements' => 'not_null|array',
        'tuple_length'        => 'required|integer_strict',
        'initial_object'      => 'not_null|array',
        'enable_logs' => 'boolean'
    );

    abstract protected function _getSuccessiveElements($tuple);

    public function _construct()
    {
        parent::_construct();
        if (!$this->hasInitialObject()) $this->setInitialObject([]);
    }

    public function generateNextObjects($tuple)
    {
        $result = [];
        foreach ($this->_getSuccessiveElements($tuple) as $newElements)
        {
            // _getSuccessiveElements method can return 1 or more elements. If it return 1 element, pack in into array
            $newTuplePart = is_array(reset($newElements)) ? $newElements : [$newElements];

            // add new tuple part to end of current tuple
            $result[] = array_merge($tuple, $newTuplePart);
        }

        return $result;
    }

    public function objectIsComplete($tuple)
    {
        return (count($tuple) == $this->getTupleLength());
    }

    protected function _beforeGenerateBegin()
    {
        // /*if ($this->getEnableLogs())*/ file_put_contents('log.txt', date("Y-m-d H:i:s").PHP_EOL.PHP_EOL);
    }

    protected function _afterGenerateEnd()
    {
        // /*if ($this->getEnableLogs())*/ file_put_contents('log.txt', PHP_EOL.PHP_EOL.date("Y-m-d H:i:s"), FILE_APPEND);
    }

    protected function _afterObjectGenerate($object)
    {
        // /*if ($this->getEnableLogs())*/ file_put_contents('log.txt', implode(' ',var_export($object, true)/*array_column($object, 'combinatorial_value')*/).PHP_EOL, FILE_APPEND);
    }
}