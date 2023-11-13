<?php

declare(strict_types=1);

namespace App\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;


final class QualityListingController
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
     * Invokable method that returns a JsonResponse containing quality-filtered ads
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $this->listAds = $this->ads->getAdsAndScore();
        return new JsonResponse( $this->getAds() );  
    }

    /**
     * Method to get quality-filtered ads
     *
     * @return array
     */
    public function getAds(): array
    {
        $filteredAds = array_filter($this->listAds, function ($ad) {
            return $ad['score'] < 40;
        });
        usort($filteredAds, function ($ad1, $ad2) {
            return $ad2['score'] <=> $ad1['score'];
        });

        return $filteredAds;
    }
}
