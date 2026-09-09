<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function index()
    {
        $this->ensureLinkColumnExists();

        $data = [
            'posts' => Post::latest()->get() 
        ];

        return Inertia::render('Admin/Posts', $data);
    }

    public function store(Request $request)
    {
        $this->ensureLinkColumnExists();

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string',
            'link' => 'nullable|string|max:1000',
            'post_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
        ]);

        $postData = $request->except('image');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blog_images', 'public');
            $postData['image'] = '/storage/' . $path;
        }

        // Normalize URL
        $link = trim($request->input('link') ?? '');
        if (!empty($link)) {
            if (!preg_match('~^https?://~i', $link)) {
                $link = 'https://' . $link;
            }
        } else {
            $link = null;
        }

        $cleanExcerpt = trim(preg_replace('/<!--DEST_LINK:.*?-->/', '', $postData['excerpt'] ?? ''));

        $hasLinkColumn = false;
        try {
            $hasLinkColumn = Schema::hasTable('posts') && Schema::hasColumn('posts', 'link');
        } catch (\Throwable $e) {
            $hasLinkColumn = false;
        }

        if ($hasLinkColumn) {
            $postData['excerpt'] = $cleanExcerpt;
            $postData['link'] = $link;
        } else {
            // Fallback storage when link column is absent in database
            if (!empty($link)) {
                $postData['excerpt'] = $cleanExcerpt . "\n<!--DEST_LINK:{$link}-->";
            } else {
                $postData['excerpt'] = $cleanExcerpt;
            }
            unset($postData['link']);
        }

        try {
            $columns = Schema::getColumnListing('posts');
            if (!empty($columns)) {
                $postData = array_intersect_key($postData, array_flip($columns));
            }
        } catch (\Throwable $e) {
            // Continue safely
        }

        Post::create($postData);

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully.');
    }

    public function update(Request $request, Post $post)
    {
        $this->ensureLinkColumnExists();

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string',
            'link' => 'nullable|string|max:1000',
            'post_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $postData = $request->except('image');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blog_images', 'public');
            $postData['image'] = '/storage/' . $path;
        }

        // Normalize URL
        $link = trim($request->input('link') ?? '');
        if (!empty($link)) {
            if (!preg_match('~^https?://~i', $link)) {
                $link = 'https://' . $link;
            }
        } else {
            $link = null;
        }

        $cleanExcerpt = trim(preg_replace('/<!--DEST_LINK:.*?-->/', '', $postData['excerpt'] ?? ''));

        $hasLinkColumn = false;
        try {
            $hasLinkColumn = Schema::hasTable('posts') && Schema::hasColumn('posts', 'link');
        } catch (\Throwable $e) {
            $hasLinkColumn = false;
        }

        if ($hasLinkColumn) {
            $postData['excerpt'] = $cleanExcerpt;
            $postData['link'] = $link;
        } else {
            // Fallback storage when link column is absent in database
            if (!empty($link)) {
                $postData['excerpt'] = $cleanExcerpt . "\n<!--DEST_LINK:{$link}-->";
            } else {
                $postData['excerpt'] = $cleanExcerpt;
            }
            unset($postData['link']);
        }

        try {
            $columns = Schema::getColumnListing('posts');
            if (!empty($columns)) {
                $postData = array_intersect_key($postData, array_flip($columns));
            }
        } catch (\Throwable $e) {
            // Continue safely
        }

        $post->update($postData);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
    }

    /**
     * Auto-heals database schema by attempting to add the 'link' column to posts table if missing.
     */
    protected function ensureLinkColumnExists(): void
    {
        try {
            if (Schema::hasTable('posts') && !Schema::hasColumn('posts', 'link')) {
                Schema::table('posts', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('link', 1000)->nullable()->after('excerpt');
                });
            }
        } catch (\Throwable $e) {
            Log::info("Schema auto-add link column skipped: " . $e->getMessage());
        }
    }
}