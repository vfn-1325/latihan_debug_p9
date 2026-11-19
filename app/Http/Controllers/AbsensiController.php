<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matakuliah;
use App\Models\Absensi;
use App\Models\Mahasiswa;

class AbsensiController extends Controller
{
    /**
     * Display the attendance list (read-only view, cannot override here).
     */
    public function index(Request $request)
    {
        $matkuls = Matakuliah::orderBy('nama_matakuliah')->get();
        $mahasiswas = Mahasiswa::orderBy('NIM')->get();

        $selectedTanggal = $request->input('tanggal_absensi', date('Y-m-d'));
        $selectedMatkulId = $request->input('matakuliah_id', $matkuls->first()?->id);

        $absensis = collect();
        if ($selectedMatkulId && $selectedTanggal) {
            $absensis = Absensi::where('matakuliah_id', (string) $selectedMatkulId)
                ->where('tanggal_absensi', $selectedTanggal)
                ->get()
                ->keyBy('mahasiswa_id');
        }

        return view('absensi.index', [
            'matkuls' => $matkuls,
            'mahasiswas' => $mahasiswas,
            'absensis' => $absensis,
            'selectedTanggal' => $selectedTanggal,
            'selectedMatkulId' => $selectedMatkulId,
        ]);
    }

    /**
     * Show the form for filling / changing attendance.
     */
    public function create(Request $request)
    {
        $matkuls = Matakuliah::orderBy('nama_matakuliah')->get();
        $mahasiswas = Mahasiswa::orderBy('NIM')->get();

        $selectedTanggal = $request->input('tanggal_absensi', date('Y-m-d'));
        $selectedMatkulId = $request->input('matakuliah_id', $matkuls->first()?->id);

        $absensis = collect();
        if ($selectedMatkulId && $selectedTanggal) {
            $absensis = Absensi::where('matakuliah_id', (string) $selectedMatkulId)
                ->where('tanggal_absensi', $selectedTanggal)
                ->get()
                ->keyBy('mahasiswa_id');
        }

        return view('absensi.create', [
            'matkuls' => $matkuls,
            'mahasiswas' => $mahasiswas,
            'absensis' => $absensis,
            'selectedTanggal' => $selectedTanggal,
            'selectedMatkulId' => $selectedMatkulId,
        ]);
    }

    /**
     * Store or update attendance records from the create/ubah page.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_absensi' => 'required|date',
            'matakuliah_id' => 'required|exists:matakuliah,id',
            'status' => 'required|array|min:1',
            'status.*' => 'required|in:H,A,I,S',
        ]);

        // Memastikan seluruh ID mahasiswa valid
        $mahasiswaIds = Mahasiswa::whereIn(
            'id',
            array_keys($validated['status'])
        )->pluck('id')->all();

        if (count($mahasiswaIds) !== count($validated['status'])) {
            return back()
                ->withErrors([
                    'status' => 'Data mahasiswa tidak valid.'
                ])
                ->withInput();
        }

        // Menyimpan atau memperbarui data absensi
        foreach ($validated['status'] as $mahasiswaId => $statusAbsen) {
            Absensi::updateOrCreate(
                [
                    'mahasiswa_id' => (string) $mahasiswaId,
                    'matakuliah_id' => (string) $validated['matakuliah_id'],
                    'tanggal_absensi' => $validated['tanggal_absensi'],
                ],
                [
                    'status_absen' => $statusAbsen,
                ]
            );
        }

        return redirect()
            ->route('absensi.index', [
                'tanggal_absensi' => $validated['tanggal_absensi'],
                'matakuliah_id' => $validated['matakuliah_id'],
            ])
            ->with('success', 'Data kehadiran berhasil disimpan.');
    }
}
