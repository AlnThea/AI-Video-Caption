import sys
import wave
import json
import os
from vosk import Model, KaldiRecognizer

# ✅ Path model Vosk
model_path = r"C:\laragon\www\avc\vosk-model\vosk-model-en-us-0.22"
model = Model(model_path)
rec = KaldiRecognizer(model, 16000)
rec.SetWords(True)

# ✅ Gunakan argumen file WAV dari PHP
audio_file = sys.argv[1] if len(sys.argv) > 1 else "sound/0125.wav"

# ✅ Path indikator stop dan folder output
stop_file = "stop.txt"
subtitle_folder = "subtitle"

# ✅ Pastikan folder output ada
os.makedirs(subtitle_folder, exist_ok=True)

# ✅ Buka file WAV
try:
    wf = wave.open(audio_file, "rb")
except Exception as e:
    print(f"❌ Gagal membuka file WAV: {e}")
    sys.exit()

transcribed_text = []
print("⏳ Transkripsi dimulai...")

while True:
    if os.path.exists(stop_file):
        print("⛔ Proses dihentikan oleh pengguna!")
        break

    data = wf.readframes(4000)
    if len(data) == 0:
        break

    if rec.AcceptWaveform(data):
        result = json.loads(rec.Result())
        if "result" in result:
            transcribed_text.extend(result["result"])
            print(f"✅ Progress Transkripsi: {len(transcribed_text)} kata dikenali...")

if not os.path.exists(stop_file) and transcribed_text:
    final_result = json.loads(rec.FinalResult())
    if "result" in final_result:
        transcribed_text.extend(final_result["result"])

    # ✅ Simpan hasil sebagai JSON di folder subtitle/
    json_path = os.path.join(subtitle_folder, "transcription.json")
    with open(json_path, "w", encoding="utf-8") as f:
        json.dump({"result": transcribed_text}, f, indent=2)

    # ✅ Simpan progress ke `progress.txt`
    with open("progress.txt", "w") as p:
        p.write("70")  # ✅ Update progress setelah transkripsi selesai

    print(f"✅ Transkripsi selesai! JSON berhasil dibuat sebagai '{json_path}'")

    # ✅ Buat subtitle rolling `.ass`
    ass_file = os.path.join(subtitle_folder, "0125.ass")

    # ✅ Format waktu untuk subtitle
    def format_time(seconds):
        h = int(seconds // 3600)
        m = int((seconds % 3600) // 60)
        s = int(seconds % 60)
        ms = int((seconds - int(seconds)) * 100)
        return f"{h:01d}:{m:02d}:{s:02d}.{ms:02d}"

    ass_content = "[Script Info]\nTitle: Rolling Subtitle\nScriptType: v4.00+\n\n[V4+ Styles]\n"
    ass_content += "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Alignment\n"
    ass_content += "Style: Default,Titan One,80,&H0000FF00,&Hffffff,&H0,&H0,2\n\n[Events]\n"
    ass_content += "Format: Layer, Start, End, Style, Text\n"

    for entry in transcribed_text:
        start_time = format_time(entry["start"])
        end_time = format_time(entry["end"])
        text = entry["word"].upper()
        ass_content += f"Dialogue: 0,{start_time},{end_time},Default,{text}\n"

    with open(ass_file, "w", encoding="utf-8") as f:
        f.write(ass_content)

    print(f"🎉 Subtitle rolling berhasil dibuat sebagai '{ass_file}'!")

    # ✅ Simpan progress ke `progress.txt`
    with open("progress.txt", "w") as p:
        p.write("100")  # ✅ Update progress setelah subtitle selesai

else:
    print("❌ Error: Tidak ada teks yang terdeteksi!")