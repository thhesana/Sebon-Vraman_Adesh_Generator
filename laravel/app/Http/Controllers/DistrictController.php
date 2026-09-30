<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    private const PER_PAGE = 5;

    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $offset = ($page - 1) * self::PER_PAGE;
        $like = '%'.$search.'%';

        $query = District::query()->where(function ($q) use ($like) {
            $q->where('District_name', 'like', $like)
                ->orWhere('District_name_nepali', 'like', $like);
        });

        $totalRows = (clone $query)->count();
        $totalPages = (int) ceil($totalRows / self::PER_PAGE);

        $districts = $query
            ->select(['District_id', 'District_name', 'District_name_nepali'])
            ->orderByDesc('District_id')
            ->offset($offset)
            ->limit(self::PER_PAGE)
            ->get();

        return view('district.index', compact('districts', 'page', 'search', 'offset', 'totalRows', 'totalPages'));
    }

    public function create()
    {
        return view('district.create', [
            'messages' => [],
            'success' => '',
            'district_name' => '',
            'district_name_nepali' => '',
        ]);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('district_name'));
        $nepali = trim((string) $request->input('district_name_nepali'));

        $messages = [];
        if ($name === '') {
            $messages[] = 'District name (English) is required.';
        }
        if ($nepali === '') {
            $messages[] = 'District name (Nepali) is required.';
        }

        if (! $messages) {
            $exists = District::where('District_name', $name)
                ->orWhere('District_name_nepali', $nepali)
                ->exists();
            if ($exists) {
                $messages[] = 'District already exists.';
            }
        }

        if ($messages) {
            return view('district.create', [
                'messages' => $messages,
                'success' => '',
                'district_name' => $name,
                'district_name_nepali' => $nepali,
            ]);
        }

        District::create(['District_name' => $name, 'District_name_nepali' => $nepali]);

        return view('district.create', [
            'messages' => [],
            'success' => 'District added successfully.',
            'district_name' => '',
            'district_name_nepali' => '',
        ]);
    }
}
