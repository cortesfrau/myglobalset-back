<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CollectedCardPrint;
use Illuminate\Database\QueryException;

class CollectedCardPrintController extends Controller
{

  public function create(Request $request)
  {
    try {
        $request->validate([
            'scryfall_id' => 'required',
            'collection_id' => 'required',
        ]);

        $isPrintInCollection = CollectedCardPrint::where([
            'scryfall_id' => $request->scryfall_id,
            'collection_id' => $request->collection_id,
        ])->exists();

        if ($isPrintInCollection) {
            return response()->json(['error' => 'Card print already exists in the collection.'], 400);
        }

        $collected_card_print = new CollectedCardPrint([
            'scryfall_id' => $request->scryfall_id,
            'collection_id' => $request->collection_id,
        ]);

        $collected_card_print->save();

        return response()->json(['message' => 'Card print stored.'], 201);

    } catch (QueryException $exception) {
        if ($exception->errorInfo[1] === 1062) {
            return response()->json(['error' => 'Could not store card print.'], 400);
        }

        throw $exception;
    }
}

  public function remove(Request $request)
  {
    try {
      $request->validate([
          'scryfall_id' => 'required',
          'collection_id' => 'required',
      ]);

      CollectedCardPrint::where([
          'scryfall_id' => $request->scryfall_id,
          'collection_id' => $request->collection_id,
      ])->delete();

      return response()->json(['message' => 'Collected card print removed.'], 200);

    } catch (\Exception $exception) {

      return response()->json(['error' => 'Error removing collected card print.'], 500);

    }
  }

    public function isPrintInCollection(Request $request)
    {
        try {
            $request->validate([
                'scryfall_id' => 'required',
                'collection_id' => 'required',
            ]);

            $isPrintInCollection = CollectedCardPrint::where([
                'scryfall_id' => $request->scryfall_id,
                'collection_id' => $request->collection_id,
            ])->exists();

            return response()->json($isPrintInCollection, 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Error checking if print is in collection.'], 500);
        }
    }

}
