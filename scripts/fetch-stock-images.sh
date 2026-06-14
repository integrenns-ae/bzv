#!/usr/bin/env bash
# Lädt CC-BY/Public-Domain-Imkerei-Bilder von Wikimedia Commons,
# konvertiert sie zu WebP in 3 Größen, schreibt CREDITS.tsv.
# Voraussetzung: cwebp, python3, curl.

set -euo pipefail

STOCK_DIR="$(cd "$(dirname "$0")/../public/bilder/stock" && pwd)"
TMP=$(mktemp -d)
LIST="$TMP/list.tsv"
trap "rm -rf $TMP" EXIT

# Suchen: query|slug|count
SEARCHES=(
  "beekeeper|imker|2"
  "honeybee flower|biene-bluete|3"
  "honeycomb bees|wabe|2"
  "honey jar|honig|1"
  "bee swarm tree|schwarm|1"
  "apiary|bienenstand|2"
  "bee smoker|smoker|1"
)

>"$LIST"
for entry in "${SEARCHES[@]}"; do
  IFS='|' read -r query slug count <<<"$entry"
  echo "Suche: $slug ($query, $count)" >&2
  curl -s "https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrsearch=${query// /%20}&gsrlimit=8&prop=imageinfo&iiprop=url|extmetadata|size&iiurlwidth=1920" \
  | SLUG="$slug" COUNT="$count" python3 -c '
import json, sys, re, os
slug = os.environ["SLUG"]
count = int(os.environ["COUNT"])
d = json.load(sys.stdin)
pages = list(d.get("query", {}).get("pages", {}).values())
out = []
for p in pages:
  ii = (p.get("imageinfo") or [{}])[0]
  if not ii or ii.get("width", 0) < 1000: continue
  url = ii.get("thumburl") or ii.get("url","")
  title = p.get("title","").replace("File:","")
  meta  = ii.get("extmetadata", {})
  artist = re.sub(r"<[^>]+>","", (meta.get("Artist",{}).get("value","") or "")).strip()[:150]
  lic    = meta.get("LicenseShortName",{}).get("value","") or "CC-BY-SA"
  out.append((ii["width"], url, title, artist, lic))
out.sort(key=lambda x: -x[0])
for i,(w,u,t,a,l) in enumerate(out[:count], start=1):
  print(f"{slug}-{i}\t{u}\t{t}\t{a}\t{l}")
' >>"$LIST"
done

echo "" >&2
echo "Heruntergeladen wird:" >&2
column -t -s $'\t' "$LIST" | cut -c1-100 >&2
echo "" >&2

cd "$STOCK_DIR"
echo -e "name\tattribution\tlicense\tsource" > CREDITS.tsv

while IFS=$'\t' read -r slug url title artist lic; do
  [ -z "$slug" ] && continue
  echo "↓ $slug" >&2
  curl -sL -A "bzv-gruenberg-migration/1.0" "$url" -o "$TMP/${slug}.jpg"
  for w in 480 960 1920; do
    cwebp -quiet -q 80 -resize "$w" 0 "$TMP/${slug}.jpg" -o "${slug}-${w}.webp"
  done
  # JPEG-Fallback in 1920px
  sips -Z 1920 -s formatOptions 82 "$TMP/${slug}.jpg" -o "${slug}.jpg" >/dev/null
  src_url="https://commons.wikimedia.org/wiki/File:${title// /_}"
  printf "%s\t%s\t%s\t%s\n" "$slug" "$artist" "$lic" "$src_url" >> CREDITS.tsv
done < "$LIST"

echo "" >&2
echo "Fertig:" >&2
echo "  $(ls *.webp | wc -l | tr -d ' ') WebP, $(ls *.jpg | wc -l | tr -d ' ') JPG-Fallbacks, $(du -sh . | cut -f1)" >&2
