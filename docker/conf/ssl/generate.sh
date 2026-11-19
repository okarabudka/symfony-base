#!/bin/sh
# Self-signed certificate for local HTTPS; generated on the host, never committed.
set -eu
cd "$(dirname "$0")"
[ -f cert.pem ] && [ -f cert.key ] && { echo "cert.pem and cert.key already exist"; exit 0; }
openssl req -x509 -nodes -newkey rsa:2048 -days 825 -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" -keyout cert.key -out cert.pem
echo "created docker/conf/ssl/cert.pem and cert.key"
