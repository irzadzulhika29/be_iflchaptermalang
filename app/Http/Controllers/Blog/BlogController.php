<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Blog;
use App\Models\BlogCategories;
use App\Models\BlogImages;
use App\Models\Comment;

class BlogController extends Controller
{
  private $imageService;

  public function __construct(ImageService $imageService)
  {
      $this->imageService = $imageService;
  }

  private function getBlogDetails(Blog $blog)
  {
    $user = JWTAuth::getToken() ? JWTAuth::parseToken()->authenticate() : null;
    $liked = $user ? $user->likedBlogs()->where('blog_id', $blog->id)->first() : null;
    $blog->categories = $blog->blog_categories->pluck('name')->toArray();
    unset($blog->blog_categories);

    if ($blog->blog_images) {
      $blog->images = $blog->blog_images->pluck('image_url')->toArray();
      unset($blog->blog_images);
    }

    $total_comment = Comment::where('blog_id', $blog->id)->count();
    $blog->liked = $liked ? true : false;
    $blog->slug = Str::slug($blog->title);
    $blog->short_description = $this->extractShortDescription($blog->content);
    $blog->total_comment = $total_comment;
  }

  private function extractContentImageUrls($content)
  {
    $imageUrls = [];

    preg_match_all('/<img[^>]+src="([^"]+)"/', $content, $matches);

    if (!empty($matches[1])) {
        $imageUrls = $matches[1];
    }

    return $imageUrls;
  }

  private function extractShortDescription($content)
  {
    $short_description = [];

    preg_match_all('/<p[^>]*>(.*?)<\/p>/i', $content, $matches);

    if (!empty($matches[1])) {
      foreach ($matches[1] as $match) {
        if (strpos($match, '<img') === false) {
            $short_description[] = $match;
        }
        if (count($short_description) > 1) {
          break;
        }
      }
    }

    return $short_description;
  }

  public function upload(Request $request)
  {
    if($request->hasFile('upload'))
    {
      $file = $request->file('upload');
      $title = $request->title ?? 'anonymous_blog';
      $filename = Str::slug($title);
      $image_folder = "image/blog/" . $filename;
      $image_file_name = $filename;
      $image_tags = ["blog"];

      $image_url = $this->imageService->uploadFile($image_folder, $file, $image_file_name, $image_tags);
  
      return response()->json([
        'filename' => $filename, 
        'uploaded' => 1, 
        'url' => $image_url
      ]);
    }
  }

  public function index()
  {
    $blogs = Blog::with(['blog_categories' => function ($query) {
      $query->select('blog_categories.name');
    }, 'blog_images'])->get();

    $blogs->each(function ($blog) {
      $this->getBlogDetails($blog);
    });

    $latest_update = Blog::latest()->value('updated_at');

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get all blogs success',
        'data' => [
          'latest_update' => $latest_update,
          'blogs' => $blogs
        ],
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function store(Request $request)
  {
    $user = auth()->user();

    $data = $request->only('title', 'content', 'like', 'images', 'blog_categories');
    $data['author_id'] = $user->id;
    $data['slug'] = Str::slug($data['title']);
    $rule = [
      'title' => ['required', 'string', 'unique:blogs'],
      'slug' => ['nullable', 'string'],
      'content' => ['required', 'string'],
      'like' => ['nullable', 'integer'],
      'images' => ['nullable', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
      'blog_categories' => ['required', 'exists:blog_categories,id'],
      'author_id' => ['required', 'uuid'],
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

      $blog = Blog::create($data);

      $imageUrls = $this->extractContentImageUrls($blog->content);

      foreach ($imageUrls as $imageUrl) {
        $imageUrl_split = explode("/", $imageUrl)[6];
        if ($imageUrl_split != $blog->slug) {
          $imageUrls_toMove[] = $imageUrl;
        } else {
          $imageUrls[] = $imageUrl;
        }
      }

      if (isset($imageUrls_toMove)) {
        $baseUrl = "https://ik.imagekit.io/iflmalang/image/blog/";
        $current_folder = "image/blog/" . $blog->slug;
        $moved_images_name = $this->imageService->renameAndMoveFiles($current_folder, $imageUrls_toMove, $blog->slug);
        
        foreach ($moved_images_name as  $moved_image_name) {
          $moved_images_urls[] = $baseUrl . $blog->slug . "/" . $moved_image_name;
        }
        
        $blogImages = array_merge($imageUrls, $moved_images_urls);
        $newContent = str_replace($imageUrls_toMove, $moved_images_urls, $blog->content);
        $blog->update(['content' => $newContent]);
      } else {
        $blogImages = $imageUrls;
      }
      
      foreach ($blogImages as $blogImage) {
        BlogImages::create([
            'blog_id' => $blog->id,
            'image_url' => $blogImage,
        ]);
      }

      if (isset($data['blog_categories'])) {
        $blog_categoryIds = $data['blog_categories'];
        $uniqueBlogCategoryIds = array_unique($blog_categoryIds);
        $existingblogcategories = BlogCategories::whereIn('id', $blog_categoryIds)->get();

        if (count($uniqueBlogCategoryIds) !== count($blog_categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'Duplicate category ids found in the input',
          ], 422);
        }

        if ($existingblogcategories->count() !== count($blog_categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'One or more categories not found with the given id',
          ], 422);
        }

        $blog->blog_categories()->sync($data['blog_categories']);

        $this->getBlogDetails($blog);
      }

      DB::commit();
  
      return response()->json([
        'status' => 'success',
        'message' => 'Blog created successfully',
        'data' => $blog,
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

  public function show(string $slug)
  {

    $blog = Blog::where('slug', $slug)->first();

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given slug',
      ], 404);
    }
    
    $this->getBlogDetails($blog);

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get blog by slug success',
        'data' => $blog,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function update(Request $request, string $id)
  {
    $blog = Blog::find($id);

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given id',
      ], 404);
    }

    $data = $request->only('title', 'content', 'like', 'images', 'blog_categories');
    $rule = [
      'title' => ['required', 'string', 'unique:blogs,title,' . $blog->id],
      'slug' => ['nullable', 'string'],
      'content' => ['required', 'string'],
      'like' => ['nullable', 'integer'],
      'images' => ['nullable', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
      'blog_categories.*' => ['nullable', 'exists:blog_categories,id'],
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
      $previous_slug = $blog->slug;
      $previous_imageUrls = $this->extractContentImageUrls($blog->content);
  
      $blog->update([
        'title' => $data['title'],
        'slug' => Str::slug($data['title']),
        'content' => $data['content'],
      ]);
      
      $current_imageUrls = $this->extractContentImageUrls($blog->content);
      $current_slug = $blog->slug;
      
      $current_folder = "image/blog/" . $current_slug;
      $baseUrl = "https://ik.imagekit.io/iflmalang/image/blog/";
      
      $removed_imageUrls = array_values(array_diff($previous_imageUrls, $current_imageUrls));
      $added_imageUrls = array_values(array_diff($current_imageUrls, $previous_imageUrls));
      $remaining_imageUrls = array_values(array_intersect($previous_imageUrls, $current_imageUrls));
      
      if ($added_imageUrls) {
        foreach ($added_imageUrls as $added_image_url) {
          BlogImages::create([
            'blog_id' => $blog->id,
            'image_url' => $added_image_url,
          ]);
        }
      }
      
      if ($current_slug != $previous_slug) {
        if ($remaining_imageUrls) {
          foreach ($remaining_imageUrls as $remaining_imageUrl) {
            $imageUrls_toMove[] = $remaining_imageUrl;
          }
          
          foreach ($added_imageUrls as $added_imageUrl) {
            $addedImageUrls_oldPath = explode("/", $added_imageUrl)[6];
            if ($addedImageUrls_oldPath != $current_slug) {
              $imageUrls_toMove[] = $added_imageUrl;
            }
          }

          $moved_images_name = $this->imageService->renameAndMoveFiles($current_folder, $imageUrls_toMove, $current_slug);

          foreach ($moved_images_name as $index => $moved_image_name) {
            $moved_images_urls[] = $baseUrl . $current_slug . "/" . $moved_image_name;
            BlogImages::where('image_url', $imageUrls_toMove[$index])
                      ->first()
                      ->update(['image_url' => $moved_images_urls[$index]]);
          }

          $newContent = str_replace($imageUrls_toMove, $moved_images_urls, $blog->content);
          $blog->update(['content' => $newContent]);
                  
          foreach($remaining_imageUrls as $remaining_imageUrl) {
            BlogImages::where('image_url', $remaining_imageUrl)
                      ->first()
                      ?->delete();
          }

          foreach($removed_imageUrls as $removed_imageUrl) {
            BlogImages::where('image_url', $removed_imageUrl)
                      ->first()
                      ->delete();
          }
        } else {
          foreach ($removed_imageUrls as $removed_imageUrl) {
            $removed_imageUrl_split = explode("/", $removed_imageUrl);
            $removed_imageUrl_folder = implode("/", array_slice($removed_imageUrl_split, 4, 3));
            $this->imageService->deleteFile($removed_imageUrl_folder, $removed_imageUrl);
            BlogImages::where('image_url', $removed_imageUrl)
                      ->first()
                      ->delete();
          }
        }
      } 
      else {
        if($previous_imageUrls != $current_imageUrls) {      
          foreach ($removed_imageUrls as $removed_imageUrl) {
            $removed_imageUrl_split = explode("/", $removed_imageUrl);
            $removed_imageUrl_folder = implode("/", array_slice($removed_imageUrl_split, 4, 3));
            $this->imageService->deleteFile($removed_imageUrl_folder, $removed_imageUrl);
            BlogImages::where('image_url', $removed_imageUrl)
                      ->first()
                      ->delete();
          }
        }
      }

      $updated_imageUrls = $this->extractContentImageUrls($blog->content);
      $old_blog_images = BlogImages::where('blog_id', $blog->id)
                ->whereNotIn('image_url', $updated_imageUrls)
                ->pluck('image_url');

      if ($old_blog_images->isNotEmpty()) {
        foreach ($old_blog_images as $old_blog_image) {
          BlogImages::where('image_url', $old_blog_image)->delete();
        }
      }

      if (isset($data['blog_categories'])) {
        $blog_categoryIds = $data['blog_categories'];
        $uniqueBlogCategoryIds = array_unique($blog_categoryIds);
        $existingblogcategories = BlogCategories::whereIn('id', $blog_categoryIds)->get();

        if (count($uniqueBlogCategoryIds) !== count($blog_categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'Duplicate category ids found in the input',
          ], 422);
        }

        if ($existingblogcategories->count() !== count($blog_categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'One or more categories not found with the given id',
          ], 422);
        }

        $blog->blog_categories()->sync($data['blog_categories']);
      }

      $blog->refresh();

      $this->getBlogDetails($blog);

      DB::commit();

      return response()->json([
        'status' => 'success', 
        'message' => 'Blog updated successfully',
        'data' => $blog,
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

  public function destroy(string $id)
  {
    $blog = Blog::find($id);

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given id',
      ], 404);
    }

    $this->getBlogDetails($blog);

    try {
      DB::beginTransaction();

      $blog_title =  Str::slug($blog->title);
      $image_folder = 'image/blog/' . $blog_title;
      $images = $blog->images;

      foreach ($images as $image) {
        $this->imageService->deleteFile($image_folder, $image);
      }
      
      $this->imageService->deleteFolder($image_folder);
      $blog->blog_categories()->detach();
      $blog->delete();

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Blog deleted successfully',
        'data' => $blog,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function likeBlog(string $id)
  {
    $user = auth()->user();
    $blog = Blog::find($id);

    if (!$blog) {
      return response()->json([
        'status' => 'error',
        'message' => 'Blog not found with the given id',
      ], 404);
    }

    try {
      if (!$user) {
        return response()->json([
          'status' => 'failed',
          'message' => 'You need to login to like blogs',
        ], 403);
      } else {
        $likedByUser = $user->likedBlogs()->where('blog_id', $blog->id)->first();
  
        if (!$likedByUser) {
          $blog->increment('like');
          $user->likedBlogs()->attach($blog->id);
          return response()->json([
            'status' => 'success',
            'message' => 'Like blog success',
            'data' => $blog,
          ], 200);
        } else {
          $blog->decrement('like');
          $user->likedBlogs()->detach($blog->id);
          return response()->json([
            'status' => 'success',
            'message' => 'Unlike blog success',
            'data' => $blog,
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
