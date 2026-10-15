<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class SystemSettingsController extends Controller
{
    public function profile()
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        
        // Default values jika belum ada data
        $data = [
            'company_name' => $settings['company_name'] ?? 'Atelier Production',
            'company_address' => $settings['company_address'] ?? '',
            'company_npwp' => $settings['company_npwp'] ?? '',
            'company_phone' => $settings['company_phone'] ?? '',
            'company_email' => $settings['company_email'] ?? '',
            'logo_path' => $settings['logo_path'] ?? null,
        ];

        return view('settings.system.profile', compact('data'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_npwp' => ['nullable', 'string', 'max:50'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', File::image()->max(2048)], // Max 2MB, hanya gambar
        ]);

        // Handle upload logo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('system/logos', 'public');
            
            // Hapus logo lama jika ada
            $oldLogo = DB::table('settings')->where('key', 'logo_path')->value('value');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            
            $this->saveSetting('logo_path', $path);
        }

        // Simpan text settings
        $textFields = ['company_name', 'company_address', 'company_npwp', 'company_phone', 'company_email'];
        foreach ($textFields as $field) {
            $this->saveSetting($field, $validated[$field] ?? '');
        }

        return back()->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    public function preferences()
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        
        $data = [
            'currency' => $settings['currency'] ?? 'IDR',
            'tax_rate' => $settings['tax_rate'] ?? 11,
            'date_format' => $settings['date_format'] ?? 'DD/MM/YYYY',
            'timezone' => $settings['timezone'] ?? 'Asia/Jakarta',
        ];

        return view('settings.system.preferences', compact('data'));
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:3'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'date_format' => ['required', 'string', 'in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD'],
            'timezone' => ['required', 'string', 'max:50'],
        ]);

        foreach ($validated as $key => $value) {
            $this->saveSetting($key, $value);
        }

        return back()->with('success', 'Preferensi sistem berhasil disimpan.');
    }

    private function saveSetting(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now()]
        );
    }
}