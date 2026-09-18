<template>
  <div class="max-w-4xl mx-auto p-6 space-y-8">
    <h1 class="text-2xl font-bold">Dashboard UMKM Saya</h1>

    <form @submit.prevent="saveProfile" class="bg-white rounded-xl shadow p-6 space-y-4">
      <h2 class="text-lg font-semibold">Informasi Usaha</h2>

      <div>
        <label class="block text-sm font-medium mb-1">Nama Usaha</label>
        <input v-model="form.business_name" type="text" class="w-full border rounded-lg px-3 py-2" required />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Jenis Usaha (Katalog)</label>
          <select v-model="form.category_id" class="w-full border rounded-lg px-3 py-2">
            <option value="">Pilih Kategori</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Lokasi (Kecamatan)</label>
          <select v-model="form.location_id" class="w-full border rounded-lg px-3 py-2">
            <option value="">Pilih Lokasi</option>
            <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Alamat Detail</label>
        <input v-model="form.address_detail" type="text" class="w-full border rounded-lg px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Detail Usaha</label>
        <textarea v-model="form.description" rows="4" class="w-full border rounded-lg px-3 py-2"></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="form.instagram" placeholder="Instagram" class="border rounded-lg px-3 py-2" />
        <input v-model="form.facebook" placeholder="Facebook" class="border rounded-lg px-3 py-2" />
        <input v-model="form.whatsapp" placeholder="WhatsApp" class="border rounded-lg px-3 py-2" />
        <input v-model="form.tiktok" placeholder="TikTok" class="border rounded-lg px-3 py-2" />
      </div>

      <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg" :disabled="saving">
        {{ saving ? 'Menyimpan...' : 'Simpan Profil' }}
      </button>
      <p v-if="message" :class="isError ? 'text-red-600' : 'text-green-600'" class="text-sm">{{ message }}</p>
    </form>

    <div v-if="umkmId" class="bg-white rounded-xl shadow p-6 space-y-4">
      <h2 class="text-lg font-semibold">Foto Tempat Usaha</h2>
      <div class="flex flex-wrap gap-4">
        <div v-for="p in photos" :key="p.id" class="relative w-32 h-32">
          <img :src="photoUrl(p.photo_path)" class="w-full h-full object-cover rounded-lg" />
          <button @click="deletePhoto(p.id)" class="absolute -top-2 -right-2 bg-red-600 text-white w-6 h-6 rounded-full text-xs">✕</button>
        </div>
      </div>
      <input type="file" accept="image/*" @change="uploadPhoto" />
    </div>

    <div v-if="umkmId" class="bg-white rounded-xl shadow p-6 space-y-4">
      <h2 class="text-lg font-semibold">Produk & Harga</h2>
      <form @submit.prevent="saveProduct" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input v-model="productForm.name" placeholder="Nama Produk" class="border rounded-lg px-3 py-2 md:col-span-2" required />
        <input v-model="productForm.price" type="number" min="0" placeholder="Harga" class="border rounded-lg px-3 py-2" required />
        <button type="submit" class="bg-green-600 text-white rounded-lg px-3 py-2">{{ productForm.id ? 'Update' : 'Tambah' }}</button>
      </form>
      <table class="w-full text-sm mt-4">
        <tbody>
          <tr v-for="p in products" :key="p.id" class="border-b">
            <td class="py-2">{{ p.name }}</td>
            <td>Rp {{ Number(p.price).toLocaleString('id-ID') }}</td>
            <td class="text-right space-x-2">
              <button @click="editProduct(p)" class="text-blue-600">Edit</button>
              <button @click="deleteProduct(p.id)" class="text-red-600">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/services/api'

const categories = ref([]), locations = ref([]), photos = ref([]), products = ref([])
const umkmId = ref(null), saving = ref(false), message = ref(''), isError = ref(false)

const form = reactive({ business_name: '', category_id: '', location_id: '', address_detail: '', description: '', instagram: '', facebook: '', whatsapp: '', tiktok: '' })
const productForm = reactive({ id: null, name: '', price: '', description: '' })

const photoUrl = (path) => `${api.defaults.baseURL}/${path}`

async function loadLookups() {
  const [c, l] = await Promise.all([api.get('/api/categories.php'), api.get('/api/locations.php')])
  categories.value = c.data.data
  locations.value = l.data.data
}

async function loadProfile() {
  const { data } = await api.get('/api/umkm/my-profile.php')
  if (data.data) {
    Object.assign(form, data.data)
    umkmId.value = data.data.id
    photos.value = data.data.photos || []
    products.value = data.data.products || []
  }
}

async function saveProfile() {
  saving.value = true
  try {
    const { data } = await api.post('/api/umkm/profile.php', form)
    umkmId.value = data.id
    message.value = 'Profil berhasil disimpan.'
    isError.value = false
    await loadProfile()
  } catch (e) {
    message.value = e.response?.data?.message || 'Gagal menyimpan.'
    isError.value = true
  } finally {
    saving.value = false
  }
}

async function uploadPhoto(e) {
  const file = e.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('photo', file)
  await api.post('/api/umkm/photos.php', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
  e.target.value = ''
  await loadProfile()
}

async function deletePhoto(id) { await api.delete(`/api/umkm/photos.php?id=${id}`); await loadProfile() }

async function saveProduct() {
  await api.post('/api/umkm/products.php', productForm)
  Object.assign(productForm, { id: null, name: '', price: '', description: '' })
  await loadProfile()
}

function editProduct(p) { Object.assign(productForm, p) }
async function deleteProduct(id) { await api.delete(`/api/umkm/products.php?id=${id}`); await loadProfile() }

onMounted(async () => { await loadLookups(); await loadProfile() })
</script>
