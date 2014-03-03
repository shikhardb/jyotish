<?php
/**
 * @link      http://github.com/kunjara/jyotish for the canonical source repository
 * @license   GNU General Public License version 2 or later
 */

namespace Jyotish\Rashi;

use Jyotish\Service\Utils;

/**
 * Class with Rashi names and attributes.
 *
 * @author Kunjara Lila das <vladya108@gmail.com>
 */
class Rashi {
	const BHAVA_CHARA = 'chara';
	const BHAVA_STHIRA = 'sthira';
	const BHAVA_DVISVA = 'dvisva';

	/**
	 * Array of all rashis.
	 * 
     * @var array 
	 * @see Maharishi Parashara. Brihat Parashara Hora Shastra. Chapter 4, Verse 3.
     */
	static public $RASHI = array(
		1 => 'Mesha',
		2 => 'Vrishabha',
		3 => 'Mithuna',
		4 => 'Karka',
		5 => 'Simha',
		6 => 'Kanya',
		7 => 'Tula',
		8 => 'Vrishchika',
		9 => 'Dhanu',
		10 => 'Makara',
		11 => 'Kumbha',
		12 => 'Meena',
	);
	
	/**
	 * Devanagari title 'rashi' in transliteration.
	 * 
	 * @var array
	 * @see Jyotish\Alphabet\Devanagari
	 */
	static public $translit = array(
		'ra','aa','sha','i'
	);
	
	/**
	 * Unicode of rashi.
	 * 
	 * @var string
	 */
	static public $rashiUnicode;
	
	/**
	 * Bhava of rashi.
	 * 
	 * @var string
	 * @see Maharishi Parashara. Brihat Parashara Hora Shastra. Chapter 4, Verse 5-5 1/2.
	 */
	static public $rashiBhava;
	
	/**
	 * Bhuta of rashi.
	 * 
	 * @var string
	 */
	static public $rashiBhuta;
	
	/**
	 * Gender of rashi.
	 * 
	 * @var string
	 * @see Maharishi Parashara. Brihat Parashara Hora Shastra. Chapter 4, Verse 5-5 1/2.
	 */
	static public $rashiGender;
	
	/**
	 * Limb of Kaal Purush.
	 * 
	 * @var string
	 * @see Maharishi Parashara. Brihat Parashara Hora Shastra. Chapter 4, Verse 4-4 1/2.
	 */
	static public $rashiLimb;
	
	/**
	 * Prakriti of rashi.
	 * 
	 * @var string
	 * @see Maharishi Parashara. Brihat Parashara Hora Shastra. Chapter 4, Verse 5-5 1/2.
	 */
	static public $rashiPrakriti;

	static public function getInstance($number, $options = null) {
		if (array_key_exists($number, self::$RASHI)) {
			$rashiClass = 'Jyotish\\Rashi\\Object\\R' . $number;
			$rashiObject = new $rashiClass($options);

			return $rashiObject;
		} else {
			throw new Exception\InvalidArgumentException("Rashi with the number '$number' does not exist.");
		}
	}
	
	static public function nextRashi($rashi) {
		$rashiIncrement = $rashi + 1;
		$rashiZodiac = self::inZodiacRashi($rashiIncrement);
		
		return $rashiZodiac;
	}
	
	static public function inZodiacRashi($rashi, $step = 1) {
		$rashiStep = $rashi + ($step - 1);
		
		if($rashiStep < 12) {
			$rashiZodiac = $rashiStep;
		} else {
			$rashiZodiac = fmod($rashiStep, 12);
			if($rashiZodiac == 0) {
				$rashiZodiac = 12;
			}
		}
		
		return $rashiZodiac;
	}

}