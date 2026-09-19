"""Jalankan:  python -m unittest -v"""

import unittest

from fastapi.testclient import TestClient

from main import app
from nlp import interpret


class InterpretTest(unittest.TestCase):
    def test_contoh_dari_spesifikasi(self):
        r = interpret("aku butuh makanan di balikpapan selatan")
        self.assertEqual(r.intent, "cari_makanan")
        self.assertEqual(r.action, "cari")
        self.assertEqual(r.location, "balikpapan selatan")
        self.assertEqual(r.message, "Baik, mencari makanan di balikpapan selatan.")
        self.assertEqual(r.entities["category"], "Kuliner")
        self.assertEqual(r.entities["location"], "Balikpapan Selatan")
        self.assertEqual(r.entities["keyword"], "")

    def test_kata_bangun_dan_perintah_sekaligus(self):
        r = interpret("Oke Near By, carikan aku kopi di Balik Papan Utara", require_wake=True)
        self.assertTrue(r.wake)
        self.assertEqual(r.intent, "cari_makanan")
        self.assertEqual(r.entities["location"], "Balikpapan Utara")
        self.assertEqual(r.message, "Baik, mencari kopi di balikpapan utara.")

    def test_tanpa_kata_bangun_diabaikan_saat_siaga(self):
        r = interpret("nanti kita makan di selatan ya", require_wake=True)
        self.assertEqual(r.action, "abaikan")
        self.assertFalse(r.wake)

    def test_kata_bangun_saja(self):
        r = interpret("oke nerbi", require_wake=True)
        self.assertEqual(r.action, "siaga_perintah")
        self.assertEqual(r.message, "Ya, silakan.")

    def test_salah_dengar_dikoreksi(self):
        r = interpret("carikan penginapn di balikpapan selatn")
        self.assertEqual(r.intent, "cari_penginapan")
        self.assertEqual(r.location, "balikpapan selatan")

    def test_kata_kerja_dibacakan_sebagai_benda(self):
        self.assertEqual(interpret("aku lapar").message, "Baik, mencari makanan di sekitar Balikpapan.")

    def test_buka_nomor(self):
        r = interpret("buka nomor dua")
        self.assertEqual((r.action, r.entities["index"]), ("buka", 2))

    def test_buka_tanpa_nomor_bukan_detail(self):
        self.assertEqual(interpret("buka halaman panduan").action, "halaman")
        self.assertEqual(interpret("buka kuliner").action, "cari")

    def test_kontrol_menang_atas_cari(self):
        self.assertEqual(interpret("berhenti cari makanan").action, "berhenti")

    def test_tampilkan_semua_dengan_kategori_adalah_pencarian(self):
        r = interpret("tampilkan semua kuliner di tengah")
        self.assertEqual((r.action, r.entities["location"]), ("cari", "Balikpapan Tengah"))

    def test_kota_saja_bukan_lokasi(self):
        self.assertIsNone(interpret("makanan enak di kota ini").location)

    def test_tidak_dikenal(self):
        r = interpret("cuacanya cerah hari ini")
        self.assertEqual(r.action, "tidak_dikenal")
        self.assertIn("belum mengerti", r.message)


class EndpointTest(unittest.TestCase):
    def test_post(self):
        res = TestClient(app).post("/api/voice-nlp", json={"text": "aku butuh makanan di balikpapan selatan"})
        self.assertEqual(res.status_code, 200)
        body = res.json()
        self.assertEqual(body["intent"], "cari_makanan")
        self.assertEqual(body["location"], "balikpapan selatan")
        self.assertEqual(body["message"], "Baik, mencari makanan di balikpapan selatan.")

    def test_teks_wajib(self):
        self.assertEqual(TestClient(app).post("/api/voice-nlp", json={}).status_code, 422)


if __name__ == "__main__":
    unittest.main()
