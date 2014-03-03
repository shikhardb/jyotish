<?php
/**
 * @link      http://github.com/kunjara/jyotish for the canonical source repository
 * @license   GNU General Public License version 2 or later
 */

namespace Jyotish\Panchanga;

use DateTime;
use DateInterval;
use Jyotish\Ganita\Math;
use Jyotish\Ganita\Time;
use Jyotish\Panchanga\Tithi\Tithi;
use Jyotish\Panchanga\Nakshatra\Nakshatra;
use Jyotish\Panchanga\Yoga\Yoga;
use Jyotish\Panchanga\Vara\Vara;
use Jyotish\Panchanga\Karana\Karana;
use Jyotish\Graha\Graha;
use Jyotish\Calendar\Masa;

/**
 * Class to calculate the Panchanga.
 *
 * @author Kunjara Lila das <vladya108@gmail.com>
 */
class Panchanga {
	
	private $_ganitaObject;
	private $_userData;
	private $_paramsData;
	private $_risingData;
	
	private $_tithi = null;

	public function __construct($ganitaObject)
	{
		if($ganitaObject instanceof \Jyotish\Ganita\Method\Swetest){
			$this->_ganitaObject = $ganitaObject;
			$this->_setData();
			$this->_risingData = $this->_ganitaObject->getRising();
		} else {
			throw new Exception\InvalidArgumentException(
                'Ganita method must be Swetest object.'
            );
		}
	}
	
	public function __clone()
	{
		$this->_ganitaObject = clone $this->_ganitaObject;
	}

	/**
	 * Get Tithi
	 * 
	 * @param	boolean $withLimit
	 * @return	array
	 */
	public function getTithi($withLimit = false)
	{
		$unit = 12;
		
		$lonCh = $this->_paramsData['graha'][Graha::GRAHA_CH]['longitude'];
		$lonSy = $this->_paramsData['graha'][Graha::GRAHA_SY]['longitude'];		
		
		if($lonCh < $lonSy) $lonCh = $lonCh + 360;
		
		$tithiUnits = Math::partsToUnits(($lonCh - $lonSy), $unit);
		$tithiObject = Tithi::getInstance($tithiUnits['units']);
		
		$tithi['number'] = $tithiUnits['units'];
		$tithi['name'] = Tithi::$TITHI[$tithi['number']];
		$tithi['paksha'] = $tithiObject::$tithiPaksha;
		$tithi['left'] = ($unit - $tithiUnits['parts']) * 100 / $unit;
		
		if($withLimit){
			$limits = $this->_limitAnga($tithi, __FUNCTION__);
			$tithi['end'] = $limits['end'];
		}
		
		$this->_tithi = $tithi;
		
		return $this->_tithi;
	}
	
	/**
	 * Get Nakshatra
	 * 
	 * @param	boolean $withLimit
	 * @return	array
	 */
	public function getNakshatra($withLimit = false, $withAbhijit = false)
	{
		$unit = 360/27;
		
		$lonCh = $this->_paramsData['graha'][Graha::GRAHA_CH]['longitude'];
		$nakshatraUnits = Math::partsToUnits($lonCh, $unit);
		
		if($withAbhijit){
			if($nakshatraUnits['units'] == 21 or $nakshatraUnits['units'] == 22){
				$Abhijit = Nakshatra::getInstance(28);
				$abhijitStart	= Math::dmsToDecimal($Abhijit::$nakshatraStart);
				$abhijitEnd		= Math::dmsToDecimal($Abhijit::$nakshatraEnd);

				if($lonCh < $abhijitStart){
					$nakshatra['number'] = 21;
					$N = Nakshatra::getInstance($nakshatra['number']);
					$nStart = Math::dmsToDecimal($N::$nakshatraStart);
					$unit = $abhijitStart - $nStart;
					$left = $abhijitStart - $lonCh;
				}elseif($lonCh >= $abhijitStart and $lonCh < $abhijitEnd){
					$nakshatra['number'] = 28;
					$unit = $abhijitEnd - $abhijitStart;
					$left = $abhijitEnd - $lonCh;
				}else{
					$nakshatra['number'] = 22;
					$N = Nakshatra::getInstance($nakshatra['number']);
					$nEnd = Math::dmsToDecimal($N::$nakshatraEnd);
					$unit = $nEnd - $abhijitEnd;
					$left = $nEnd - $lonCh;
				}
				$nakshatra['ratio'] = $unit / Math::dmsToDecimal(Nakshatra::$nakshatraArc);
			}else{
				$nakshatra['number'] = $nakshatraUnits['units'];
				$nakshatra['ratio'] = 1;
				$left = $unit - $nakshatraUnits['parts'];
			}
			$nakshatra['abhijit'] = true;
		}else{
			$nakshatra['number'] = $nakshatraUnits['units'];
			$nakshatra['ratio'] = 1;
			$nakshatra['abhijit'] = false;
			$left = $unit - $nakshatraUnits['parts'];
		}
		
		$nakshatra['left'] = $left * 100 / $unit;
		$nakshatra['name'] = Nakshatra::$NAKSHATRA[$nakshatra['number']];
		
		if($withLimit){
			$limits = $this->_limitAnga($nakshatra, __FUNCTION__);
			$nakshatra['end'] = $limits['end'];
		}
		
		return $nakshatra;
	}
	
	/**
	 * Get Yoga
	 * 
	 * @param	boolean $withLimit
	 * @return	array
	 */
	public function getYoga($withLimit = false)
	{
		$unit = 360/27;
		
		$lonCh = $this->_paramsData['graha'][Graha::GRAHA_CH]['longitude'];
		$lonSy = $this->_paramsData['graha'][Graha::GRAHA_SY]['longitude'];
		$lonSum = $lonCh + $lonSy;
		
		if($lonSum > 360) {
			$lonSum = $lonSum - 360;
		}
		
		$yogaUnits = Math::partsToUnits($lonSum, $unit);
		
		$yoga['number'] = $yogaUnits['units'];
		$yoga['name'] = Yoga::$YOGA[$yoga['number']];
		$yoga['left'] = ($unit - $yogaUnits['parts']) * 100 / $unit;
		
		if($withLimit){
			$limits = $this->_limitAnga($yoga, __FUNCTION__);
			$yoga['end'] = $limits['end'];
		}
		
		return $yoga;
	}
	
	/**
	 * Get Varana
	 * 
	 * @return	array
	 */
	public function getVara()
	{
		$dateUser = new DateTime($this->_userData['date'].' '.$this->_userData['time']);
		$dateUserU = $dateUser->format('U');
		$dateRising2 = new DateTime($this->_risingData['time'][2]['rising']);
		$dateRising2U = $dateRising2->format('U');
		
		$varaNumber = $dateUser->format('w');
		
		if($dateUser >= $dateRising2) {
			$vara['number'] = $varaNumber + 1;
			
			$dateRising3 = new DateTime($this->_risingData['time'][3]['rising']);
			$dateRising3U = $dateRising3->format('U');
			$duration = $dateRising3U - $dateRising2U;
			$vara['left'] = ($dateRising3U - $dateUserU) * 100 / $duration;
			$vara['start'] = $this->_risingData['time'][2]['rising'];
			$vara['end'] = $this->_risingData['time'][3]['rising'];
		} else {
			$varaNumber != 0 ? $vara['number'] = $varaNumber : $vara['number'] = 7;
			
			$dateRising1 = new DateTime($this->_risingData['time'][1]['rising']);
			$dateRising1U = $dateRising1->format('U');
			$duration = $dateRising2U - $dateRising1U;
			$vara['left'] = ($dateRising2U - $dateUserU) * 100 / $duration;
			$vara['start'] = $this->_risingData['time'][1]['rising'];
			$vara['end'] = $this->_risingData['time'][2]['rising'];
		}
		
		$vara['name'] = Vara::$VARA[$vara['number']];
		
		return $vara;
	}
	
	/**
	 * Get Karana
	 * 
	 * @param	boolean $withLimit
	 * @return	array
	 */
	public function getKarana($withLimit = false)
	{
		if($this->_tithi['left'] < 50){
			$number = 2;
			$left = $this->_tithi['left'];
			if($withLimit)
				$karana['end'] = $this->_tithi['end'];
		} else {
			$number = 1;
			$left = $this->_tithi['left'] - 50;
			if($withLimit){
				$dateUser = new DateTime($this->_userData['date'].' '.$this->_userData['time']);
				$tithiEnd = new DateTime($this->_tithi['end']);
				$dateUserU = $dateUser->format('U');
				$tithiEndU = $tithiEnd->format('U');
				$timeHalfU = round(($tithiEndU - $dateUserU) * 50 / $this->_tithi['left']);
				$karana['end'] = $tithiEnd->sub(new DateInterval('PT'.$timeHalfU.'S'))->format(Time::FORMAT_DATETIME);
			}
		}
		
		$tithiObject = Tithi::getInstance($this->_tithi['number']);
		$karanaName = $tithiObject::$tithiKarana[$number];
		$karanaNumber = array_search($karanaName, Karana::$KARANA);
		
		$karana['number'] = $karanaNumber;
		$karana['name'] = $karanaName;
		$karana['left'] = $left * 2;
		
		return $karana;
	}
	
	/**
	 * Get user data
	 * 
	 * @return array
	 */
	public function getData()
	{
		return $this->_userData;
	}

	/**
	 * Set data for Panchanga
	 * 
	 * @param array $data
	 */
	private function _setData(array $data = null)
	{
		if(!is_null($data)) $this->_ganitaObject->setData($data);
		
		$this->_userData = $this->_ganitaObject->getData();
		$this->_paramsData = $this->_ganitaObject->getParams();
	}
	
	/**
	 * Calculate Anga limits
	 * 
	 * @param array $anga
	 * @param string $function
	 * @return array
	 */
	private function _limitAnga($anga, $function = 'getTithi')
	{
		if($function == 'getTithi'){
			$durMonth = Masa::DUR_SYNODIC * 86400;
			$nAnga = 30;
			$anga['ratio'] = 1;
		}elseif($function == 'getYoga'){
			$durMonth = Masa::DUR_SYNODIC * 86400;
			$nAnga = 27;
			$anga['ratio'] = 1;
		}elseif($function == 'getNakshatra'){
			$durMonth = Masa::DUR_SIDERIAL * 86400;
			$nAnga = 27;
		}else{
			$anga['ratio'] = 1;
		}
		
		$dateUser	= new DateTime($this->_userData['date'].' '.$this->_userData['time']);
		$durAnga	= $durMonth * $anga['ratio'] / $nAnga;
		$Panchanga	= clone $this;
		
		$timeLeft = round($durAnga * ($anga['left'] / 100) / 2);
		
		// End time
		do {
			$timeEndObject = $dateUser->add(new DateInterval('PT'.$timeLeft.'S'));
			
			$Panchanga->_setData(array(
					'date' => $timeEndObject->format(Time::FORMAT_DATA_DATE), 
					'time' => $timeEndObject->format(Time::FORMAT_DATA_TIME)
			));
			
			if($function == 'getNakshatra'){
				$angaEnd = $Panchanga->$function(false, $anga['abhijit']);
			}else{
				$angaEnd = $Panchanga->$function();
			}
			
			$timeLeft = round($durAnga * ($angaEnd['left'] / 100) / 2 );
		} while($angaEnd['left'] > .2);
		
		$result = array(
			'end' => $timeEndObject->format(Time::FORMAT_DATETIME),
		);
		
		unset($Panchanga);
		return $result;
	}
}