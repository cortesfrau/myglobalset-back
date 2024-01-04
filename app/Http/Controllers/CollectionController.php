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
  public function create(Request $request)
  {
    try {

      $request->validate([
          'user_id' => 'required|exists:users,id',
          'card_id' => 'required',
          'card_name' => 'required',
      ]);

      $collection = new Collection([
          'user_id' => $request->user_id,
          'card_id' => $request->card_id,
          'card_name' => $request->card_name,
      ]);

      $collection->save();

      return response()->json(['message' => 'Collection created.'], 201);

    } catch (QueryException $exception) {

      if ($exception->errorInfo[1] === 1062) {
          return response()->json(['error' => 'A collection for this card already exists.'], 400);
      }

      throw $exception;
    }
  }

  public function getUserCollections($userId)
  {
      try {
          $userExists = User::where('id', $userId)->exists();

          if (!$userExists) {
              return response()->json(['error' => 'User not found.'], 404);
          }

          $collections = Collection::where('user_id', $userId)->get();

          if ($collections->isEmpty()) {
              return response()->json(['error' => 'No collections found for the user.'], 404);
          }

          return response()->json(['collections' => $collections], 200);

      } catch (\Exception $exception) {
          return response()->json(['error' => 'Error retrieving user collections.'], 500);
      }
  }

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

    public function getCollection($collectionId)
    {
        try {

            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            $collection = Collection::where('id', $collectionId)->get();

            return response()->json($collection[0], 200);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'Error retrieving collection.'], 500);
        }
    }


    public function getCollectionStats($collectionId)
    {
        try {
            $collectionExists = Collection::where('id', $collectionId)->exists();

            if (!$collectionExists) {
                return response()->json(['error' => 'Collection not found.'], 404);
            }

            $collectedCardPrintCount = DB::table('collected_card_prints')
                ->where('collection_id', $collectionId)
                ->count();

            $collection = DB::table('collections')
                ->where('id', $collectionId)
                ->first();

            if (!$collection || !isset($collection->card_id)) {
                return response()->json(['error' => 'Invalid or missing card_id in the collection.'], 500);
            }

            $card_id = $collection->card_id;

            $scryfallController = new ScryfallController;
            $totalPrintsResponse = $scryfallController->getCardByOracleId($card_id);

            if ($totalPrintsResponse && isset($totalPrintsResponse['prints'])) {
                $totalPrints = $totalPrintsResponse['prints'];
            } else {
                return response()->json(['error' => 'Error retrieving total card print count from Scryfall.'], 500);
            }

            $completedPercentage = round($collectedCardPrintCount * 100 / count($totalPrints), 1);

            $data = [
                "collected_prints_count" => $collectedCardPrintCount,
                "total_prints_count" => count($totalPrints),
                "completed_percentage" => $completedPercentage,
            ];

            return response()->json($data, 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error retrieving collection stats.', 'message' => $exception->getMessage()], 500);
        }
    }




}
