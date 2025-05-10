<?php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Data\JsonDataSource;

/**
 * Blog Controller
 * 
 * Handles blog functionality using JSON data source
 */
class BlogController extends BaseController
{
    /**
     * JSON data source for blog posts
     * 
     * @var JsonDataSource
     */
    protected JsonDataSource $posts;
    
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // Initialize posts from a JSON file
        $this->posts = new JsonDataSource('data/blog_posts.json');
    }
    
    /**
     * Show blog index page with all posts
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Get all posts sorted by date
        $posts = $this->posts->getData();
        
        // Sort by publish date, newest first
        usort($posts, function($a, $b) {
            return strtotime($b['published_at'] ?? '') - strtotime($a['published_at'] ?? '');
        });
        
        return $this->view('blog/index', [
            'title' => 'Blog',
            'posts' => $posts,
            'post_count' => count($posts)
        ]);
    }
    
    /**
     * Show a single blog post
     * 
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function show(Request $request, string $slug): Response
    {
        // Find the post with matching slug
        $post = $this->posts->find('slug', $slug);
        
        if (!$post) {
            return $this->view('errors/404', [
                'title' => 'Post Not Found',
                'message' => 'The requested blog post could not be found.'
            ], 404);
        }
        
        return $this->view('blog/show', [
            'title' => $post['title'],
            'post' => $post
        ]);
    }
    
    /**
     * Show posts by category
     * 
     * @param Request $request
     * @param string $category
     * @return Response
     */
    public function category(Request $request, string $category): Response
    {
        // Find all posts in this category
        $posts = $this->posts->filter(function($post) use ($category) {
            return isset($post['category']) && 
                   strtolower($post['category']) === strtolower($category);
        });
        
        if (empty($posts)) {
            return $this->view('blog/category', [
                'title' => 'Category: ' . ucfirst($category),
                'category' => $category,
                'posts' => [],
                'post_count' => 0
            ]);
        }
        
        // Sort by publish date, newest first
        usort($posts, function($a, $b) {
            return strtotime($b['published_at'] ?? '') - strtotime($a['published_at'] ?? '');
        });
        
        return $this->view('blog/category', [
            'title' => 'Category: ' . ucfirst($category),
            'category' => $category,
            'posts' => $posts,
            'post_count' => count($posts)
        ]);
    }
    
    /**
     * Search for blog posts
     * 
     * @param Request $request
     * @return Response
     */
    public function search(Request $request): Response
    {
        $query = $request->getQuery('q', '');
        
        if (empty($query)) {
            return $this->redirect('/blog');
        }
        
        // Search in title and content
        $results = $this->posts->filter(function($post) use ($query) {
            $searchIn = ($post['title'] ?? '') . ' ' . ($post['content'] ?? '');
            return stripos($searchIn, $query) !== false;
        });
        
        return $this->view('blog/search', [
            'title' => 'Search Results',
            'query' => $query,
            'posts' => $results,
            'post_count' => count($results)
        ]);
    }
}