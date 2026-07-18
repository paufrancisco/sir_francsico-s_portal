<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFaqController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => Faq::with('section')->latest()->get(),
            'sections' => Section::orderBy('name')->orderBy('subject')->get(['id', 'name', 'subject']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'section_id' => 'nullable|exists:sections,id',
        ]);
        Faq::create($request->only('question', 'answer', 'section_id'));
        return back()->with('success', 'Naidagdag ang FAQ.');
    }

    public function update(Request $request, Faq $faq)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'section_id' => 'nullable|exists:sections,id',
        ]);
        $faq->update($request->only('question', 'answer', 'section_id'));
        return back()->with('success', 'Na-update ang FAQ.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return back()->with('success', 'Natanggal ang FAQ.');
    }
}