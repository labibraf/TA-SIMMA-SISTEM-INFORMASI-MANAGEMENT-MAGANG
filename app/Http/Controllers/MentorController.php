<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use App\Models\User;
use App\Models\Bagian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use RealRashid\SweetAlert\Facades\Alert;

class MentorController extends Controller
{
    public function index()
    {
        $mentors = Mentor::with('user','bagian')->orderBy('id', 'desc')->get();
        return view('mentor.index', compact('mentors'));
    }

    public function create()
    {
        $bagians = Bagian::all();
        return view('mentor.create', compact('bagians'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mentor' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:mentors,email',
            'no_telepon' => 'required|string|max:20',
            'nomor_identitas' => 'required|string|numeric|unique:mentors,nomor_identitas',
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'keahlian' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'bagian_id' => 'required|exists:bagians,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ],
        [
            'nama_mentor.required' => 'Nama mentor wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari :max karakter.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'no_telepon.required' => 'Nomor telepon wajib diisi.',
            'no_telepon.string' => 'Nomor telepon harus berupa teks.',
            'no_telepon.max' => 'Nomor telepon tidak boleh lebih dari :max karakter.',
            'nomor_identitas.required' => 'Nomor identitas wajib diisi.',
            'nomor_identitas.string' => 'Nomor identitas harus berupa teks.',
            'nomor_identitas.unique' => 'Nomor identitas ini sudah terdaftar.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin yang dipilih tidak valid.',
            'keahlian.required' => 'Keahlian wajib diisi.',
            'keahlian.string' => 'Keahlian harus berupa teks.',
            'keahlian.max' => 'Keahlian tidak boleh lebih dari :max karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'alamat.max' => 'Alamat tidak boleh lebih dari :max karakter.',
            'bagian_id.required' => 'Bagian wajib dipilih.',
            'bagian_id.exists' => 'Bagian yang dipilih tidak valid.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Format gambar tidak valid.',
            'foto.max' => 'Ukuran gambar tidak boleh lebih dari :max kilobyte.',
        ]
    );

    $data = $request->except('foto');

    if ($request->hasFile('foto')) {
        $foto = $request->file('foto');
        $filename = Str::uuid() . '.' . $foto->getClientOriginalExtension();

        Storage::disk('public')->putFileAs('foto_mentor', $foto, $filename);
        // Store the file in the public disk under the foto_mentor directory
        // $path = $foto->storeAs('foto_mentor', $filename, 'public');

        // Add the file path to the data array
        $data['foto'] = $filename;
        }
        else {
        $filename = $mentor->foto;
        }

    $newData = Mentor::create($data);
    try{
         // Create the record with all data including the photo path
    $user = User::create([
        'name' => $request->nama_mentor,
        'email' => $request->email,
        'password' => Hash::make('password'),
        'mentor_id' => $newData->id,
    ]);
    $newData->user_id = $user->id;
    $newData->save();
    Alert::success('Success', 'Mentor berhasil ditambahkan.');
    return redirect()->route('mentor.index');
    }catch(Exception $e){
        // Alert::error('Error', 'Gagal menambahkan mentor.');
        // return redirect()->route('mentors.index');
        $newData->delete();
        Alert::error('Error', 'Gagal menambahkan mentor.');
        return redirect()->route('mentor.index');
    }
    }

    public function edit(String $id)
    {
        $mentor = Mentor::findOrFail($id);
        $bagians = Bagian::all();
        return view('mentor.edit', compact('mentor', 'bagians'));
    }

    public function show(String $id)
    {
        $mentor = Mentor::with(['bagian', 'user', 'pesertas.bagian'])->findOrFail($id);
        return view('mentor.show', compact('mentor'));
    }

    public function getMentorsByBagian($bagianId)
    {
        // Validasi input
        $bagianId = (int) $bagianId; // Konversi ke integer

        // Ambil mentor berdasarkan bagian_id
        $mentors = Mentor::where('bagian_id', $bagianId)->get(['id', 'nama_mentor']);

        // Kembalikan respons JSON
        return response()->json($mentors);
    }

    public function update(Request $request, String $id)
    {
        $mentor = Mentor::findOrFail($id);

        $request->validate([
            'nama_mentor' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('mentors')->ignore($mentor->id),
            ],
            'no_telepon' => 'required|string|max:20',
            'nomor_identitas' => [
                'required',
                'string',
                'numeric',
                Rule::unique('mentors')->ignore($mentor->id),
            ],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'keahlian' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'bagian_id' => 'required|exists:bagians,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ],[
            'nama_mentor.required' => 'Nama mentor wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari :max karakter.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'no_telepon.required' => 'Nomor telepon wajib diisi.',
            'no_telepon.string' => 'Nomor telepon harus berupa teks.',
            'no_telepon.max' => 'Nomor telepon tidak boleh lebih dari :max karakter.',
            'nomor_identitas.required' => 'Nomor identitas wajib diisi.',
            'nomor_identitas.string' => 'Nomor identitas harus berupa teks.',
            'nomor_identitas.unique' => 'Nomor identitas ini sudah terdaftar.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin yang dipilih tidak valid.',
            'keahlian.required' => 'Keahlian wajib diisi.',
            'keahlian.string' => 'Keahlian harus berupa teks.',
            'keahlian.max' => 'Keahlian tidak boleh lebih dari :max karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'alamat.max' => 'Alamat tidak boleh lebih dari :max karakter.',
            'bagian_id.required' => 'Bagian wajib dipilih.',
            'bagian_id.exists' => 'Bagian yang dipilih tidak valid.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Format gambar tidak valid.',
            'foto.max' => 'Ukuran gambar tidak boleh lebih dari :max kilobyte.',
        ]);

        $dataToUpdate = $request->except('foto');

        if ($request->hasFile('foto')) {
            if ($mentor->foto && Storage::disk('public')->exists('foto_mentor/' . $mentor->foto)) {
                Storage::disk('public')->delete('foto_mentor/' . $mentor->foto);
            }
            $foto = $request->file('foto');
            $filename = Str::uuid() . '.' . $foto->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('foto_mentor', $foto, $filename);
            $dataToUpdate['foto'] = $filename;
        }

        $mentor->update($dataToUpdate);
        Alert::success('Success', 'Mentor berhasil diupdate.');
        return redirect()->route('mentor.index');
    }

    public function destroy(String $id)
    {
        $mentor = Mentor::with('user')->findOrFail($id);
        if ($mentor->foto && Storage::disk('public')->exists('foto_mentor/' . $mentor->foto)) {
            Storage::disk('public')->delete('foto_mentor/' . $mentor->foto);
        }
        if ($mentor->user) {
            $mentor->user->delete();
        }

        $mentor->delete();
        Alert::success('Success', 'Mentor berhasil dihapus.');
        return redirect()->route('mentor.index');
    }
}

