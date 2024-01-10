<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScryfallController extends Controller
{

    /**
     * Get card details by Oracle ID.
     *
     * @param string $oracleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCardByOracleId($oracleId)
    {
        try {
            $cardPrintings = $this->getCardPrintingsByOracleId($oracleId);

            $cardPrintings = array_filter($cardPrintings['data'], function ($printing) {
                return empty($printing['digital']) || !$printing['digital'];
            });

            $formattedData = [
                'name' => $cardPrintings[0]['name'],
                'oracle_id' => $cardPrintings[0]['oracle_id'],
                'art_uri' => $cardPrintings[0]['image_uris']['art_crop'],
                'prints' => array_values($this->mapCardPrintings($cardPrintings)),
            ];

            return $formattedData;

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error in the Scryfall request: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Map card printings for response.
     *
     * @param array $cardPrintings
     * @return array
     */
    private function mapCardPrintings($cardPrintings)
    {
        $languagesData = config('languages');
        $mappedPrintings = [];

        foreach ($cardPrintings as $cardPrinting) {
            $languageCode = $cardPrinting['lang'];
            $languageData = $languagesData[$languageCode];
            $setIcon = $this->getCachedSetIcon($cardPrinting['set_id']);

            $result = [
                'id' => $cardPrinting['id'],
                'set_name' => $cardPrinting['set_name'],
                'set_id' => $cardPrinting['set_id'],
                'lang' => [
                    'name' => $languageData['name'],
                    'code' => $languageData['code'],
                    'flag_icon' => $languageData['flag_icon'],
                ],
                'image_uri' => $cardPrinting['image_uris']['png'],
                'digital' => $cardPrinting['digital'],
                'artist' => $cardPrinting['artist'],
                'set_release_date' => $cardPrinting['released_at'],
                'foil' => $cardPrinting['foil'],
                'nonfoil' => $cardPrinting['nonfoil'],
                'is_collected' => null,
                'set_icon' => $setIcon,
            ];

            // Duplicate if both foil and nonfoil are true
            if ($cardPrinting['foil'] && $cardPrinting['nonfoil']) {
                $foilVersion = 'foil';
                $foilId = $cardPrinting['id'] . '_' . $foilVersion;

                $foilResult = [
                    'id' => $foilId,
                    'set_name' => $result['set_name'],
                    'set_id' => $result['set_id'],
                    'lang' => [
                        'name' => $languageData['name'],
                        'code' => $languageData['code'],
                        'flag_icon' => $languageData['flag_icon'],
                    ],
                    'image_uri' => $result['image_uri'],
                    'digital' => $result['digital'],
                    'artist' => $result['artist'],
                    'set_release_date' => $result['set_release_date'],
                    'foil' => $result['foil'],
                    'nonfoil' => $result['nonfoil'],
                    'is_collected' => $result['is_collected'],
                    'set_icon' => $result['set_icon'],
                    'is_foil_version' => true,
                ];

                $mappedPrintings[] = $result;
                $mappedPrintings[] = $foilResult;
            } else {
                $mappedPrintings[] = $result;
            }
        }

        return $mappedPrintings;
    }


    /**
     * Get card printings by Oracle ID with caching.
     *
     * @param string $oracleId
     * @return array
     * @throws \Exception
     */
    private function getCardPrintingsByOracleId($oracleId)
    {
        $cacheKey = 'card_printings_' . $oracleId;

        // Try to get card printings from cache
        $cardPrintings = cache($cacheKey);

        if ($cardPrintings === null) {
            // If not in cache, make the API request and store the response in cache
            try {
                $apiUrl = "https://api.scryfall.com/cards/search?q=oracleid:{$oracleId}&unique=prints&order=released&dir=asc&include_multilingual=true";
                $response = Http::get($apiUrl);

                if ($response->successful()) {
                    $cardPrintings = $response->json();
                    cache([$cacheKey => $cardPrintings], now()->addHours(24)); // Cache for 24 hours
                } else {
                    throw new \Exception('Error in the Scryfall request');
                }
            } catch (\Exception $e) {
                throw new \Exception('Error in the Scryfall request: ' . $e->getMessage());
            }
        }

        return $cardPrintings;
    }

    /**
     * Get set details by set ID.
     *
     * @param string $setId
     * @return array
     * @throws \Exception
     */
    public function getSetById($setId)
    {
        $cacheKey = 'set_info_' . $setId;

        // Try to get set details from cache
        $setInfo = cache($cacheKey);

        if ($setInfo === null) {
            // If not in cache, make the API request and store the response in cache
            try {
                $apiUrl = "https://api.scryfall.com/sets/{$setId}";
                $response = Http::get($apiUrl);

                if ($response->successful()) {
                    $setInfo = $response->json();
                    cache([$cacheKey => $setInfo], now()->addHours(48)); // Cache for 48 hours
                } else {
                    throw new \Exception('Error in the Scryfall request');
                }
            } catch (\Exception $e) {
                throw new \Exception('Error in the Scryfall request: ' . $e->getMessage());
            }
        }

        return $setInfo;
    }

    /**
     * Get set icon URI by set ID with caching.
     *
     * @param string $setId
     * @return string|null
     */
    private function getCachedSetIcon($setId)
    {
        $cacheKey = 'set_icon_' . $setId;

        // Try to get set icon from cache
        $setIcon = cache($cacheKey);

        if ($setIcon === null) {
            // If not in cache, fetch set details and store the icon in cache
            try {
                $setInfo = $this->getSetById($setId);
                $setIcon = isset($setInfo['icon_svg_uri']) ? $setInfo['icon_svg_uri'] : null;

                cache([$cacheKey => $setIcon], now()->addHours(48));
            } catch (\Exception $e) {
                \Log::error("Error fetching set icon: {$e->getMessage()}");
                $setIcon = null;
            }
        }

        return $setIcon;
    }
}
