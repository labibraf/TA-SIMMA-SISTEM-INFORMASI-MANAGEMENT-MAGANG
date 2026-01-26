# DEADLINE MANAGEMENT - TUGAS GUGUR

## 📋 Konsep

Tugas yang **melewati deadline** akan dianggap **GUGUR**:

- ❌ Tidak bisa upload laporan harian
- ❌ Tidak terhitung ke target waktu
- ✅ Hanya bisa lihat (read-only)

---

## 🔧 Implementasi

### 1. Controller Validation

**File**: `app/Http/Controllers/LaporanHarianController.php`

```php
// Block upload jika deadline lewat
if ($penugasan->deadline && now()->greaterThan($penugasan->deadline->endOfDay())) {
    Alert::error('Tugas Gugur', 'Tugas ini sudah melewati deadline dan dianggap gugur.');
    return redirect()->route('penugasans.show', $penugasan->id);
}

// Exclude dari query
->where(function($query) {
    $query->whereNull('deadline')
          ->orWhere('deadline', '>=', now()->startOfDay());
})
```

### 2. Exclude dari Perhitungan

**File**: `app/Models/Peserta.php`

```php
// Hitung waktu - EXCLUDE tugas gugur
$totalWaktuIndividu = $this->penugasan()
    ->where('status_tugas', 'Selesai')
    ->where('is_approved', 1)
    ->where(function($query) {
        $query->whereNull('deadline')
              ->orWhere('deadline', '>=', now()->startOfDay());
    })
    ->sum('beban_waktu');
```

### 3. UI Indicator

**File**: `resources/views/Penugasan/show.blade.php`

```blade
@php
    $isOverdue = $penugasan->deadline && now()->greaterThan($penugasan->deadline->endOfDay());
@endphp

@if($isOverdue)
    <span class="badge bg-danger">Gugur (Lewat Deadline)</span>
    <span class="badge bg-dark">Tidak Terhitung</span>
@endif
```

---

## ✅ Checklist

- [x] Validasi deadline di controller
- [x] Filter query exclude tugas gugur
- [x] Exclude dari perhitungan waktu
- [x] Badge UI di index & show
- [x] Block button tambah laporan
- [x] Cast deadline ke `datetime` di model

---

**Status**: ✅ Production Ready  
**Date**: 2025-12-15
