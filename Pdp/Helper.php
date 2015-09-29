<?php
namespace Litvinenko\Combinatorics\Pdp;

use Litvinenko\Combinatorics\Pdp\IO;
use Litvinenko\Combinatorics\Pdp\Point;

class Helper extends \Litvinenko\Common\Object
{
    const LOG_FILE       = 'log/system.log';
    const ERROR_LOG_FILE = 'log/error.log';

    protected $dataRules = array(
        'log_errors'       => 'boolean',
        'throw_exceptions' => 'boolean'
    );


    protected function _construct()
    {
        if (!$this->hasLogErrors()) $this->setLogErrors(true);
        if (!$this->hasThrowExceptions()) $this->setThrowExceptions(true);
    }

    public static function log($message)
    {
        // file_put_contents(self::LOG_FILE, date("Y-m-d H:i:s") . ": $message\n", FILE_APPEND);
    }

    public static function logError($message)
    {
        // file_put_contents(self::ERROR_LOG_FILE, date("Y-m-d H:i:s") . " Error occured:\n$message\n", FILE_APPEND);
    }

    public function validateObjects($objects)
    {
        if (!is_array($objects))
        {
            $objects = array($objects);
        }

        foreach($objects as $object)
        {
            if (!($object instanceof \Litvinenko\Common\Object))
            {
                $message = 'given ' . gettype($object) . ' is not instance of \Litvinenko\Common\Object';
                if ($this->getThrowExceptions()) throw new \Exception($message);
                if ($this->getLogErrors()) self::logError($message);
            }
            else
            {
                try
                {
                    $object->validate();
                }
                catch (\Litvinenko\Common\Object\Exception $e)
                {
                    if ($this->getThrowExceptions()) throw new \Exception($e->getMessage());
                    if ($this->getLogErrors()) self::logError($e->getMessage());
                }
            }
        }
    }

    /**
     * Helper function for packing points to data neede for generator
     *
     * @param  array $points
     *
     * @return array
     */
    public static function getGeneratorDataFromPoints(array $points)
    {
        $result = [];
        foreach ($points as $point)
        {
            $result[] = [
                'id'                  => $point->getId(),
                'value'               => $point,
                'combinatorial_value' => $point->getCombinatorialValue()
            ];
        }

        return $result;
    }

    public static function getPointSequenceFromTuple($tuple)
    {
        return array_column($tuple, 'value');
    }

    public static function getPointSequencesFromGeneratorData($generatorData)
    {
        $result = [];
        foreach ($generatorData as $tuple)
        {
            $sequence = [];
            $result[] = self::getPointSequenceFromTuple($tuple);//array_column($tuple, 'value');
            // foreach ($tuple as $point)
            // {
            //     $sequence[] = $point['value'];
            // }
            // $result[] = $sequence;
        }

        return $result;
    }

    public static function getPickupsFromPointSequence($pointSequence)
    {
        $result = [];
        foreach ($pointSequence as $point)
        {
            if ($point->isPickup())
            {
                $result[] = $point;
            }
        }

        return $result;
    }

    public static function removeDepotFromPointSequence($pointSequence)
    {
        $result = [];
        foreach ($pointSequence as $point)
        {
            if (!$point->isDepot())
            {
                $result[] = $point;
            }
        }

        return $result;
    }

    public static function pointSequenceIncludesAllPickupsAndDeliveries($pointSequence, $allPoints)
    {
        return (count( self::removeDepotFromPointSequence($pointSequence) ) == count( self::removeDepotFromPointSequence($allPoints)) ) ;
    }

    public static function getPickupsAndDeliveriesCount($pointSequence)
    {
        return (count( self::removeDepotFromPointSequence($pointSequence) )) ;
    }

    public function canLoad($pointSequence, $pythonFile, $loadArea, $weightCapacity, $allPoints)
    {
        $result = false;

        $points = self::removeDepotFromPointSequence($pointSequence);
        $boxFileName = dirname($pythonFile).'/boxes.txt';

        if (!$this->getBoxesFileIsFilled())
        {
            file_put_contents($boxFileName, IO::getBoxesTextForExternalPdpHelper($allPoints));
            $this->setBoxesFileIsFilled(true);
        }

        if (!file_exists($pythonFile)) throw new \Exception("Python file '$pythonFile' does not exist!");

        $cmdString = "python $pythonFile" .
                        " -b {$boxFileName}" .
                        " -n "   . (int)(count($allPoints)/2) .
                        " -c \"" . implode(' ', $loadArea) . ' ' . $weightCapacity . "\"" .
                        " -r \""  . implode(' ', Point::getPointIds($points)) . "  1\"" .
                        " -p";
        $cmdResult = exec($cmdString);
      //  echo $cmdResult . "\n";
        $result = ($cmdResult == 'True');

        return $result;
    }

    public static function getLoadAreaVolume($loadArea)
    {
        return floatval($loadArea['x']) * floatval($loadArea['y']) * floatval($loadArea['z']);
    }
}