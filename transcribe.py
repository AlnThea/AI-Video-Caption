import os
import json
import subprocess
import wave
import random
from vosk import Model, KaldiRecognizer
import sys

# CONFIGURATION - DYNAMIC FILENAME ONLY
# HARUS ada argument filename, tidak ada fallback!
if len(sys.argv) > 1:
    input_filename = sys.argv[1]
    base_name = os.path.splitext(input_filename)[0]

    AUDIO_FILE = os.path.join("audio", base_name + ".wav")
    INPUT_VIDEO = os.path.join("upload", input_filename)
    OUTPUT_ASS = os.path.join("subtitles", base_name + ".ass")
else:
    # ERROR - tidak ada filename provided
    print("[ERROR] Tidak ada filename provided. Usage: python transcribe.py <filename>")
    sys.exit(1)

MODEL_DIR = os.path.join("vosk-model", "vosk-model-en-us-0.22")
FFMPEG_PATH = r"C:\ProgramData\chocolatey\bin\ffmpeg.exe"


def safe_print(message):
    """Print yang aman untuk Windows console"""
    try:
        print(message)
    except UnicodeEncodeError:
        message = message.replace('🎧', '[AUDIO]')
        message = message.replace('🎤', '[TRANSCRIBE]')
        message = message.replace('✅', '[SUCCESS]')
        message = message.replace('❌', '[ERROR]')
        message = message.replace('💡', '[INFO]')
        print(message)


def format_time(seconds):
    h, rem = divmod(seconds, 3600)
    m, s = divmod(rem, 60)
    return f"{int(h)}:{int(m):02d}:{int(s):02d}.{int((s - int(s)) * 100):02d}"


def split_text(text):
    words = text.split()
    if len(words) <= 3:
        return text.upper()

    lines = []
    remaining_words = words.copy()
    while len(remaining_words) > 0 and len(lines) < 3:
        chunk_size = random.randint(2, min(4, len(remaining_words)))
        chunk = " ".join(remaining_words[:chunk_size])
        lines.append(chunk)
        remaining_words = remaining_words[chunk_size:]

    return r"\N".join(line.upper() for line in lines)


def generate_ass(transcript):
    ass_header = """[Script Info]
Title: Natural Phrase Subtitle
ScriptType: v4.00+
PlayResX: 1080
PlayResY: 1920

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline,StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default,Titan One,65,&H00FFFFFF,&H000000FF,&H00000000,&H00000000,5,0,0,0,100,100,0,0,1,1,1,1,100,10,230,0

[Events]
Format: Layer, Start, End, Style, Text
"""

    try:
        os.makedirs(os.path.dirname(OUTPUT_ASS), exist_ok=True)

        with open(OUTPUT_ASS, "w", encoding="utf-8") as f:
            f.write(ass_header)

            all_words = []
            for item in transcript:
                if 'result' in item:
                    all_words.extend(item['result'])

            phrases = []
            current_phrase = []
            for word in all_words:
                current_phrase.append(word)
                if (word['word'][-1] in {'.', '?', '!'} or
                        len(current_phrase) >= random.randint(2, 3)):
                    phrases.append(current_phrase)
                    current_phrase = []

            if current_phrase:
                phrases.append(current_phrase)

            for phrase in phrases:
                if not phrase:
                    continue

                start = max(0, phrase[0]['start'] - 0.3)
                end = phrase[-1]['end'] + 0.3
                full_text = " ".join(word['word'] for word in phrase)
                text = split_text(full_text)

                f.write(f"Dialogue: 0,{format_time(start)},{format_time(end)},Default,{{\\fad(200,200)}}{text}\n")

        safe_print("[SUCCESS] Subtitle berhasil dibuat: " + OUTPUT_ASS)
        return True

    except Exception as e:
        safe_print("[ERROR] Error membuat subtitle: " + str(e))
        return False


def main():
    try:
        safe_print(f"[INFO] Processing video file: {INPUT_VIDEO}")
        safe_print(f"[INFO] Output audio file: {AUDIO_FILE}")
        safe_print(f"[INFO] Output subtitle file: {OUTPUT_ASS}")

        # 1. Check dependencies
        if not os.path.exists(FFMPEG_PATH):
            safe_print("[ERROR] FFmpeg tidak ditemukan")
            return False

        # 2. Check input video - HARUS ADA FILE UPLOADED
        if not os.path.exists(INPUT_VIDEO):
            safe_print("[ERROR] File video tidak ditemukan: " + INPUT_VIDEO)
            return False

        # 3. Check model VOSK
        if not os.path.exists(MODEL_DIR):
            safe_print("[ERROR] Model VOSK tidak ditemukan")
            return False

        # 4. Ekstrak audio DARI FILE UPLOADED
        safe_print("[AUDIO] Mengekstrak audio dari video...")
        os.makedirs(os.path.dirname(AUDIO_FILE), exist_ok=True)

        result = subprocess.run([
            FFMPEG_PATH,
            "-y", "-i", INPUT_VIDEO,  # FILE UPLOADED
            "-vn", "-ar", "16000", "-ac", "1",
            "-f", "wav", AUDIO_FILE  # AUDIO DARI FILE UPLOADED
        ], capture_output=True, text=True, timeout=300)

        if result.returncode != 0:
            safe_print("[ERROR] Error ekstrak audio: " + result.stderr)
            return False

        if not os.path.exists(AUDIO_FILE):
            safe_print("[ERROR] File audio tidak berhasil dibuat: " + AUDIO_FILE)
            return False

        safe_print("[SUCCESS] Audio berhasil diekstrak: " + AUDIO_FILE)

        # 5. Transkripsi dengan VOSK
        safe_print("[TRANSCRIBE] Memulai transkripsi audio...")
        model = Model(MODEL_DIR)
        rec = KaldiRecognizer(model, 16000)
        rec.SetWords(True)

        results = []
        with wave.open(AUDIO_FILE, "rb") as wf:
            if wf.getnchannels() != 1 or wf.getsampwidth() != 2 or wf.getframerate() != 16000:
                safe_print("[ERROR] Format audio tidak sesuai")
                return False

            while True:
                data = wf.readframes(4000)
                if len(data) == 0:
                    break
                if rec.AcceptWaveform(data):
                    results.append(json.loads(rec.Result()))
            results.append(json.loads(rec.FinalResult()))

        safe_print("[SUCCESS] Transkripsi selesai. " + str(len(results)) + " results ditemukan")

        # 6. Generate subtitle
        return generate_ass(results)

    except Exception as e:
        safe_print("[ERROR] Error dalam proses transkripsi: " + str(e))
        return False


if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)