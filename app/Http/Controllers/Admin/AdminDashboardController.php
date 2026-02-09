<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CfmStudyYear;
use App\Models\Post;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'posts' => Post::count(),
                'categories' => Category::count(),
                'pendingCategories' => Category::where('is_approved', false)->count(),
                'studyYears' => CfmStudyYear::count(),
            ],
        ]);
    }
}
