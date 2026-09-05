<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Patients\GlobalSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recherche globale depuis la barre supérieure (§34).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearch $search): View
    {
        $term = $request->string('q')->toString();

        return view('search', [
            'term' => $term,
            'groups' => $search->search($term, $request->user()),
        ]);
    }
}
