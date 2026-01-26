<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Bagian;
use App\Models\Mentor;
use RealRashid\SweetAlert\Facades\Alert;

class PesertaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $peserta = collect([]);

        if ($user && $user->role) {
            if (($user->role_id == 1) || ($user->role->role_name == 'Admin')) {
                $peserta = Peserta::with(['user', 'bagian', 'mentor'])
                         ->orderBy('id', 'desc')
                         ->get(); // Gunakan array untuk konsistensi

            } elseif (($user->role_id == 2) || ($user->role->role_name == 'Mentor')) {
                if ($user->mentor) {
                    $mentorId = $user->mentor->id;
                    $peserta = Peserta::with(['user', 'bagian', 'mentor'])
                                    ->where('mentor_id', $mentorId)
                                    ->orderBy('id', 'desc')
                                    ->get();
                }
            }
        }
        return view('peserta.index', compact('peserta'));
    }

    public function create()
    {
        $user = Auth::user();
        $bagians = Bagian::all();

        // Untuk admin: ambil semua mentor dengan relasi bagian
        if($user && $user->role && $user->role->role_name == 'Admin') {
            $mentors = Mentor::with('bagian')->get();
            $isAdmin = true;
        }
        // Untuk mentor: hanya diri sendiri
        elseif($user && $user->role && $user->role->role_name == 'Mentor' && $user->mentor) {
            $mentors = collect([$user->mentor]);
            $isAdmin = false;
        }
        // Default
        else {
            $mentors = collect();
            $isAdmin = false;
        }

        return view('peserta.create', compact('bagians', 'mentors', 'isAdmin'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nomor_identitas' => 'required|string|min:6|max:20|unique:pesertas', // NIK/KTP, harus unik
            'email' => 'required|string|email|max:255|unique:pesertas,email', // Email, harus unik dan format email
            'no_telepon' => 'required|string|max:20', // Nomor telepon
            'alamat' => 'required|string|max:500',
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])], // Harus salah satu dari pilihan
            'asal_instansi' => 'required|string|max:255',
            'jurusan' => 'required|string|max:255',
            'tipe_magang' => ['required', Rule::in(['Kerja Praktik', 'Magang Nasional', 'Penelitian'])], // Harus salah satu dari pilihan
            'tanggal_mulai_magang' => 'required|date', // Harus format tanggal
            'tanggal_selesai_magang' => 'required|date|after_or_equal:tanggal_mulai_magang', // Harus tanggal dan setelah/sama dengan tanggal mulai
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Foto opsional, harus gambar dengan ukuran maksimal 2MB
            'bagian_id' => 'required|exists:bagians,id',
            'mentor_id' => 'required|exists:mentors,id',
            'target_method' => ['required', Rule::in(['sks', 'manual'])],
            'sks' => 'required|integer|min:1|max:30', // SKS selalu required
            'target_waktu_manual' => 'required_if:target_method,manual|integer|min:1',
            ],[
                    // Pesan error kustom untuk setiap aturan validasi
                'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
                'nama_lengkap.string' => 'Nama lengkap harus berupa teks.',
                'nama_lengkap.max' => 'Nama lengkap tidak boleh lebih dari :max karakter.',

                'nomor_identitas.required' => 'Nomor identitas wajib diisi.',
                'nomor_identitas.string' => 'Nomor identitas harus berupa teks.',
                'nomor_identitas.min' => 'Nomor identitas minimal :min karakter.',
                'nomor_identitas.max' => 'Nomor identitas maksimal :max karakter.',
                'nomor_identitas.unique' => 'Nomor identitas ini sudah terdaftar.',

                'email.required' => 'Email wajib diisi.',
                'email.string' => 'Email harus berupa teks.',
                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email tidak boleh lebih dari :max karakter.',
                'email.unique' => 'Email ini sudah terdaftar.',

                'no_telepon.required' => 'Nomor telepon wajib diisi.',
                'no_telepon.string' => 'Nomor telepon harus berupa teks.',
                'no_telepon.max' => 'Nomor telepon tidak boleh lebih dari :max karakter.',

                'alamat.required' => 'Alamat wajib diisi.',
                'alamat.string' => 'Alamat harus berupa teks.',
                'alamat.max' => 'Alamat tidak boleh lebih dari :max karakter.',

                'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
                'jenis_kelamin.in' => 'Jenis kelamin yang dipilih tidak valid.',

                'asal_instansi.required' => 'Asal instansi wajib diisi.',
                'asal_instansi.string' => 'Asal instansi harus berupa teks.',
                'asal_instansi.max' => 'Asal instansi tidak boleh lebih dari :max karakter.',

                'jurusan.required' => 'Jurusan wajib diisi.',
                'jurusan.string' => 'Jurusan harus berupa teks.',
                'jurusan.max' => 'Jurusan tidak boleh lebih dari :max karakter.',

                'tipe_magang.required' => 'Tipe magang wajib dipilih.',
                'tipe_magang.in' => 'Tipe magang yang dipilih tidak valid.',

                'tanggal_mulai_magang.required' => 'Tanggal mulai magang wajib diisi.',
                'tanggal_mulai_magang.date' => 'Format tanggal mulai magang tidak valid.',

                'tanggal_selesai_magang.required' => 'Tanggal selesai magang wajib diisi.',
                'tanggal_selesai_magang.date' => 'Format tanggal selesai magang tidak valid.',
                'tanggal_selesai_magang.after_or_equal' => 'Tanggal selesai magang harus setelah atau sama dengan tanggal mulai magang.',

                'bagian_id.required' => 'Bagian wajib dipilih.',
                'bagian_id.exists' => 'Bagian yang dipilih tidak valid.',

                'mentor_id.required' => 'Mentor penanggung jawab wajib dipilih.',
                'mentor_id.exists' => 'Mentor yang dipilih tidak valid.',

                'target_method.required' => 'Metode target waktu wajib dipilih.',
                'target_method.in' => 'Metode target waktu yang dipilih tidak valid.',

                'sks.required_if' => 'Jumlah SKS wajib diisi ketika memilih metode SKS.',
                'sks.integer' => 'Jumlah SKS harus berupa angka.',
                'sks.min' => 'Jumlah SKS minimal :min.',
                'sks.max' => 'Jumlah SKS maksimal :max.',

                'target_waktu_manual.required_if' => 'Target waktu wajib diisi ketika memilih input manual.',
                'target_waktu_manual.integer' => 'Target waktu harus berupa angka.',
                'target_waktu_manual.min' => 'Target waktu minimal :min jam.',
            ]);

        $data = $request->except(['foto', 'target_waktu_manual']);

        // Pastikan SKS selalu tersimpan dari input form
        $data['sks'] = $request->sks ?? 0;

        // Hitung target waktu berdasarkan metode yang dipilih
        if ($request->target_method === 'sks') {
            $data['target_waktu_tugas'] = round(($request->sks * 45), 2);
            // SKS sudah di-set di atas, tidak perlu set ulang
        } else {
            $data['target_waktu_tugas'] = $request->target_waktu_manual;
            // Tetap simpan SKS dari input form meskipun menggunakan metode manual
        }

        // waktu_maksimum akan dihitung otomatis oleh accessor di Model berdasarkan hari kerja
        $data['waktu_tugas_tercapai'] = 0;

        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $filename = Str::uuid() . '.' . $foto->getClientOriginalExtension();

            // Simpan file ke storage/app/public/foto_peserta
            $foto->storeAs('foto_peserta', $filename, 'public');

            $data['foto'] = $filename;
        }

        $newData = Peserta::create($data);
        $user = User::create([
            'name' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => Hash::make('password'),
            'peserta_id' => $newData->id,
        ]);
        $newData->user_id = $user->id;
        $newData->save();

        Alert::success('Success', 'Peserta berhasil ditambahkan.');
        return redirect()->route('peserta.index');
    }

    public function edit(string $id)
    {
        $peserta = Peserta::findOrFail($id);
        $bagians = Bagian::all();

        // Tambahkan logika untuk $isAdmin dan $mentors seperti di create()
        $user = Auth::user();
        $isAdmin = false;
        $mentors = collect(); // Default koleksi kosong

        // Untuk admin: ambil semua mentor dengan relasi bagian
        if($user && $user->role && $user->role->role_name == 'Admin') {
            $mentors = Mentor::with('bagian')->get();
            $isAdmin = true;
        }
        // Untuk mentor: hanya diri sendiri (meskipun untuk edit, admin biasanya yang mengubah)
        elseif($user && $user->role && $user->role->role_name == 'Mentor' && $user->mentor) {
            $mentors = collect([$user->mentor]);
            $isAdmin = false;
        }

        // Jika bukan admin, Anda mungkin tidak ingin menampilkan dropdown mentor/bagian
        // Tapi tetap kirim data untuk konsistensi view

        return view('peserta.edit', compact('peserta', 'bagians', 'isAdmin', 'mentors'));
    }

    public function update(Request $request, $id)
    {
            $peserta = Peserta::findOrFail($id);

            // Cek apakah data akademis dapat diedit
            if (!$peserta->can_edit_data_akademis) {
                Alert::error('Tidak Dapat Diedit', 'Data akademis peserta tidak dapat diubah karena laporan akhir sudah diterima. Batalkan laporan akhir terlebih dahulu jika ingin mengubah data.');
                return redirect()->back();
            }

            // Validasi tambahan: cek apakah field terproteksi berubah
            $protectedFields = $peserta->protected_fields;
            foreach ($protectedFields as $field) {
                if ($request->has($field)) {
                    $oldValue = $peserta->$field;
                    $newValue = $request->$field;

                    // Khusus untuk target_waktu_tugas yang bisa berubah berdasarkan method
                    if ($field === 'target_waktu_tugas') {
                        if ($peserta->target_method === 'manual') {
                            $newValue = $request->target_waktu_manual;
                        } else {
                            continue; // Skip validasi untuk SKS method
                        }
                    }

                    if ($oldValue != $newValue) {
                        Alert::error('Perubahan Tidak Diizinkan', "Field {$field} tidak dapat diubah karena laporan akhir sudah diterima.");
                        return redirect()->back();
                    }
                }
            }

            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'nomor_identitas' => [
                    'required',
                    'string',
                    'min:6',
                    'max:20',
                    Rule::unique('pesertas')->ignore($peserta->id),
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('pesertas')->ignore($peserta->id),
                ],
                'no_telepon' => 'required|string|max:20',
                'alamat' => 'required|string|max:500',
                'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
                'asal_instansi' => 'required|string|max:255',
                'jurusan' => 'required|string|max:255',
                'tipe_magang' => ['required', Rule::in(['Kerja Praktik', 'Magang Nasional', 'Penelitian'])],
                'tanggal_mulai_magang' => 'required|date',
                'tanggal_selesai_magang' => 'required|date|after_or_equal:tanggal_mulai_magang',
                'foto' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'bagian_id' => 'required|exists:bagians,id',
                'mentor_id' => 'required|exists:mentors,id',
                'target_method' => ['required', Rule::in(['sks', 'manual'])],
                'sks' => 'required|integer|min:1|max:30', // SKS selalu required
                'target_waktu_manual' => 'required_if:target_method,manual|integer|min:1',
            ],[
                // Pesan error kustom untuk setiap aturan validasi
                'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
                'nama_lengkap.string' => 'Nama lengkap harus berupa teks.',
                'nama_lengkap.max' => 'Nama lengkap tidak boleh lebih dari :max karakter.',

                'nomor_identitas.required' => 'Nomor identitas wajib diisi.',
                'nomor_identitas.string' => 'Nomor identitas harus berupa teks.',
                'nomor_identitas.min' => 'Nomor identitas minimal :min karakter.',
                'nomor_identitas.max' => 'Nomor identitas maksimal :max karakter.',
                'nomor_identitas.unique' => 'Nomor identitas ini sudah terdaftar.',

                'email.required' => 'Email wajib diisi.',
                'email.string' => 'Email harus berupa teks.',
                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email tidak boleh lebih dari :max karakter.',
                'email.unique' => 'Email ini sudah terdaftar.',

                'no_telepon.required' => 'Nomor telepon wajib diisi.',
                'no_telepon.string' => 'Nomor telepon harus berupa teks.',
                'no_telepon.max' => 'Nomor telepon tidak boleh lebih dari :max karakter.',

                'alamat.required' => 'Alamat wajib diisi.',
                'alamat.string' => 'Alamat harus berupa teks.',
                'alamat.max' => 'Alamat tidak boleh lebih dari :max karakter.',

                'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
                'jenis_kelamin.in' => 'Jenis kelamin yang dipilih tidak valid.',

                'asal_instansi.required' => 'Asal instansi wajib diisi.',
                'asal_instansi.string' => 'Asal instansi harus berupa teks.',
                'asal_instansi.max' => 'Asal instansi tidak boleh lebih dari :max karakter.',

                'jurusan.required' => 'Jurusan wajib diisi.',
                'jurusan.string' => 'Jurusan harus berupa teks.',
                'jurusan.max' => 'Jurusan tidak boleh lebih dari :max karakter.',

                'tipe_magang.required' => 'Tipe magang wajib dipilih.',
                'tipe_magang.in' => 'Tipe magang yang dipilih tidak valid.',

                'tanggal_mulai_magang.required' => 'Tanggal mulai magang wajib diisi.',
                'tanggal_mulai_magang.date' => 'Format tanggal mulai magang tidak valid.',

                'tanggal_selesai_magang.required' => 'Tanggal selesai magang wajib diisi.',
                'tanggal_selesai_magang.date' => 'Format tanggal selesai magang tidak valid.',
                'tanggal_selesai_magang.after_or_equal' => 'Tanggal selesai magang harus setelah atau sama dengan tanggal mulai magang.',

                'bagian_id.required' => 'Bagian wajib dipilih.',
                'bagian_id.exists' => 'Bagian yang dipilih tidak valid.',

                'mentor_id.required' => 'Mentor penanggung jawab wajib dipilih.',
                'mentor_id.exists' => 'Mentor yang dipilih tidak valid.',

                'target_method.required' => 'Metode target waktu wajib dipilih.',
                'target_method.in' => 'Metode target waktu yang dipilih tidak valid.',

                'sks.required_if' => 'Jumlah SKS wajib diisi ketika memilih metode SKS.',
                'sks.integer' => 'Jumlah SKS harus berupa angka.',
                'sks.min' => 'Jumlah SKS minimal :min.',
                'sks.max' => 'Jumlah SKS maksimal :max.',

                'target_waktu_manual.required_if' => 'Target waktu wajib diisi ketika memilih input manual.',
                'target_waktu_manual.integer' => 'Target waktu harus berupa angka.',
                'target_waktu_manual.min' => 'Target waktu minimal :min jam.',
            ]);

            $dataToUpdate = $request->except(['foto', 'target_waktu_manual']); // Ambil semua data kecuali 'foto'

            // Pastikan SKS selalu tersimpan dari input form
            $dataToUpdate['sks'] = $request->sks ?? 0;

            // Hitung target waktu berdasarkan metode yang dipilih
            if ($request->target_method === 'sks') {
                $dataToUpdate['target_waktu_tugas'] = round(($request->sks * 45), 2);
                // SKS sudah di-set di atas, tidak perlu set ulang
            } else {
                $dataToUpdate['target_waktu_tugas'] = $request->target_waktu_manual;
                // Tetap simpan SKS dari input form meskipun menggunakan metode manual
            }

            // waktu_maksimum akan dihitung otomatis oleh accessor di Model berdasarkan hari kerja

            // Cek apakah ada file foto baru di request
            if ($request->hasFile('foto')) {
            // Jika ada, hapus foto lama dari storage (jika ada)
                if ($peserta->foto && Storage::disk('public')->exists('foto_peserta/' . $peserta->foto)) {
                    Storage::disk('public')->delete('foto_peserta/' . $peserta->foto);
                }

                $file = $request->file('foto');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                // Simpan file ke storage/app/public/foto_peserta
                $file->storeAs('foto_peserta', $filename, 'public');

                $dataToUpdate['foto'] = $filename;
            }            $peserta->update($dataToUpdate);

            Alert::success('Success', 'Data peserta berhasil diperbarui.');
            // 5. Redirect dengan pesan sukses
            return redirect()->route('peserta.index');
            //
    }

    public function assignSks(Request $request, $id)
    {
            $peserta = Peserta::findOrFail($id);
            $request->validate([
                'target_method' => ['required', Rule::in(['sks', 'manual'])],
                'sks' => 'required|integer|min:1|max:30', // SKS selalu required
                'target_waktu_manual' => 'required_if:target_method,manual|integer|min:1',
                'tipe_magang' => ['required', Rule::in(['Kerja Praktik', 'Magang Nasional', 'Penelitian'])],
            ]);

            $updateData = [
                'tipe_magang' => $request->tipe_magang,
                'target_method' => $request->target_method,
                'sks' => $request->sks ?? 0, // Pastikan SKS selalu tersimpan
            ];

            // Hitung target waktu berdasarkan metode yang dipilih
            if ($request->target_method === 'sks') {
                $updateData['target_waktu_tugas'] = round(($request->sks * 45), 2);
                // SKS sudah di-set di atas, tidak perlu set ulang
            } else {
                $updateData['target_waktu_tugas'] = $request->target_waktu_manual;
                // Tetap simpan SKS dari input form meskipun menggunakan metode manual
            }

            // waktu_maksimum akan dihitung otomatis oleh accessor di Model berdasarkan hari kerja

            $peserta->update($updateData);
            return redirect()->route('peserta.index');
    }

    public function show($id)
    {
        // Eager load relasi yang dibutuhkan dan refresh data terbaru
        $peserta = Peserta::with(['user', 'bagian', 'penugasan', 'mentor', 'laporanHarian.penugasan'])->findOrFail($id);

        // Refresh data dari database untuk memastikan data terbaru
        $peserta->refresh();

        // Update waktu tugas tercapai jika diperlukan
        $peserta->updateWaktuTugasTercapai();

        // Ambil semua tugas peserta (individu + divisi) menggunakan method getTugasPeserta()
        $semuaTugas = $peserta->getTugasPeserta();

        return view('peserta.show', compact('peserta', 'semuaTugas'));
    }



    public function destroy(String $id)
    {
            $peserta = Peserta::with('user')->findOrFail($id);

            // Hapus foto jika ada
            if ($peserta->foto && Storage::disk('public')->exists('foto_peserta/' . $peserta->foto)) {
                Storage::disk('public')->delete('foto_peserta/' . $peserta->foto);
            }

            // Hapus user terkait jika ada
            if ($peserta->user) {
                $peserta->user->delete();
            }

            // Hapus peserta
            $peserta->delete();

            Alert::success('Success', 'Data peserta dan akun user terkait berhasil dihapus.');
            return redirect()->route('peserta.index');
    }
}
