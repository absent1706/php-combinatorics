<?php
namespace Litvinenko\Combinatorics\Common\Evaluator;

abstract class AbstractEvaluator extends \Litvinenko\Common\Object
{
    const BOUND_TYPE_OPTIMISTIC  = 'optimistic';
    const BOUND_TYPE_PESSIMISTIC = 'pessimistic';

    protected $dataRules = array(
        'avaliable_bound_types' => 'required|array',
    );

    public function _construct()
    {
        $this->setAvaliableBoundTypes([self::BOUND_TYPE_OPTIMISTIC, self::BOUND_TYPE_PESSIMISTIC]);
    }

    public function getBound($object, $boundType, array $additionalInfo = array())
    {
        // $this->validate();
        if (in_array($boundType, $this->getAvaliableBoundTypes()))
        {
            return $this->_calculateBound($object, $boundType, $additionalInfo);
        }
    }

    abstract protected function _calculateBound($object, $boundType, array $additionalInfo = array());
}
