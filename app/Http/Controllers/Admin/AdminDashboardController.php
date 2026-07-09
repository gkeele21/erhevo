<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Category;
use App\Models\CfmStudyYear;
use App\Models\ChurchCalling;
use App\Models\Post;
use App\Models\Talk;
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
                'authors' => Author::count(),
                'callings' => ChurchCalling::count(),
                'talks' => Talk::count(),
                'categories' => Category::count(),
                'pendingCategories' => Category::where('is_approved', false)->count(),
                'studyYears' => CfmStudyYear::count(),
            ],
        ]);
    }
}
