<?php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Database\DatabaseConnection;
use Framework\Data\JsonDataSource;

/**
 * Example User Controller
 * 
 * Demonstrates how to use the framework with database and JSON data sources
 */
class UserController extends BaseController
{
    /**
     * Database connection
     * 
     * @var DatabaseConnection
     */
    protected DatabaseConnection $db;
    
    /**
     * JSON data source for users
     * 
     * @var JsonDataSource
     */
    protected JsonDataSource $jsonUsers;
    
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // Database connection example
        $this->db = new DatabaseConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'my_app',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ]);
        
        // JSON data source example - path is relative to the project root
        $this->jsonUsers = new JsonDataSource('data/users.json');
    }
    
    /**
     * Show all users from database
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            // Example of fetching users from database
            $users = $this->db->select("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
            
            // Example of using template engine with data
            return $this->view('users/index', [
                'users' => $users,
                'title' => 'User List',
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            // Graceful error handling
            return $this->view('errors/database', [
                'error' => $e->getMessage(),
                'title' => 'Database Error'
            ]);
        }
    }
    
    /**
     * Show a user from database by ID
     * 
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        try {
            // Example of fetching a single user with parameters
            $user = $this->db->selectOne(
                "SELECT * FROM users WHERE id = ?",
                [$id]
            );
            
            if (!$user) {
                return $this->view('errors/404', [
                    'title' => 'User Not Found',
                    'message' => 'The requested user could not be found'
                ], 404);
            }
            
            return $this->view('users/show', [
                'user' => $user,
                'title' => 'User Profile: ' . $user['name']
            ]);
        } catch (\Exception $e) {
            return $this->view('errors/database', [
                'error' => $e->getMessage(),
                'title' => 'Database Error'
            ]);
        }
    }
    
    /**
     * Create a new user
     * 
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate input
            $errors = $this->validateUser($data);
            
            if (!empty($errors)) {
                return $this->view('users/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'title' => 'Create User'
                ]);
            }
            
            // Example of inserting data
            $userId = $this->db->insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Redirect to the user profile
            return $this->redirect("/users/{$userId}", [
                'success' => 'User created successfully'
            ]);
        } catch (\Exception $e) {
            return $this->view('errors/database', [
                'error' => $e->getMessage(),
                'title' => 'Database Error'
            ]);
        }
    }
    
    /**
     * Example of using JSON data source
     * 
     * @param Request $request
     * @return Response
     */
    public function jsonUsers(Request $request): Response
    {
        try {
            // Get all users from the JSON file
            $users = $this->jsonUsers->getData();
            
            return $this->view('users/json-list', [
                'users' => $users,
                'title' => 'JSON User List',
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            return $this->view('errors/data', [
                'error' => $e->getMessage(),
                'title' => 'Data Source Error'
            ]);
        }
    }
    
    /**
     * Search by name in JSON data
     * 
     * @param Request $request
     * @return Response
     */
    public function searchJson(Request $request): Response
    {
        try {
            $query = $request->getQuery('q', '');
            
            // Filter the JSON data for matching names
            $users = $this->jsonUsers->filter(function($user) use ($query) {
                return !empty($query) && 
                       is_array($user) &&
                       isset($user['name']) &&
                       stripos($user['name'], $query) !== false;
            });
            
            return $this->view('users/json-list', [
                'users' => $users,
                'title' => 'Search Results',
                'count' => count($users),
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return $this->view('errors/data', [
                'error' => $e->getMessage(),
                'title' => 'Data Source Error'
            ]);
        }
    }
    
    /**
     * Example of validation
     * 
     * @param array $data
     * @return array
     */
    protected function validateUser(array $data): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is invalid';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        return $errors;
    }
}