<?php

namespace Litvinenko\Combinatorics\Pdp;

use \Litvinenko\Combinatorics\Pdp\Point;
use \Litvinenko\Common\App;

class Pdp extends \Litvinenko\Common\Object
{
    protected function _construct()
    {
      if (!$this->hasData('avaliable_methods'))
      {
        $this->setData('avaliable_methods', ['gen','branch_bound']);
      }

      if (!$this->hasData('default_depot_coords'))
      {
        $this->setData('default_depot_coords', [200,200]);
      }
    }

    public function generateRandomData($pairCount)
    {
      $minRandomCoord     = 1;
      $maxRandomCoord     = 500000;
      $minRandomBoxSize   = 50;
      $maxRandomBoxSize   = 1;
      $minRandomBoxWeight = 100;
      $maxRandomBoxWeight = 1;

      $result = [];

      $result['depot'] = $this->getData('default_depot_coords');
      // $result[]
      for ($i = 1; $i <= ($pairCount*2); $i++)
      {
        // coords
        $result['points'][$i][0] = rand($minRandomCoord,$maxRandomCoord);
        $result['points'][$i][1] = rand($minRandomCoord,$maxRandomCoord);

        if ($i <= $pairCount)
        {
          // box dimensions
          $result['points'][$i][2] = rand($minRandomBoxSize,$maxRandomBoxSize);
          $result['points'][$i][3] = rand($minRandomBoxSize,$maxRandomBoxSize);
          $result['points'][$i][4] = rand($minRandomBoxSize,$maxRandomBoxSize);

          // box weight
          $result['points'][$i][5] = rand($minRandomBoxWeight,$maxRandomBoxWeight);
        }
      }

      return $result;
    }

    public function writePdpDataToFile($data, $filename)
    {
      // $content = count($data) . PHP_EOL;

      // $i = 1;
      // foreach ($data as $row)
      // {
      //     $content .= $i++ . ' ' . implode(' ', $row) . PHP_EOL;
      // }

      // $depotData = (isset($data['depot'])) ? $data['depot'] : $this->getDepotCoords();
      // $content .= 'depot ' . implode(' ', $depotData);

      // file_put_contents($filename, $content);
      file_put_contents($filename,json_encode($data,JSON_FORCE_OBJECT|JSON_PRETTY_PRINT));
      return true;
    }

    public function readPdpDataFromFile($filename)
    {
      if (!file_exists($filename)) throw new \Exception("File $filename does not exist!");
      return json_decode(file_get_contents($filename), true);
    }

    protected static function createPointsFromArray($data)
    {
        $points = [];
        $pairCount = count($data)/2;

        $i = 1;
        foreach ($data as $display_id => $pointInfo)
        {
            $id = ($display_id == 0) ? Point::DEPOT_ID : $i++;
            $newPoint = new Point([
                'id'                  => $id,
                'display_id'          => $display_id,
                'x'                   => floatval($pointInfo[0]),
                'y'                   => floatval($pointInfo[1]),
                'box_dimensions'      => isset($pointInfo[2]) ? ['x' => floatval($pointInfo[2]) , 'y' => floatval($pointInfo[3]), 'z' => floatval($pointInfo[4])] : null,
                'box_weight'          => isset($pointInfo[5]) ? floatval($pointInfo[5]) : null,
                'combinatorial_value' => $id,
            ]);

            if ($id == Point::DEPOT_ID)
            {
                $newPoint->setType(Point::TYPE_DEPOT);
                $newPoint->setPairId(Point::DEPOT_ID);
                $newPoint->setBoxWeight(0);
                $depot = $newPoint;
            }
            else
            {
                $isPickup = ($id <= $pairCount);
                $newPoint->addData([
                    'type'    => $isPickup ? Point::TYPE_PICKUP : Point::TYPE_DELIVERY,
                    'pair_id' => $isPickup ? ($id + $pairCount)   : ($id - $pairCount),
                    ]);
            }
            $points[$id] = $newPoint;

        }

        // assign to each delivery point box weight = -1*<box weight of correspoding pickup>
        // also validate all points
        foreach($points as $point)
        {
            if ($point->getType() == Point::TYPE_DELIVERY)
            {
                $point->setBoxWeight(- $points[$point->getPairId()]->getBoxWeight());
                $point->setBoxDimensions($points[$point->getPairId()]->getBoxDimensions());
            }

            if ($point->isInvalid())
            {
                throw new \Exception ("Point #" . $point->getId() . " is invalid: " . print_r($point->getValidationErrors(), true));
            }
        }

        return (count($points)>1) ? $points : reset($points);
    }

  public function getDummySolution()
  {
      return [
          'path'          => explode(' ','d u m m y _ p a t h'),
          'path_cost'     => 99999999999,
          'solution_time' => 0,
          'info'      => [
              'total_generated_paths' => 123,
          ],
          'errors' => ['dummy solution']
      ];
  }

  public function getSolution($data, $config, $method = 'gen')
  {
      App::resetSingletons();

      $result = [];
      try
      {
          if (in_array($method, array_keys($this->getAvaliableMethods())))
          {
              $result = $this->{'solve'.ucwords($method)}($data, $config);
          }
          else
          {
              throw new \Exception("Invalid solution method '{$method}'. Avaliable methods are '" . implode("','", array_keys($this->getAvaliableMethods())) . "'");
          }
      }
      catch(\Exception $e)
      {
          $result['errors'][] = $e->getMessage();
      }

      return $result;
  }

  public function solveGen($data, $config)
  {
    // $pdpPointsFile  = '../pdp_points.txt';
    // $pdpConfigFile  = '../pdp_config.ini';
    $solverClass    = '\Litvinenko\Combinatorics\Pdp\Solver\PreciseGenerationSolver';
    $metricsClass   = '\Litvinenko\Combinatorics\Pdp\Metrics\EuclideanMetric';
    $evaluatorClass = '\Litvinenko\Combinatorics\Pdp\Evaluator\PdpEvaluator';
    $generationLogFile = '';
    $solutionLogFile = 'solution.txt';

    // $pdpPoints = \Litvinenko\Combinatorics\Pdp\IO::readPointsFromFile($pdpPointsFile);
    // $pdpConfig = \Litvinenko\Combinatorics\Pdp\IO::readConfigFromIniFile($pdpConfigFile);
    // var_dump($pdpPoints);

    $solver = App::getSingleton($solverClass);
    $solver->_construct();

    $solver->addData(array_merge($config, [
        'depot'     => self::createPointsFromArray(array(0 => $data['depot'])),
        'points'    => self::createPointsFromArray($data['points']),
        'evaluator' => new $evaluatorClass(['metrics'   => new $metricsClass])
        ])
    );

    // echo "<pre>\n";
    try
    {
        App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper\Time')->start();
        $bestPath = $solver->getSolution();
        $solutionTime = App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper\Time')->getTimeFromStart();
        // printf('Solution was obtained in %.4F seconds', $solutionTime);

        $totalGeneratedPaths = count($solver->getGeneratedPointSequences());
        // echo "\n\ntotal paths generated:" .  $totalGeneratedPaths . "\n";
        // App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper\Time')->start();

        // if ($pdpConfig['log_solution'] && $solutionLogFile)
        // {
        //     $log =  "-------------- all paths at last step:\n";
        //     foreach ($solver->getGeneratedPointSequences() as $pointSequence)
        //     {
        //         $log .= IO::getPathAsText($pointSequence) . ' ' . $solver->_getCost($pointSequence) .   "\n";
        //     }

        //     $log .= "\n\n-------------not loaded paths:\n";
        //     foreach (App::getSingleton('\SolutionInfoCollector')->getNotLoadedPaths() as $pointSequence)
        //     {
        //         $log .= IO::getPathAsText($pointSequence) . ' ' . $solver->_getCost($pointSequence) .   "\n";
        //     }
        //     file_put_contents($solutionLogFile, $log);
        // }

        $bestCost = $solver->_getCost($bestPath);
        // echo "\n\nfinal path: " . IO::getPathAsText($bestPath) . " with cost " . $bestCost . "\n";


        // printf('All other operations took %.4F seconds', App::getSingleton('\Litvinenko\Combinatorics\Pdp\Helper\Time')->getTimeFromStart());

        $result = [
            'path'          => $bestPath->getPointsParams('display_id'),
            'path_cost'     => $bestCost,
            'solution_time' => $solutionTime,
            'info'      => [
                'total_generated_paths' => $totalGeneratedPaths,
            ]
        ];

    }
    catch (\Exception $e)
    {
        $result['errors'][] = "Exception occured: \n" . $e->getMessage();
    }

    // echo PHP_EOL . json_encode($result);

    return $result;
  }
}

function write_php_ini($array, $file)
{
    $res = array();
    foreach($array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval)
            {
              if(is_array($sval))
              {
                foreach($sval as $thirdLevelKey => $thirdLevelVal)
                {
                  $res[] = "{$skey}[{$thirdLevelKey}] = ". getIniValue($thirdLevelVal);
                }
              }
              else
              {
                $res[] = "$skey = ".getIniValue($sval);
              }
            }
        }
        else $res[] = "$key = ".getIniValue($val);
    }
    safefilerewrite($file, implode("\r\n", $res));
}

function getIniValue($value)
{
  if ($value === "1") return 'yes';
  if ($value === "") return 'no';
  if (is_numeric($value)) return $value;
  return '"'.$value.'"';
}

function safefilerewrite($fileName, $dataToSave)
{    if ($fp = fopen($fileName, 'w'))
    {
        $startTime = microtime();
        do
        {            $canWrite = flock($fp, LOCK_EX);
           // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
           if(!$canWrite) usleep(round(rand(0, 100)*1000));
        } while ((!$canWrite)and((microtime()-$startTime) < 1000));

        //file was locked so now we can store information
        if ($canWrite)
        {            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

}