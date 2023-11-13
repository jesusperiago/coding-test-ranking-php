<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

final class PublicListingController
{
    private CalculateScoreController $ads;
    private array $listAds = [];

    /**
     * Constructor that injects a CalculateScoreController instance
     *
     * @param CalculateScoreController $ads
     */
    public function __construct(CalculateScoreController $ads)
    {
        $this->ads = $ads;
    }
    
    /**
     * Invokable method that returns a JsonResponse containing ads sorted by score in descending order
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $this->listAds = $this->ads->getAdsAndScore();
        return new JsonResponse($this->getAdsSortedByScoreDesc());
    }

    /**
     * Method to get ads sorted by score in descending order
     *
     * @return array
     */
    public function getAdsSortedByScoreDesc(): array
    {
        $filteredAds = array_filter($this->listAds, function ($ad) {
            return $ad['score'] >= 40;
        });
        usort($filteredAds, function ($ad1, $ad2) {
            return $ad2['score'] <=> $ad1['score'];
        });

        return $filteredAds;
    }
}
