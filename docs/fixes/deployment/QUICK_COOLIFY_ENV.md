# Quick Guide: Environment Variables di Coolify

## 🎯 Jawaban Singkat

**Q: Apakah perlu `env_file: - .env.production` di Coolify?**

**A: YA, PERTAHANKAN** ✅

---

## 📊 Perbandingan

### **Opsi 1: Dengan `env_file` (RECOMMENDED)** ✅

```yaml
services:
  app:
    env_file:
      - .env.production  # ← KEEP THIS
```

**Kelebihan:**
- ✅ Easy to manage (1 file untuk semua config)
- ✅ Bisa test di local dengan exact production config
- ✅ Easy rollback (tinggal revert file)
- ✅ Version controlled (bisa track changes)
- ✅ Portable (bisa deploy di mana saja)

**Kekurangan:**
- ⚠️ Harus hati-hati jangan commit secrets

---

### **Opsi 2: Tanpa `env_file` (NOT RECOMMENDED)** ❌

```yaml
services:
  app:
    # NO env_file
    # Semua env vars dari Coolify UI
```

**Kelebihan:**
- ✅ Secrets tidak di file

**Kekurangan:**
- ❌ Harus input 100+ variables manual di Coolify
- ❌ Tidak bisa test di local
- ❌ Sulit rollback
- ❌ Tidak ada version control
- ❌ Terikat dengan Coolify

---

## ✅ Rekomendasi

### **PERTAHANKAN Setup Current:**

```yaml
# docker-compose.yaml
services:
  app:
    env_file:
      - .env.production  # ← KEEP THIS
    environment:
      APP_ENV: production
      CONTAINER_ROLE: app
```

**Dengan:**
1. ✅ `.env.production` di server (JANGAN commit ke git)
2. ✅ `.env.production.template` di git (untuk reference)
3. ✅ Add ke `.gitignore`:
   ```
   .env.production
   ```

---

## 🔐 Security Best Practice

### **Jangan Commit Secrets:**

```gitignore
# .gitignore
.env
.env.production
.env.local

# Keep templates
!.env.example
!.env.production.template
```

### **Deployment Flow:**

1. **Push code** (tanpa `.env.production`)
2. **Di server**, create `.env.production`:
   ```bash
   cp .env.production.template .env.production
   nano .env.production  # Edit secrets
   ```
3. **Deploy via Coolify**

---

## 🎯 Kesimpulan

**Untuk production di Coolify:**

| Aspek | Rekomendasi |
|-------|-------------|
| **`env_file`** | ✅ PERTAHANKAN |
| **`.env.production`** | ✅ Di server, JANGAN di git |
| **`.env.production.template`** | ✅ Di git (tanpa secrets) |
| **Coolify env vars** | ⚠️ Optional (untuk override secrets) |

**Setup current Anda sudah BENAR** ✅

**Yang perlu dilakukan:**
1. ✅ Add `.env.production` ke `.gitignore`
2. ✅ Create `.env.production.template` untuk reference
3. ✅ Deploy as usual

---

**Last Updated:** 2024-01-15
**Status:** Current setup is GOOD ✅
