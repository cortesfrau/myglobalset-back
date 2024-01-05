<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScryfallController extends Controller
{

    public function getCardByOracleId($oracleId)
    {
        try {
            $cardPrintings = $this->getCardPrintingsdByOracleId($oracleId);

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

    private function mapCardPrintings($cardPrintings)
    {
        $languagesData = config('languages');

        return array_map(function ($cardPrinting) use ($languagesData) {
            $languageCode = $cardPrinting['lang'];

            if (isset($languagesData[$languageCode])) {
                $languageData = $languagesData[$languageCode];

                return [
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
                'is_collected' => null
                ];

            } else {

            $english = [
                'name' => 'English',
                'code' => 'en',
                'flag_icon' => 'fi fi-gb fis',
            ];

            return [
                'oracle_id' => $cardPrinting['oracle_id'],
                'set_name' => $cardPrinting['set_name'],
                'set_id' => $cardPrinting['set_id'],
                'lang' => $english,
                'image_uri' => $cardPrinting['image_uris']['png'],
                'digital' => $cardPrinting['digital'],
                'artist' => $cardPrinting['artist'],
                'set_release_date' => $cardPrinting['released_at'],
            ];
            }
        }, $cardPrintings);
    }

    private function getCardPrintingsdByOracleId($oracleId)
    {
        $apiUrl = "https://api.scryfall.com/cards/search?q=oracleid:{$oracleId}&unique=prints&order=released&dir=asc&include_multilingual=true";

        try {
        $response = Http::get($apiUrl);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Error in the Scryfall request');
        }
        } catch (\Exception $e) {
            throw new \Exception('Error in the Scryfall request: ' . $e->getMessage());
        }
    }

    public function getSetById($setId)
    {
        $apiUrl = "https://api.scryfall.com/sets/{$setId}";

        try {
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new \Exception('Error in the Scryfall request');
            }
        } catch (\Exception $e) {
            throw new \Exception('Error in the Scryfall request: ' . $e->getMessage());
        }
    }
}
