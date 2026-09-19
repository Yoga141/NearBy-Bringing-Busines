"""
Layanan NLP asisten suara NearBy.

    POST /api/voice-nlp   {"text": "...", "require_wake": false}
    GET  /api/voice-nlp/health

Terpisah dari backend Laravel: Laravel tetap memegang data UMKM, layanan ini
hanya menafsirkan teks. Saat pengembangan, Vite meneruskan `/api/voice-nlp` ke
sini (lihat frontend/vite.config.ts) dan sisa `/api` ke Laravel.

Jalankan:  uvicorn main:app --port 8001 --reload
"""

import os
from dataclasses import asdict

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from nlp import interpret

app = FastAPI(title="NearBy Voice NLP", version="1.0.0")

# Layanan ini tidak memakai cookie maupun data pribadi - hanya teks masuk, JSON
# keluar - jadi CORS terbuka aman. Batasi lewat NLP_CORS_ORIGINS bila perlu,
# mis. "https://nearbybalikpapan.com".
app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in os.getenv("NLP_CORS_ORIGINS", "*").split(",")],
    allow_methods=["GET", "POST"],
    allow_headers=["Content-Type", "Accept"],
)


class VoiceRequest(BaseModel):
    text: str = Field(..., max_length=500, description="Transkrip mentah dari SpeechRecognition")
    require_wake: bool = Field(
        False,
        description='True saat asisten siaga: ucapan tanpa "Oke NearBy" dijawab intent "abaikan".',
    )


class Entities(BaseModel):
    category: str | None
    location: str | None
    page: str | None
    keyword: str
    index: int | None


class VoiceResponse(BaseModel):
    intent: str
    action: str
    location: str | None
    message: str
    wake: bool
    entities: Entities
    text: str


@app.post("/api/voice-nlp", response_model=VoiceResponse)
def voice_nlp(req: VoiceRequest) -> dict:
    return asdict(interpret(req.text, req.require_wake))


@app.get("/api/voice-nlp/health")
def health() -> dict:
    return {"status": "ok"}
