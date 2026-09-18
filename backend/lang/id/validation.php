<?php

/*
|--------------------------------------------------------------------------
| Pesan validasi (Bahasa Indonesia)
|--------------------------------------------------------------------------
|
| APP_LOCALE=id, and the SPA shows these messages verbatim (ApiError.firstError),
| so every rule the API uses needs an Indonesian sentence. Controllers still
| override the most common ones with more specific wording.
|
*/

return [
    'accepted' => ':Attribute harus disetujui.',
    'array' => ':Attribute harus berupa daftar.',
    'boolean' => ':Attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'different' => ':Attribute dan :other harus berbeda.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak ditemukan.',
    'extensions' => ':Attribute harus berformat: :values.',
    'file' => ':Attribute harus berupa berkas.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => ':Attribute maksimal berisi :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobita.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berformat: :values.',
    'mimetypes' => ':Attribute harus berformat: :values.',
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobita.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'present' => ':Attribute wajib ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_if' => ':Attribute wajib diisi bila :other adalah :value.',
    'required_with' => ':Attribute wajib diisi bila :values diisi.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobita.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus :size karakter.',
    ],
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'url' => 'Format :attribute tidak valid.',

    'custom' => [],

    /*
    | Human names for request fields, used in place of :attribute.
    */
    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'phone' => 'nomor telepon',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
        'role' => 'peran',
        'category' => 'kategori',
        'location' => 'wilayah',
        'address' => 'alamat',
        'hours' => 'jam buka',
        'ig' => 'Instagram',
        'price_label' => 'kisaran harga',
        'tag' => 'deskripsi singkat',
        'status' => 'status',
        'text' => 'isi',
        'stars' => 'jumlah bintang',
        'reply' => 'balasan',
        'photo' => 'foto',
        'video' => 'video',
        'title' => 'judul',
        'url' => 'tautan',
        'file' => 'file',
        'rows' => 'baris data',
    ],
];
