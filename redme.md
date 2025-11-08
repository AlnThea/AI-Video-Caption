lib python
python -m pip install vosk
python -m pip install matplotlib


install choco cmd
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
choco install ffmpeg

fonts
Titan One Regular

C:\Python313\python.exe
python transcribe_to_ass.py     
ffmpeg -i 0125.mp4 -vf "subtitles=0125.ass" -c:a copy 0125.mp4  