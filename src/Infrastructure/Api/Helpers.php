<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use DateTimeImmutable;
use DateTimeZone;

final class Helpers
{
    /**
     * Undocumented function
     *
     * @param string $timezone
     * @return void
     */
    final function calculateTime($timezone = 'Europe/Paris'): DateTimeImmutable
    {
        $timezone = new DateTimeZone($timezone );
        $time =  new DateTimeImmutable('yesterday', $timezone);
        return $time;
    }

    /**
     * Remove all accent marks on the string
     *
     * @param array $str
     * @return array|string String lowercase without accent
     */
    final function remove_accents_and_lower($str): array|string
    {

		$str = str_replace(
		array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
		'a',
		$str
		);

		$str = str_replace(
		array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
		'e',
		$str );

		$str = str_replace(
		array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
		'i',
		$str );

		$str = str_replace(
		array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
		'o',
		$str );

		$str = str_replace(
		array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
		'u',
		$str );

		$str = str_replace(
		array('Ñ', 'ñ', 'Ç', 'ç'),
		array('n', 'n', 'c', 'c'),
		$str
		);
		
		return $str;
	}

    /**
     * Calculate the number of words in a sentence
     *
     * @param string $sentence
     * @param string $word
     * @return int Number of words in a sentence
     */
    final function calculateWordsInString($sentence, $word)
    {
        return substr_count(strtolower($sentence),strtolower($word));
    }
}