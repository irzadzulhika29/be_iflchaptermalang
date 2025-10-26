<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\User;

class CommentController extends Controller
{
  private $replies_count = 0;

  private function showReply($replies) {
    $user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;
    foreach ($replies as $reply) {
      $liked = $user ? $user->likedComments()->where('comment_id', $reply->id)->first() : null;
      $comment_user = User::find($reply->user_id) ?? null;
      $comment_reference = Comment::find($reply->base_comment_id);
      $comment_reference_creator = User::find($comment_reference->user_id);
      $reply->liked = $liked ? true : false;
      $reply->profile_picture = $comment_user->profile_picture ?? null;
      $reply->replying_to = $comment_reference_creator ? $comment_reference_creator->username : null;
      unset($reply->user);
      $this->replies_count++;
      
      if ($reply->replies->isNotEmpty()) {
        $this->showReply($reply->replies); 
      }
    }

    return $this->replies_count;
  }

  private function deleteReplies($comment) {
    foreach ($comment->replies as $reply) {
      $this->deleteReplies($reply);

      $reply->delete();
      $this->replies_count++;
    }
    return $this->replies_count;
  }
  
  public function viewComment(string $blog_id)
  {
    $comments = Comment::where('blog_id', $blog_id)
                        ->whereNull('base_comment_id')  
                        ->with('replies')                  
                        ->get();
      
    $user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;
    
    foreach ($comments as $comment) {
      $liked = $user ? $user->likedComments()->where('comment_id', $comment->id)->first() : null;
      $user = User::find($comment->user_id);
      $comment->profile_picture = $user ? $user->profile_picture : null;
      $comment->liked = $liked ? true : false;

      $this->showReply($comment->replies);
    }

    $total_comment = Comment::where('blog_id', $blog_id)->count();
    $latest_update = Comment::latest()->value('updated_at');

    if (!$comments) {
      return response()->json([
        'status' => 'error',
        'message' => 'No comment found in the blog',
      ], 404);
    }

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get all comment in blog success',
        'data' => [
          'total_comment' => $total_comment,
          'latest_update' => $latest_update,
          'replies' => $comments
        ]
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function viewCommentById(string $comment_id)
  {
    $current_user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;

    $comment = Comment::with('replies')->find($comment_id);

    $liked = $current_user ? $current_user->likedComments()->where('comment_id', $comment->id)->first() : null;
    $comment->liked = $liked ? true : false;
    if ($comment->base_comment_id) {
      $comment_reference = Comment::find($comment->base_comment_id);
      $comment_reference_creator = User::find($comment_reference->user_id);
      $comment->replying_to = $comment_reference_creator ? $comment_reference_creator->username : null;
    }

    if (!$comment) {
      return response()->json([
        'status' => 'error',
        'message' => 'No comment found in the blog',
      ], 404);
    }

    $user = User::find($comment->user_id);
    $comment->profile_picture = $user ? $user->profile_picture : null;

    $reply_count = $this->showReply($comment->replies) + 1;  

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get comment by id success',
        'total' => $reply_count,
        'data' => $comment,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function addComment(Request $request, string $id)
  {
    $user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;

    $blog = Blog::find($id);

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given id',
      ], 404);
    }

    $data = $request->only('username', 'content');
    $data['blog_id'] = $blog->id;
    $data['user_id'] = $user ? $user->id : null;
    $data['username'] = $user ? $user->username : ($request->username ?? 'anonymous');

    $rule = [
      'username' => ['nullable', 'string', 'max:255'],
      'content' => ['required', 'string'],
      'user_id' => ['nullable', 'uuid'],
      'blog_id' => ['required', 'uuid'],
    ];

    $validator = Validator::make($data, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      DB::beginTransaction();

      $comment = Comment::create($data);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Create comment success',
        'data' => $comment,
      ], 201);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function editComment(Request $request, string $blog_id, string $comment_id)
  {
    $user = auth()->user();

    $blog = Blog::find($blog_id);

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given id',
      ], 404);
    }

    $comment = Comment::find($comment_id);

    if (!$comment) {
      return response()->json([
        'status' => 'error',
        'message' => 'Comment not found with the given id',
      ], 404);
    }

    if ($user->id != $comment->user_id || !$user->hasRole(User::ROLE_ADMIN)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Unauthorized, you are not the creator of the comment',
      ], 403);
    }

    $data = $request->only('content');
    $rule = ['content' => ['required', 'string']];

    $validator = Validator::make($data, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      DB::beginTransaction();

      $comment->update(['content' => $data['content'] ?? $comment->content]);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Edit comment success',
        'data' => $comment,
      ], 200);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function deleteComment(string $comment_id)
  {
    $user = auth()->user();
    
    $comment = Comment::with('replies')->find($comment_id);

    if (!$comment) {
      return response()->json([
        'status' => 'error',
        'message' => 'Comment not found with the given id',
      ], 404);
    }

    if (!$user) {
      return response()->json([
        'status' => 'error',
        'message' => 'Unauthorized, you are not the creator of the comment',
      ], 403);
    } else {
      if ($user->checkRole() != User::ROLE_ADMIN) {
        if ($user->id != $comment->user_id) {
          return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized, you are not the creator of the comment',
          ], 403);
        }
      }
    }

    try {
      DB::beginTransaction();

      $deleted_replies = $this->deleteReplies($comment);
      
      $comment->delete();
      
      $count = $deleted_replies + 1;
      
      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Delete comment success',
        'total' => $count,
        'data' => $comment,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function replyComment(Request $request, string $blog_id, string $comment_id)
  {
    $user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;

    $base_comment = Comment::find($comment_id);

    if (!$base_comment) {
      return response()->json([
        'status' => 'error',
        'message' => 'Base comment not found with the given id',
      ], 404);
    }

    $data = $request->only('username', 'content');
    $data['blog_id'] = $blog_id;
    $data['base_comment_id'] = $base_comment->id;
    $data['user_id'] = $user? $user->id : null;
    $data['username'] = $user ? $user->username : ($request->name ?? 'anonymous');

    $rule = [
      'username' => ['nullable', 'string', 'max:255'],
      'content' => ['required', 'string'],
      'base_comment_id' => ['required', 'uuid'],
      'user_id' => ['nullable', 'uuid'],
      'blog_id' => ['required', 'uuid'],
    ];

    $validator = Validator::make($data, $rule);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'error' => $validator->messages(),
      ], 422);
    }

    try {
      DB::beginTransaction();

      $comment = Comment::create($data);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Create reply comment success',
        'data' => $comment,
      ], 201);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function likeComment(string $comment_id)
  {
    $user = auth()->user();
    $comment = Comment::find($comment_id);

    if (!$comment) {
      return response()->json([
        'status' => 'error',
        'message' => 'comment not found with the given id',
      ], 404);
    }

    try {
      if (!$user) {
        return response()->json([
          'status' => 'failed',
          'message' => 'You need to login to like comments',
        ], 403);
      } else {
        $likedByUser = $user->likedComments()->where('comment_id', $comment->id)->first();
  
        if (!$likedByUser) {
          $comment->increment('like');
          $user->likedcomments()->attach($comment->id);
          return response()->json([
            'status' => 'success',
            'message' => 'Like comment success',
            'data' => $comment,
          ], 200);
        } else {
          $comment->decrement('like');
          $user->likedComments()->detach($comment->id);
          return response()->json([
            'status' => 'success',
            'message' => 'Unlike comment success',
            'data' => $comment,
          ], 200);
        }
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
