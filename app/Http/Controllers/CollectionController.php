<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collection;
use Illuminate\Database\QueryException;
use App\Models\User;
use App\Models\CollectedCardPrint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\ScryfallController;

class CollectionController extends Controller
{
    /**
     * Create a new collection for a user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'card_id' => 'required',
                'card_name' => 'required',
            ]);

            // Create a new Collection instance
            $collection = new Collection([
                'user_id' => $request->user_id,
                'card_id' => $request->card_id,
                'card_name' => $request->card_name,
            ]);

            // Save the collection to the database
            $collection->save();

            // Return a successful response
            return response()->json(['message' => 'Collection created.'], 201);
        } catch (QueryException $exception) {
            // Handle unique constraint violation (collection already exists for this card)
            if ($exception->errorInfo[1] === 1062) {
                return response()->json(['error' => 'A collection for this card already exists.'], 400);
            }

            // Re-throw other exceptions
            throw $exception;
        }
    }

    /**
     * Delete a collection and associated collected card prints.
     *
     * @param int $collectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($collectionId)
    {
        try {
            // Check if the collection exists
            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            // Delete collected card prints associated with the collection
            CollectedCardPrint::where('collection_id', $collectionId)->delete();

            // Delete the collection
            Collection::where('id', $collectionId)->delete();

            // Return a successful response
            return response()->json(['message' => 'Collection deleted successfully.'], 200);

        } catch (QueryException $exception) {
            // Handle exceptions during the process
            return response()->json(['error' => 'Error deleting collection.'], 500);
        }
    }

    /**
     * Get all collections for a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserCollections($userId)
    {
        try {
            // Check if the user exists
            $userExists = User::where('id', $userId)->exists();

            if (!$userExists) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            // Get collections for the specified user
            $collections = Collection::where('user_id', $userId)->get();

            if ($collections->isEmpty()) {
                return response()->json(['error' => 'No collections found for the user.'], 404);
            }

            // Return collections as a JSON response
            return response()->json(['collections' => $collections], 200);

        } catch (\Exception $exception) {
            // Handle general exceptions
            return response()->json(['error' => 'Error retrieving user collections.'], 500);
        }
    }

    /**
     * Get details of a specific collection.
     *
     * @param int $collectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollection($collectionId)
    {
        try {
            // Check if the collection exists
            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            // Get the collection details
            $collection = Collection::where('id', $collectionId)->get();

            // Return the collection details as a JSON response
            return response()->json($collection[0], 200);

        } catch (\Exception $exception) {
            // Handle exceptions during the process
            return response()->json(['error' => 'Error retrieving collection.'], 500);
        }
    }

    /**
     * Get the content of a specific collection, including card prints and their collection status.
     *
     * @param int $collectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollectionContent($collectionId)
    {
        try {
            // Check if the collection exists
            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            // Get the collection details
            $collection = Collection::find($collectionId);

            // Instantiate ScryfallController for fetching card data
            $scryfallController = new ScryfallController();
            $cardData = $scryfallController->getCardByOracleId($collection->card_id);

            // Process card prints and check their collection status
            $cardPrints = $cardData['prints'];
            foreach ($cardPrints as &$cardPrint) {
                $scryfallId = isset($cardPrint['id']) ? $cardPrint['id'] : null;

                \Log::info("Value of \$scryfallId: {$scryfallId}");

                // Check if the card print is collected in the specified collection
                $isCollected = CollectedCardPrint::where('collection_id', $collectionId)
                    ->where('scryfall_id', $scryfallId)
                    ->exists();

                // Add the 'is_collected' property to each card print
                $cardPrint['is_collected'] = $isCollected;
            }

            // Format the response data
            $formattedData = [
                'name' => $cardData['name'],
                'oracle_id' => $cardData['oracle_id'],
                'art_uri' => $cardData['art_uri'],
                'prints' => array_values($cardPrints),
            ];

            // Return the formatted data as a JSON response
            return response()->json($formattedData, 200);

        } catch (\Exception $exception) {
            // Log the exception
            \Log::error("Error in getCollectionContent: {$exception->getMessage()}");
            return response()->json(['error' => 'Error retrieving collection content.'], 500);
        }
    }

    /**
     * Get statistics for a specific collection, including collected and total prints count.
     *
     * @param int $collectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollectionStats($collectionId)
    {
        try {
            // Check if the collection exists
            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            // Get the count of collected card prints for the specified collection
            $collectedCardPrintCount = DB::table('collected_card_prints')
                ->where('collection_id', $collectionId)
                ->count();

            // Get details of the specified collection
            $collection = DB::table('collections')
                ->where('id', $collectionId)
                ->first();

            // Check for valid card_id in the collection details
            if (!$collection || !isset($collection->card_id)) {
                return response()->json(['error' => 'Invalid or missing card_id in the collection.'], 500);
            }

            $card_id = $collection->card_id;

            // Fetch total prints for the card from Scryfall
            $scryfallController = new ScryfallController;
            $totalPrintsResponse = $scryfallController->getCardByOracleId($card_id);

            // Check if the response is valid and contains prints
            if ($totalPrintsResponse && isset($totalPrintsResponse['prints'])) {
                $totalPrints = $totalPrintsResponse['prints'];
            } else {
                return response()->json(['error' => 'Error retrieving total card print count from Scryfall.'], 500);
            }

            // Calculate completed percentage based on collected and total prints count
            $completedPercentage = round($collectedCardPrintCount * 100 / count($totalPrints), 1);

            // Prepare the response data
            $data = [
                "collected_prints_count" => $collectedCardPrintCount,
                "total_prints_count" => count($totalPrints),
                "completed_percentage" => $completedPercentage,
            ];

            // Return the response data as a JSON response
            return response()->json($data, 200);

        } catch (\Exception $exception) {
            // Return an error response for any exception during the process
            return response()->json(['error' => 'Error retrieving collection stats.', 'message' => $exception->getMessage()], 500);
        }
    }
}
