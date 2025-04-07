#!/bin/bash

# Créer le dossier des polices s'il n'existe pas
mkdir -p public/build/fonts

# Télécharger Meow Script
curl -L "https://github.com/google/fonts/raw/main/ofl/meowscript/MeowScript-Regular.ttf" -o "public/build/fonts/MeowScript-Regular.ttf"

# Télécharger Montserrat
curl -L "https://github.com/google/fonts/raw/main/ofl/montserrat/static/Montserrat-Regular.ttf" -o "public/build/fonts/Montserrat-Regular.ttf"
curl -L "https://github.com/google/fonts/raw/main/ofl/montserrat/static/Montserrat-Bold.ttf" -o "public/build/fonts/Montserrat-Bold.ttf"

# Donner les permissions appropriées
chmod 644 public/build/fonts/*.ttf 