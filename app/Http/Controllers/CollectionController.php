<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collection;
use Illuminate\Database\QueryException;

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
}
