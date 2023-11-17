<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Infrastructure\Persistence\InFileSystemPersistence;
use DateTimeImmutable;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;


final class CalculateScoreController
{
    private InFileSystemPersistence $persistence;

    /**
     * Constructor accepting an InFileSystemPersistence object
     *
     * @param InFileSystemPersistence $persistence
     */
    public function __construct(InFileSystemPersistence $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * Invokable method returning a JsonResponse
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $ads = $this->onCache();
        return new JsonResponse($ads);
    }

    /**
     * Caching method that stores ads data in a cache and returns the data
     *
     * @return void
     */
    public function onCache()
    {
        $cache = new FilesystemAdapter();
        // TODO: Check cache should update or not
        $cacheItem = $cache->getItem('ads');
        $data = $this->getAdsAndScore();
        $cacheItem->set($data);
        $cache->save($cacheItem);
        return $data;
    }

    /**
     * Retrieves ads data and their scores
     *
     * @return array
     */
    public function getAdsAndScore(): array
    {
        $ads = $this->persistence->getAds();
        $scores = [];
        foreach ($ads as $ad) {
            $score = $this->calculateScore($ad);
            $ad->setScore($score);
            $date =  $score < 40 ? new DateTimeImmutable() : null;
            if($date != null)
            {
                $ad->setIrrelevantSince($date);
                $date = $date->format('Y-m-d');
            }
            $scores[] = [
                'id'                => $ad->getId(),
                'typology'          => $ad->getTypology(),
                'description'       => $ad->getDescription(),
                'pictures'          => $ad->getPictures(),
                'houseSize'         => $ad->getHouseSize(),
                'gardenSize'        => $ad->getGardenSize(),
                'score'             => $ad->getScore(),
                'irrelevantSince'   => $date,
            ];
        }
        return $scores;
    }

    /**
     * Calculates the score for an ad based on various criteria
     *
     * @param [Ad] $ad
     * @return integer
     */
    public function calculateScore($ad): int
    {
        $score = 0;

        $score += $this->calculateScoreForPhotos($ad);
        $score += $this->calculateScoreForDescription($ad);
        $score += $this->calculateScoreForSpecificWords($ad);
        $score += $this->calculateScoreForCompleteness($ad);

        $score = $score >= 100 ? 100 : $score;
        
        return max(0, $score);
    }

    /**
     * Calculates the score based on the presence and quality of photos
     *
     * @param [Ad] $ad
     * @return integer
     */
    private function calculateScoreForPhotos($ad): int
    {
        $score = 0;
        $pictures = $ad->getPictures();

        if (empty($pictures)) {
            $score -= 10;
        }

        foreach ($pictures as $picture) {
            $opicture = $this->persistence->getPictureById($picture);
            $score += $opicture->isHighResolution() ? 20 : 10;
        }

        return $score;
    }

    /**
     * Remove all accent marks on the string
     *
     * @param string $str
     * @return string String lowercase without accent
     */
    private function remove_accents_and_lower($str){

		$str = str_replace(
		array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
		array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
		$str
		);

		$str = str_replace(
		array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
		array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
		$str );

		$str = str_replace(
		array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
		array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
		$str );

		$str = str_replace(
		array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
		array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
		$str );

		$str = str_replace(
		array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
		array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
		$str );

		$str = str_replace(
		array('Ñ', 'ñ', 'Ç', 'ç'),
		array('N', 'n', 'C', 'c'),
		$str
		);
		
		return strtolower($str);
	}


    /**
     * Calculates the score based on the ad's description and typology
     *
     * @param [Ad] $ad
     * @return integer Score for description length
     */
    private function calculateScoreForDescription($ad): int
    {
        $score = 0;
        $descriptionBonus = 0;

        $description = $ad->getDescription();
        $typology = $ad->getTypology();

        if (!empty($description)) {
            $descriptionBonus = 5;

            $wordCount = str_word_count($this->remove_accents_and_lower($description));

            if ($typology === 'FLAT') {
                if ($wordCount >= 20 && $wordCount <= 49) {
                    $score += 10;
                } elseif ($wordCount >= 50) {
                    $score += 30;
                }
            } elseif ($typology === 'CHALET' && $wordCount > 50) {
                $score += 20;
            }
        }

        $score += $descriptionBonus;

        return $score;
    }

    /**
     * Calculates the score based on the ad's description and typology
     *
     * @param [Ad] $ad
     * @return integer
     */
    private function calculateScoreForSpecificWords($ad): int
    {
        $score = 0;
        $specificWords = ['Luminoso', 'Nuevo', 'Céntrico', 'Reformado', 'Ático'];
        $wordBonus = 5;
        $description = $this->remove_accents_and_lower($ad->getDescription());
        foreach ($specificWords as $word) {
            $word = $this->remove_accents_and_lower($word);
            $times = substr_count($description,$word);
            $score += ($times * $wordBonus);
        }

        return $score;
    }

    /**
     * Calculates the score based on the completeness of ad information
     *
     * @param [Ad] $ad
     * @return integer
     */
    private function calculateScoreForCompleteness($ad): int
    {
        $completenessBonus = 40;

        if ($ad->getTypology() === 'GARAGE') {

            if (empty($ad->getPictures()) || !$ad->getHouseSize() > 0) {
                return 0;
            }
            return $completenessBonus;
        }

        if (empty($ad->getDescription())) {
            return 0;
        }

        if (empty($ad->getPictures())) {
            return 0;
        }

        switch ($ad->getTypology()) {
            case 'FLAT':
                if (!$ad->getHouseSize() > 0) {
                    return 0;
                }
                break;

            case 'CHALET':
                if ($ad->getHouseSize() === null || $ad->getGardenSize() === null) {
                    return 0;
                }
                break;

            default:
                return 0;
        }

        return $completenessBonus;
    }
}
