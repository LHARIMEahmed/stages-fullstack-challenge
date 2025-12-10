<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Article;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;


class CommentController extends Controller
{
    /**
     * Get comments for an article.
     */
    public function index($articleId)
    {
        $comments = Comment::where('article_id', $articleId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }
    
    /**
     * Store a new comment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'user_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);
        //
        $validated['content'] = Purifier::clean($validated['content'], 'myclean');
        $comment = Comment::create($validated);
        $comment->load('user');

        return response()->json($comment, 201);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $articleId = $comment->article_id;

        $comment->delete();

        $remainingComments2 = Comment::where('article_id', $articleId)->get();
        
        $firstComment = $remainingComments2->first(); // first() au lieu de [0] safe, retourne null si aucun commentaire , la deuxime modif, ,
        return response()->json([
            'message' => 'Comment deleted successfully',
            'remaining_count' => $remainingComments2->count(),
            'first_remaining' => $firstComment,
        ]);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json($comment);
    }
}

