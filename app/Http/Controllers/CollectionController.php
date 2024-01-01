<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Collection;
use Illuminate\Database\QueryException;
use App\Models\User;

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
                return response()->json(['error' => 'No collections found.'], 404);
            }

            return response()->json(['collections' => $collections], 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error retrieving user collections.'], 500);
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

}
