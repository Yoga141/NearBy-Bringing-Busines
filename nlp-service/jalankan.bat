@echo off
rem Menjalankan layanan NLP asisten suara NearBy di http://127.0.0.1:8001
rem Pertama kali dijalankan: membuat virtualenv dan memasang dependensi.
cd /d "%~dp0"
if not exist .venv (
  python -m venv .venv || exit /b 1
  .venv\Scripts\python -m pip install -r requirements.txt || exit /b 1
)
.venv\Scripts\python -m uvicorn main:app --host 127.0.0.1 --port 8001 --reload
