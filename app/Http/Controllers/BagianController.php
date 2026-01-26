<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bagian;
use RealRashid\SweetAlert\Facades\Alert;

class BagianController extends Controller
{
    public function index()
    {
        $bagians = Bagian::withCount(['pesertas', 'mentors'])->get();
        return view('bagian.index', compact('bagians'));
    }

    public function create()
    {
        return view('bagian.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_bagian' => 'required|string|max:255|unique:bagians,nama_bagian',
        ]);

        Bagian::create([
            'nama_bagian' => $request->nama_bagian,
        ]);

        Alert::success('Success', 'Data bagian berhasil ditambahkan.');
        return redirect()->route('bagian.index');
    }

    public function show($id)
    {
        $bagian = Bagian::findOrFail($id);
        return view('bagian.show', compact('bagian'));
    }

    public function edit($id)
    {
        $bagian = Bagian::withCount(['pesertas', 'mentors'])->findOrFail($id);
        return view('bagian.edit', compact('bagian'));
    }

    public function update(Request $request, $id)
    {
        $bagian = Bagian::findOrFail($id);

        $request->validate([
            'nama_bagian' => 'required|string|max:255|unique:bagians,nama_bagian,' . $id,
        ]);

        $bagian->update([
            'nama_bagian' => $request->nama_bagian,
        ]);

        Alert::success('Success', 'Data bagian berhasil diperbarui.');
        return redirect()->route('bagian.index');
    }

    public function destroy($id)
    {
        $bagian = Bagian::findOrFail($id);
        $bagian->delete();
        Alert::success('Success', 'Data bagian berhasil dihapus.');
        return redirect()->route('bagian.index');
    }
}
