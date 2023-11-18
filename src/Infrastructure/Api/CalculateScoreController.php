<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Infrastructure\Persistence\InFileSystemPersistence;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use DateTimeImmutable;

final class CalculateScoreController
{
    private InFileSystemPersistence $persistence;
    private Helpers $helpers;

    /**
     * Constructor accepting an InFileSystemPersistence object
     *
     * @param InFileSystemPersistence $persistence
     */
    public function __construct(InFileSystemPersistence $persistence, Helpers $helper)
    {
        $this->persistence = $persistence;
        $this->helpers = $helper;
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
        $cache->deleteItem('ads');
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
            $date =  $score < 40 ? $this->helpers->calculateTime() : null;
            if( $date instanceof DateTimeImmutable )
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
     * @param Ad $ad Object of type Ad
     * @return integer Final score for Ad min 0 and max 100
     */
    public function calculateScore($ad): int
    {
        $score = 0;

        $score += $this->calculateScoreForPhotos($ad);
        $score += $this->calculateScoreForDescription($ad);
        $score += $this->calculateScoreForSpecificWords($ad);
        $score += $this->calculateScoreForCompleteness($ad);

        $score = $score > 100 ? 100 : $score;
        
        return max(0, $score);
    }

    /**
     * Calculates the score based on the presence and quality of photos
     *
     * @param Ad $ad Object of type Ad
     * @return integer Score for pictures quality and quantity
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
     * Calculates the score based on the ad's description and typology
     *
     * @param Ad $ad Object of type Ad
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

            $wordCount = str_word_count($this->helpers->remove_accents_and_lower($description));

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
     * @param Ad $ad Object of type Ad
     * @return integer Return score for specificwords
     */
    private function calculateScoreForSpecificWords($ad): int
    {
        $score = 0;
        $specificWords = ['Luminoso', 'Nuevo', 'Céntrico', 'Reformado', 'Ático'];
        $wordBonus = 5;
        $description = $this->helpers->remove_accents_and_lower($ad->getDescription() );
        $specificWords = $this->helpers->remove_accents_and_lower($specificWords );
        foreach ($specificWords as $word) {
            $times = $this->helpers->calculateWordsInString($description, $word );
            $score += ( $times * $wordBonus );
        }
        return $score;
    }

    /**
     * Calculates the score based on the completeness of ad information
     *
     * @param Ad $ad Object of type Ad
     * @return integer Returns score if the ad is complete
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
