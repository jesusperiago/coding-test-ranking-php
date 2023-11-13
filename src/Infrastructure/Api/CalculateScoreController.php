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
     * Calculates the score based on the ad's description and typology
     *
     * @param [Ad] $ad
     * @return integer
     */
    private function calculateScoreForDescription($ad): int
    {
        $score = 0;
        $descriptionBonus = 0;

        $description = $ad->getDescription();
        $typology = $ad->getTypology();

        if (!empty($description)) {
            $descriptionBonus = 5;

            $wordCount = str_word_count($description);

            if ($typology === 'FLAT') {
                if ($wordCount >= 20 && $wordCount < 50) {
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

        foreach ($specificWords as $word) {
            if (stripos($ad->getDescription(), $word) !== false) {
                $score += $wordBonus;
            }
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
