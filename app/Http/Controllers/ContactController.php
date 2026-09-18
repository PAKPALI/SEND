<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->contacts()->withCount('groups')->latest();
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('phone', 'like', '%'.$request->string('q').'%'));
        $contacts = $query->paginate($this->perPage($request))->withQueryString();
        return view('contacts.index', compact('contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:30'], 'country_code' => ['nullable', 'string', 'size:2'], 'email' => ['nullable', 'email', 'max:180']]);
        $data['phone'] = $this->normalizePhone($data['phone'], $data['country_code'] ?? $request->user()->country_code);
        $data['country_code'] = strtoupper($data['country_code'] ?? $request->user()->country_code);
        $request->user()->contacts()->updateOrCreate(['phone' => $data['phone']], $data);
        return back()->with('success', 'Contact ajouté à votre carnet.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        $first = fgetcsv($handle, 0, $this->delimiter($request->file('file')->getRealPath()));
        if (!$first) return back()->withErrors(['file' => 'Le fichier est vide.']);
        $delimiter = $this->delimiter($request->file('file')->getRealPath());
        $headers = array_map(fn ($value) => Str::of((string) $value)->lower()->ascii()->replace([' ', '-', '_'], '')->trim()->toString(), $first);
        $knownHeaders = array_intersect($headers, ['nom', 'name', 'prenom', 'fullname', 'telephone', 'phone', 'tel', 'contact', 'mobile', 'numero', 'numerodetelephone', 'country', 'pays', 'email']);
        $hasHeader = count($knownHeaders) > 0;
        if (!$hasHeader) {
            rewind($handle);
            $headers = ['name', 'phone', 'country_code', 'email'];
        }
        $imported = 0; $skipped = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) continue;
            $values = array_pad($row, count($headers), null);
            $record = array_combine($headers, array_slice($values, 0, count($headers)));
            $firstName = trim((string) ($record['prenom'] ?? ''));
            $lastName = trim((string) ($record['nom'] ?? ''));
            $name = trim(implode(' ', array_filter([
                $lastName ?: ($record['name'] ?? ''),
                $firstName,
            ]))) ?: trim((string) ($record['fullname'] ?? ''));
            $phoneValue = trim((string) ($record['telephone'] ?? $record['phone'] ?? $record['tel'] ?? $record['contact'] ?? $record['mobile'] ?? $record['numero'] ?? $record['numerodetelephone'] ?? ''));
            if ($phoneValue === '' && !$hasHeader) $phoneValue = trim((string) ($values[1] ?? ''));
            if ($name === '' || $phoneValue === '') { $skipped++; continue; }
            $country = strtoupper(trim((string) ($record['country_code'] ?? $record['country'] ?? $record['pays'] ?? $request->user()->country_code)));
            $phones = $this->extractPhones($phoneValue, $country);
            if ($phones === []) { $skipped++; continue; }
            foreach ($phones as $phone) {
                $request->user()->contacts()->updateOrCreate(['phone' => $phone], ['name' => $name, 'phone' => $phone, 'country_code' => $country ?: $request->user()->country_code, 'email' => $record['email'] ?? null, 'status' => 'active']);
                $imported++;
            }
        }
        fclose($handle);
        return back()->with('success', "$imported contact(s) importé(s).".($skipped ? " $skipped ligne(s) ignorée(s)." : ''));
    }

    public function destroy(Request $request, Contact $contact)
    {
        abort_unless($contact->user_id === $request->user()->id, 403);
        $contact->delete();
        return back()->with('success', 'Contact supprimé.');
    }

    private function delimiter(string $path): string
    {
        $line = (string) fgets(fopen($path, 'rb'));
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private function normalizePhone(string $phone, string $country): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone) ?: '';
        if (str_starts_with($phone, '00')) $phone = '+'.substr($phone, 2);
        if (!str_starts_with($phone, '+') && strtoupper($country) === 'TG') $phone = '+228'.$phone;
        return $phone;
    }

    private function extractPhones(string $value, string $country): array
    {
        $value = trim($value);
        preg_match_all('/\+\d{8,15}/', $value, $internationalMatches);
        $candidates = $internationalMatches[0] ?? [];

        if ($candidates === []) {
            $candidates = preg_split('/[\s,;\/|]+/', str_replace(["\r", "\n"], ' ', $value), -1, PREG_SPLIT_NO_EMPTY);
        }

        return collect($candidates)
            ->map(fn ($phone) => $this->normalizePhone((string) $phone, $country))
            ->filter(fn ($phone) => $phone !== '' && preg_match('/^\+\d{8,15}$/', $phone))
            ->unique()
            ->values()
            ->all();
    }
}
