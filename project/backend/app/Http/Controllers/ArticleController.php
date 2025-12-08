<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles.
     */
    public function index(Request $request)
    {
        // utilisation de l'Eager Loading pour éviter le problème N+1
        $articles = Article::with(['author', 'comments'])->get();

        $articles = $articles->map(function ($article) use ($request) {
            if ($request->has('performance_test')) {
                usleep(30000); // 30ms par article pour simuler le coût du N+1
            }

            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200) . '...',
                'author' => $article->author->name,
                'comments_count' => $article->comments->count(),
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json($articles);
    }

    /**
     * Display the specified article.
     */
    public function show($id)
    {
        $article = Article::with(['author', 'comments.user'])->findOrFail($id);

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'author' => $article->author->name,
            'author_id' => $article->author->id,
            'image_path' => $article->image_path,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'comments' => $article->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => $comment->user->name,
                    'created_at' => $comment->created_at,
                ];
            }),
        ]);
    }

    /**
     * Search articles.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }
        
        /**
         * Recherche insensible à la casse et aux accents dans MySQL sans modifier la table.
         *
         * Problématique :
         *   - On veut que "cafe" retourne aussi "café", "CAFÉ", etc.
         *   - La colonne title de la table articles n'a pas forcément la collation utf8mb4_unicode_ci.
         *   - Modifier la table n'est pas souhaité.
         *
         * Solution :
         *   - On utilise CONVERT(title USING utf8mb4) pour forcer temporairement la conversion
         *     du champ en utf8mb4 lors de la comparaison.
         *   - On applique COLLATE utf8mb4_unicode_ci pour rendre la recherche insensible :
         *       - à la casse (ci = case-insensitive)
         *       - aux accents (unicode_ci = accent-insensitive)
         *
         * 
         * Notes :
         *   - Cette technique fonctionne même si la table ou la colonne a une autre collation.
         *   - La table et les données ne sont pas modifiées.
         *   - MySQL doit supporter la collation utf8mb4_unicode_ci.
         */

        /**
         * l'ajout de la requête preparer avec ? un placeholder ? et un binding de paramètres, ce qui transforme la requête en requête préparée sécurisée.
         */
        $articles = DB::select("
        SELECT * FROM articles
        WHERE CONVERT(title USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", ["%$query%"]);



        $results = array_map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200),
                'published_at' => $article->published_at,
            ];
        }, $articles);

        return response()->json($results);
    }

    /**
     * Store a newly created article.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'author_id' => 'required|exists:users,id',
            'image_path' => 'nullable|string',
        ]);

        $article = Article::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'author_id' => $validated['author_id'],
            'image_path' => $validated['image_path'] ?? null,
            'published_at' => now(),
        ]);

        return response()->json($article, 201);
    }

    /**
     * Update the specified article.
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|max:255',
            'content' => 'sometimes|required',
        ]);

        $article->update($validated);

        return response()->json($article);
    }

    /**
     * Remove the specified article.
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }
}

