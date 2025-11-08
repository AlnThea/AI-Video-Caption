import os
import json
import subprocess
import wave
import io
import random
from vosk import Model, KaldiRecognizer
from datetime import timedelta

# CONFIGURATION
# CONFIGURATION - Use raw strings for Windows paths
AUDIO_FILE = os.path.join("audio", "0512.wav")
MODEL_DIR = os.path.join("vosk-model", "vosk-model-en-us-0.22")
OUTPUT_ASS = os.path.join("subtitles", "output.ass")

# Fungsi untuk format waktu ASS
def format_time(seconds):
    h, rem = divmod(seconds, 3600)
    m, s = divmod(rem, 60)
    return f"{int(h)}:{int(m):02d}:{int(s):02d}.{int((s - int(s)) * 100):02d}"


# Stopwords dan aturan pembagian teks
STOPWORDS = {"a", "the", "of", "in", "on", "to", "is", "and", "for", "with", "at", "by"}


def split_text(text):
    words = text.split()

    # Jika kata <= 3, jadikan 1 baris
    if len(words) <= 3:
        return text.upper()

    # Gabungkan beberapa kata secara acak (min 2 kata per baris)
    lines = []
    remaining_words = words.copy()

    while len(remaining_words) > 0 and len(lines) < 3:
        # Ambil 2-4 kata acak untuk tiap baris
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
,10,230,0

[Events]
Format: Layer, Start, End, Style, Text
"""
    with open(OUTPUT_ASS, "w", encoding="utf-8") as f:
        f.write(ass_header)

        # Step 1: Collect ALL words first
        all_words = []
        for item in transcript:
            if 'result' in item:
                all_words.extend(item['result'])

        # Step 2: Group into natural phrases (3-7 words each)
        phrases = []
        current_phrase = []
        for word in all_words:
            current_phrase.append(word)
            # End phrase at natural breaks (.,?!) or 3-7 words
            if (word['word'][-1] in {'.', '?', '!'} or
                    len(current_phrase) >= random.randint(2, 3)):
                phrases.append(current_phrase)
                current_phrase = []

        # Step 3: Process each phrase into 3-line captions
        for phrase in phrases:
            if not phrase:
                continue

            # Get start/end times
            start = phrase[0]['start'] - 0.3  # Start slightly earlier
            end = phrase[-1]['end'] + 0.3  # End slightly later

            # Combine all words in this phrase
            full_text = " ".join(word['word'] for word in phrase)

            # Split into 3 lines randomly
            text = split_text(full_text)

            f.write(
                  f"Dialogue: 0,{format_time(start)},{format_time(end)},Default,{{\\fad(200,200)}}{text}\n")

if __name__ == "__main__":
    # Ekstrak audio jika belum ada
    if not os.path.exists(AUDIO_FILE):
        subprocess.run([
            "/usr/bin/ffmpeg",
            "-i", "upload/0512.mp4",
            "-vn", "-ar", "16000", "-ac", "1",
            "-f", "wav", AUDIO_FILE
        ], check=True)

    # Transkripsi dengan VOSK
    model = Model(MODEL_DIR)
    rec = KaldiRecognizer(model, 16000)
    rec.SetWords(True)

    with wave.open(AUDIO_FILE, "rb") as wf:
        results = []
        while True:
            data = wf.readframes(4000)
            if len(data) == 0:
                break
            if rec.AcceptWaveform(data):
                results.append(json.loads(rec.Result()))
        results.append(json.loads(rec.FinalResult()))

    generate_ass(results)
    print(f"Subtitle berhasil dibuat: {OUTPUT_ASS}")
